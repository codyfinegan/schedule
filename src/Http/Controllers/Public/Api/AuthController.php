<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Public\Api;

use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Config;
use Schedule\Http\Concerns\{ParsesJsonBody, RespondsWithJson};
use Schedule\Http\Exceptions\HttpException;
use Schedule\Http\Resources\UserResource;
use Schedule\Models\{LoginCode, Session, User};
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AuthController
{
    use RespondsWithJson;
    use ParsesJsonBody;

    public function __construct(
        private readonly Config $config,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function requestCode(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        $email = strtolower(trim((string)($body['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw HttpException::unprocessable('A valid email is required.', ['email' => 'invalid']);
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $user = User::create(['email' => $email]);
        }

        $code = (string)random_int(100000, 999999);

        LoginCode::create([
            'user_id' => $user->id,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(
                (int)$this->config->get('login_code.ttl_minutes', 15)
            ),
        ]);

        $this->sendCodeEmail($user, $code);

        // Always 200 -- don't leak whether an email is registered.
        return $this->json(['message' => 'If that email is registered, a login code has been sent.']);
    }

    private function sendCodeEmail(User $user, string $code): void
    {
        $email = (new Email())
            ->from($this->config->get('mail.from'))
            ->to($user->email)
            ->subject('Your Schedule login code')
            ->text(
                "Your login code is: {$code}\n\nIt expires in {$this->config->get('login_code.ttl_minutes')} minutes."
            );

        $this->mailer->send($email);
    }

    public function verifyCode(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $code = trim((string)($body['code'] ?? ''));

        $invalid = fn() => throw HttpException::unauthorized('Invalid or expired code.');

        if ($email === '' || $code === '') {
            $invalid();
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $invalid();
        }

        $loginCode = LoginCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'))
            ->orderByDesc('id')
            ->get()
            ->first(fn(LoginCode $candidate) => password_verify($code, $candidate->code_hash));

        if ($loginCode === null) {
            $invalid();
        }

        $loginCode->consumed_at = CarbonImmutable::now('UTC');
        $loginCode->save();

        if (!$user->is_approved) {
            throw HttpException::forbidden('Your account is pending admin approval.');
        }

        $token = bin2hex(random_bytes(32));
        $ttlDays = (int)$this->config->get('session.ttl_days', 365);
        $expiresAt = CarbonImmutable::now('UTC')->addDays($ttlDays);

        Session::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        $response = $this->json(['user' => UserResource::make($user)]);

        return $this->withSessionCookie($response, $token, $expiresAt);
    }

    private function withSessionCookie(
        ResponseInterface $response,
        string $token,
        CarbonImmutable $expiresAt
    ): ResponseInterface {
        $secure = $this->config->get('session.secure', true) ? '; Secure' : '';

        $cookie = sprintf(
            '%s=%s; Expires=%s; Path=/; HttpOnly%s; SameSite=Lax',
            $this->config->get('session.cookie_name'),
            $token,
            $expiresAt->format(DATE_COOKIE),
            $secure,
        );

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        if ($session instanceof Session) {
            $session->delete();
        }

        $response = $this->json(['message' => 'Logged out.']);

        return $this->withSessionCookie($response, '', CarbonImmutable::createFromTimestamp(0));
    }
}
