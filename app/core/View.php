<?php
/**
 * View Rendering Engine
 */

namespace App\Core;

class View
{
    public static function render(string $viewPath, array $data = [], ?string $layout = 'customer_layout'): void
    {
        // Extract variables to view scope
        extract($data);
        
        $appConfig = require APP_PATH . '/config/app.php';
        $baseUrl = $appConfig['public_url'];
        $appName = $appConfig['name'];

        // Start capturing view content
        ob_start();
        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "<div class='alert alert-danger'>View file not found: {$viewPath}</div>";
        }
        $content = ob_get_clean();

        // If layout is specified, inject into layout
        if ($layout) {
            $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
                return;
            }
        }

        echo $content;
    }
}
