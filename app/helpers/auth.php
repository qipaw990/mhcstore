<?php
/**
 * Authentication Helpers
 */

function auth_user(): ?array
{
    $uid = auth_id();
    if ($uid > 0) {
        $u = \App\Core\Database::fetchOne("SELECT id, name, email, phone, role, avatar, api_token FROM users WHERE id = ? LIMIT 1", [$uid]);
        if ($u) {
            $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $u);
            return $_SESSION['user'];
        }
    }
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function auth_id(): ?int
{
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] > 0) {
        return (int)$_SESSION['user']['id'];
    }

    $token = get_bearer_token();
    if (!empty($token)) {
        // 1. Direct query to users table via api_token
        $u = \App\Core\Database::fetchOne("SELECT id, name, email, phone, role, avatar, api_token FROM users WHERE api_token = ? LIMIT 1", [$token]);
        if ($u) {
            $_SESSION['user'] = $u;
            return (int)$u['id'];
        }

        // 2. Fallback to personal_access_tokens
        try {
            $t = \App\Core\Database::fetchOne("SELECT user_id FROM personal_access_tokens WHERE token = ? LIMIT 1", [$token]);
            if ($t && !empty($t['user_id'])) {
                $u2 = \App\Core\Database::fetchOne("SELECT id, name, email, phone, role, avatar, api_token FROM users WHERE id = ? LIMIT 1", [$t['user_id']]);
                if ($u2) {
                    $_SESSION['user'] = $u2;
                    return (int)$u2['id'];
                }
                return (int)$t['user_id'];
            }
        } catch (\Throwable $e) {}
    }

    // 3. Fallback to X-User-ID header if sent by mobile app
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $normalizedHeaders = [];
    foreach ($headers as $k => $v) {
        $normalizedHeaders[strtolower($k)] = $v;
    }
    $userIdHeader = $normalizedHeaders['x-user-id'] ?? $_SERVER['HTTP_X_USER_ID'] ?? $_SERVER['REDIRECT_HTTP_X_USER_ID'] ?? null;
    if (!empty($userIdHeader) && is_numeric($userIdHeader)) {
        $u3 = \App\Core\Database::fetchOne("SELECT id, name, email, phone, role, avatar, api_token FROM users WHERE id = ? LIMIT 1", [(int)$userIdHeader]);
        if ($u3) {
            $_SESSION['user'] = $u3;
            return (int)$u3['id'];
        }
    }

    return null;
}

function auth_role(): ?string
{
    if (isset($_SESSION['user']['role']) && !empty($_SESSION['user']['role'])) {
        return $_SESSION['user']['role'];
    }

    $uid = auth_id();
    if ($uid > 0) {
        $u = \App\Core\Database::fetchOne("SELECT role FROM users WHERE id = ? LIMIT 1", [$uid]);
        if ($u) {
            if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                $_SESSION['user']['role'] = $u['role'];
            }
            return $u['role'];
        }
    }
    return null;
}

function is_authenticated(): bool
{
    return auth_id() !== null;
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
    $normalizedHeaders = [];
    foreach ($headers as $k => $v) {
        $normalizedHeaders[strtolower($k)] = $v;
    }

    $authHeader = $normalizedHeaders['authorization']
               ?? $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? $_SERVER['Authorization']
               ?? '';

    if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    // Direct X-Api-Token header or parameter
    return $normalizedHeaders['x-api-token']
        ?? $_SERVER['HTTP_X_API_TOKEN']
        ?? $_SERVER['REDIRECT_HTTP_X_API_TOKEN']
        ?? $_REQUEST['api_token']
        ?? $_REQUEST['token']
        ?? $_GET['token']
        ?? $_POST['token']
        ?? null;
}
