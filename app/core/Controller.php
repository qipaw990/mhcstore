<?php
/**
 * Base Controller Class
 */

namespace App\Core;

abstract class Controller
{
    protected function view(string $viewPath, array $data = [], ?string $layout = 'customer_layout'): void
    {
        View::render($viewPath, $data, $layout);
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        // Release session lock before responding to enable concurrent, non-blocking polling
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (ob_get_length()) {
            @ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; script-src * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; script-src-elem * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; style-src * 'unsafe-inline' https: http:; style-src-elem * 'unsafe-inline' https: http:; img-src * data: blob: https: http:; media-src * data: blob: mediastream: https: http:; connect-src * https: http: ws: wss:; font-src * data: https: http:; frame-src *; child-src * blob:; worker-src * blob:;");
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function successResponse(string $message = 'Success', $data = null, int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $statusCode);
    }

    protected function errorResponse(string $message = 'Error', $errors = null, int $statusCode = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], $statusCode);
    }

    protected function redirect(string $url): void
    {
        // If URL is already absolute (starts with http:// or https://), use as-is
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            header("Location: {$url}");
            exit;
        }

        // Build absolute URL using public_url from config
        $appConfig = require APP_PATH . '/config/app.php';
        $base = rtrim($appConfig['public_url'], '/');

        // Strip any leading slash from the relative path
        $path = ltrim($url, '/');

        header("Location: {$base}/{$path}");
        exit;
    }

    protected function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function isAuth(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    protected function getPost(?string $key = null, $default = null)
    {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = is_array($input) ? $input : [];
        } else {
            $data = $_POST;
        }

        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    protected function isJsonRequest(): bool
    {
        return (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));
    }

    protected function getQuery(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
}
