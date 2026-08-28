<?php
/**
 * Authentication Helpers
 */

function auth_user(): ?array
{
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    $uid = (int)($_REQUEST['user_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    if ($uid > 0) {
        $u = \App\Core\Database::fetchOne("SELECT id, name, email, phone, role, avatar FROM users WHERE id = ? LIMIT 1", [$uid]);
        if ($u) return $u;
    }
    return null;
}

function auth_id(): ?int
{
    if (isset($_SESSION['user']['id'])) {
        return (int)$_SESSION['user']['id'];
    }
    $uid = (int)($_REQUEST['user_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? $_SERVER['HTTP_X_USER_ID'] ?? 0);
    if ($uid > 0) {
        return $uid;
    }
    $token = get_bearer_token();
    if ($token) {
        $t = \App\Core\Database::fetchOne("SELECT user_id FROM personal_access_tokens WHERE token = ? LIMIT 1", [$token]);
        if ($t) return (int)$t['user_id'];
    }
    return null;
}

function auth_role(): ?string
{
    if (isset($_SESSION['user']['role'])) {
        return $_SESSION['user']['role'];
    }
    $role = $_REQUEST['user_role'] ?? $_GET['user_role'] ?? $_POST['user_role'] ?? null;
    if ($role) return $role;

    $uid = auth_id();
    if ($uid > 0) {
        $u = \App\Core\Database::fetchOne("SELECT role FROM users WHERE id = ? LIMIT 1", [$uid]);
        if ($u) return $u['role'];
    }
    return null;
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
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return $_GET['token'] ?? null;
}
