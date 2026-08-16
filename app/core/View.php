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

        if (!headers_sent()) {
            header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; script-src * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; script-src-elem * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; style-src * 'unsafe-inline' https: http:; style-src-elem * 'unsafe-inline' https: http:; img-src * data: blob: https: http:; connect-src * https: http: ws: wss:; font-src * data: https: http:; frame-src *; child-src * blob:; worker-src * blob:;");
        }

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
