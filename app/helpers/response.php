<?php
/**
 * Response Helpers
 */

function api_response(bool $success, string $message, $data = null, $errors = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'errors'  => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_success(string $message, $data = null, int $statusCode = 200): void
{
    api_response(true, $message, $data, null, $statusCode);
}

function api_error(string $message, $errors = null, int $statusCode = 400): void
{
    api_response(false, $message, null, $errors, $statusCode);
}
