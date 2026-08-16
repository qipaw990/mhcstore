<?php
namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\Wallet;
use Exception;

class AuthService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(string $emailOrPhone, string $password): array
    {
        $sql = "SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `is_active` = 1 LIMIT 1";
        $user = Database::fetchOne($sql, [$emailOrPhone, $emailOrPhone]);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Email/No. HP atau password salah.");
        }

        // Generate or refresh API token
        $apiToken = bin2hex(random_bytes(32));
        $this->userModel->update($user['id'], ['api_token' => $apiToken]);
        $user['api_token'] = $apiToken;

        // Set session
        $_SESSION['user'] = $user;

        return $user;
    }

    public function registerCustomer(array $data): array
    {
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1", [$data['email'], $data['phone']]);
        if ($existing) {
            throw new Exception("Email atau Nomor HP sudah terdaftar.");
        }

        $apiToken = bin2hex(random_bytes(32));
        $userId = $this->userModel->create([
            'role'      => 'customer',
            'name'      => sanitize($data['name']),
            'email'     => sanitize($data['email']),
            'phone'     => sanitize($data['phone']),
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'avatar'    => 'assets/images/users/default.png',
            'is_active' => 1,
            'api_token' => $apiToken
        ]);

        // Init wallet with welcome bonus
        (new Wallet())->create([
            'user_id'          => $userId,
            'user_type'        => 'customer',
            'balance'          => 25000.00, // Welcome gift Rp 25.000
            'total_earned'     => 25000.00,
            'total_withdrawn'  => 0.00
        ]);

        $user = $this->userModel->find($userId);
        $_SESSION['user'] = $user;

        return $user;
    }
}
