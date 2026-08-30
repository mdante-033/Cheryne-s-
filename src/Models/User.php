<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function createPasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $pdo = Database::connection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (id SERIAL PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, token_hash CHAR(64) NOT NULL UNIQUE, expires_at TIMESTAMP NOT NULL, used_at TIMESTAMP NULL, created_at TIMESTAMP NOT NULL DEFAULT NOW())");
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL')->execute(['user_id' => $userId]);
        $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function consumePasswordResetToken(string $tokenHash): ?int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token_hash' => $tokenHash]);
        $token = $stmt->fetch();
        if (!$token) {
            return null;
        }

        $update = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id AND used_at IS NULL');
        $update->execute(['id' => $token['id']]);
        return $update->rowCount() === 1 ? (int) $token['user_id'] : null;
    }

    public static function updatePassword(int $userId, string $password): bool
    {
        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash, failed_login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $userId]);
    }

    public static function create(string $name, string $email, string $password, ?string $phone = null, string $role = 'customer'): array
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role)
             VALUES (:name, :email, :phone, :password_hash, :role)
             RETURNING id, name, email, phone, role, created_at'
        );

        $stmt->execute([
            'name' => $name,
            'email' => strtolower($email),
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        return $stmt->fetch();
    }

    public static function recordFailedLogin(string $email): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 locked_until = CASE
                     WHEN failed_login_attempts + 1 >= 5 THEN NOW() + INTERVAL '15 minutes'
                     ELSE locked_until
                 END
             WHERE email = :email"
        );
        $stmt->execute(['email' => strtolower($email)]);
    }

    public static function resetFailedLogin(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
