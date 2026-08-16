<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'role', 'name', 'email', 'phone', 'password', 'avatar',
        'is_active', 'remember_token', 'api_token', 'email_verified_at'
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->firstWhere('email', $email);
    }

    public function findByPhone(string $phone): ?array
    {
        return $this->firstWhere('phone', $phone);
    }

    public function findByToken(string $token): ?array
    {
        return $this->firstWhere('api_token', $token);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
