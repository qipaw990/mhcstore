<?php
/**
 * Authentication Helpers
 */

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_id(): ?int
{
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function auth_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function is_authenticated(): bool
{
    return !empty($_SESSION['user']['id']);
}

function is_admin(): bool
{
    return auth_role() === 'admin';
}

function is_vendor(): bool
{
    return auth_role() === 'vendor';
}

function is_customer(): bool
{
    return auth_role() === 'customer';
}

function is_delivery(): bool
{
    return auth_role() === 'delivery_man';
}

function get_bearer_token(): ?string
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return $_GET['token'] ?? null;
}
