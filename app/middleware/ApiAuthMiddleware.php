<?php
namespace App\Middleware;

use App\Core\Database;

class ApiAuthMiddleware
{
    public function handle(): bool
    {
        $token = get_bearer_token();

        if (!$token) {
            api_error('Unauthorized. API Bearer Token is required.', null, 401);
            return false;
        }

        $user = Database::fetchOne("SELECT * FROM `users` WHERE `api_token` = ? AND `is_active` = 1 LIMIT 1", [$token]);

        if (!$user) {
            api_error('Invalid or expired API token.', null, 401);
            return false;
        }

        $_SESSION['api_user'] = $user;
        $_SESSION['user'] = $user;

        return true;
    }
}
