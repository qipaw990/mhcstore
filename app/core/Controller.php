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
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
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
        header("Location: {$url}");
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

    protected function getPost(): array
    {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true);
            return is_array($input) ? $input : [];
        }
        return $_POST;
    }

    protected function getQuery(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
}
