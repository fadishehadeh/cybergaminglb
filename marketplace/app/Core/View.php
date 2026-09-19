<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * Renders app/Views/{template}.php (dots or slashes), then wraps it in app/Views/layouts/{layout}.php.
     * The view runs first, in the same scope the layout later runs in: a view can set variables such as
     * $meta or $pageTitle and the layout will see them. The rendered view is available to the layout as $content.
     */
    public static function render(string $template, array $data = [], ?string $layout = 'site'): void
    {
        $file = base_path('app/Views/' . str_replace('.', '/', $template) . '.php');
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $template");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = base_path("app/Views/layouts/$layout.php");
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: $layout");
        }
        require $layoutFile;
    }
}
