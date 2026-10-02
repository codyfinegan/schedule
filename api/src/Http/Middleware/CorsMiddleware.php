<?php
declare(strict_types=1);

namespace Schedule\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Config;

class CorsMiddleware implements MiddlewareInterface
{
    private const string ALLOWED_METHODS = 'GET, POST';
    private const string ALLOWED_HEADERS = 'Content-Type, X-CSRF-Token';

    public function __construct(
        private readonly Config $config,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = $origin !== '' && $origin === $this->config->origin_url;

        if ($this->isPreflight($request)) {
            $response = $this->responseFactory->createResponse(204);
            if ($allowed) {
                $response = $response
                    ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
                    ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
                    ->withHeader('Access-Control-Max-Age', '600');
            }
        } elseif ($origin !== '' && !$allowed && !$this->isSafeMethod($request)) {
            // A simple (non-preflighted) cross-site request, e.g. a form post, would
            // otherwise reach the handler with only the CORS headers withheld.
            // Requests without an Origin are not from a browser on another site
            // (server-to-server webhooks), so they pass through.
            $response = $this->responseFactory->createResponse(403);
        } else {
            $response = $handler->handle($request);
        }

        $response = $response->withAddedHeader('Vary', 'Origin');
        if (!$allowed) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    private function isPreflight(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS'
            && $request->hasHeader('Access-Control-Request-Method');
    }

    private function isSafeMethod(ServerRequestInterface $request): bool
    {
        return in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}