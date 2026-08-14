<?php

declare(strict_types=1);

namespace Schedule\View;

/**
 * Hand-rolled PHP view rendering -- no templating engine, matching the rest
 * of the codebase's minimal-dependency style. Templates are plain PHP files
 * under resources/views/ evaluated with $data extracted into scope.
 */
final readonly class TemplateRenderer
{
    private string $viewsPath;

    public function __construct(
        string $viewsPath,
        private ViteManifest $vite,
    ) {
        $this->viewsPath = rtrim($viewsPath, '/');
    }

    /**
     * Renders resources/views/pages/{$page}.php as the page body, then
     * wraps it in resources/views/layout.php.
     */
    public function renderPage(string $page, array $data): string
    {
        $content = $this->renderTemplate("pages/{$page}", $data);

        return $this->renderTemplate('layout', [...$data, 'content' => $content]);
    }

    private function renderTemplate(string $name, array $data): string
    {
        $vite = $this->vite;
        extract($data);

        ob_start();
        include "{$this->viewsPath}/{$name}.php";

        return ob_get_clean();
    }
}
