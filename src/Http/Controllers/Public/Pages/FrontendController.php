<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Public\Pages;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Streams built Vue asset bundles (client/, built into storage/frontend/ --
 * never exposed directly by Caddy) for /assets/* and /favicon.ico. Page
 * routes (see app/routes.php's Pages\* controllers) own everything else.
 */
final class FrontendController
{
    private const MIME_TYPES = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'text/javascript',
        'mjs' => 'text/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'map' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=utf-8',
    ];

    private readonly string $distPath;

    public function __construct(string $distPath)
    {
        $this->distPath = rtrim($distPath, '/');
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $requestedFile = $this->resolveRequestedFile($request);

        if ($requestedFile === null) {
            return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], 'Not found.');
        }

        return $this->fileResponse($requestedFile);
    }

    private function resolveRequestedFile(ServerRequestInterface $request): ?string
    {
        $path = rawurldecode(trim($request->getUri()->getPath(), '/'));
        if ($path === '') {
            return null;
        }

        $realDistPath = realpath($this->distPath);
        $realFilePath = realpath($this->distPath . '/' . $path);

        if ($realDistPath === false || $realFilePath === false) {
            return null;
        }

        if (!str_starts_with($realFilePath, $realDistPath . DIRECTORY_SEPARATOR) || !is_file($realFilePath)) {
            return null;
        }

        return $realFilePath;
    }

    private function fileResponse(string $filePath): ResponseInterface
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $headers = [
            'Content-Type' => self::MIME_TYPES[$extension] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];

        return new Response(200, $headers, Stream::create(fopen($filePath, 'rb')));
    }
}
