<?php

declare(strict_types=1);

namespace Schedule\View;

use RuntimeException;

/**
 * Resolves <script>/<link> tags for a Vite entry -- either pointing at the
 * Vite dev server (when configured) or by walking the built manifest.json's
 * chunk graph, so CSS pulled in transitively by a Vue SFC (rather than the
 * entry file itself) isn't silently dropped.
 */
final class ViteManifest
{
    private ?array $manifest = null;

    public function __construct(
        private readonly string $distPath,
        private readonly ?string $devServerUrl,
    ) {
    }

    public function tags(string $entryName): string
    {
        if ($this->devServerUrl !== null) {
            $base = rtrim($this->devServerUrl, '/');

            return implode("\n", [
                sprintf('<script type="module" src="%s/@vite/client"></script>', $base),
                sprintf('<script type="module" src="%s/src/entries/%s.ts"></script>', $base, $entryName),
            ]);
        }

        $manifest = $this->loadManifest();
        $entryKey = "src/entries/{$entryName}.ts";

        if (!isset($manifest[$entryKey])) {
            throw new RuntimeException("Vite manifest has no entry for \"{$entryKey}\".");
        }

        [$cssFiles, $jsFiles] = $this->collectChunks($manifest, $entryKey);
        $entryFile = $manifest[$entryKey]['file'];

        $tags = [];
        foreach ($cssFiles as $css) {
            $tags[] = sprintf('<link rel="stylesheet" href="/%s">', $css);
        }
        foreach ($jsFiles as $js) {
            if ($js === $entryFile) {
                continue;
            }
            $tags[] = sprintf('<link rel="modulepreload" href="/%s">', $js);
        }
        $tags[] = sprintf('<script type="module" src="/%s"></script>', $entryFile);

        return implode("\n", $tags);
    }

    /**
     * @return array{0: list<string>, 1: list<string>} [cssFiles, jsFiles]
     */
    private function collectChunks(array $manifest, string $key, array &$visited = []): array
    {
        if (isset($visited[$key])) {
            return [[], []];
        }
        $visited[$key] = true;

        $chunk = $manifest[$key];
        $css = $chunk['css'] ?? [];
        $js = [$chunk['file']];

        foreach ($chunk['imports'] ?? [] as $importKey) {
            [$importedCss, $importedJs] = $this->collectChunks($manifest, $importKey, $visited);
            $css = [...$css, ...$importedCss];
            $js = [...$js, ...$importedJs];
        }

        return [array_values(array_unique($css)), array_values(array_unique($js))];
    }

    private function loadManifest(): array
    {
        if ($this->manifest === null) {
            $path = $this->distPath . '/.vite/manifest.json';
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException("Vite manifest not found at {$path}. Run \"npm run build\" in client/.");
            }

            $this->manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        }

        return $this->manifest;
    }
}
