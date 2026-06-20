<?php

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_WINDOW_MIN = 15;

    public static function attempt(string $email, string $password): array
    {
        $db = Database::connection();

        if (self::isRateLimited($email)) {
            return ['ok' => false, 'error' => 'Trop de tentatives. Réessayez dans quelques minutes.'];
        }

        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        self::logAttempt($email);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Identifiants invalides.'];
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['ok' => false, 'error' => 'Compte temporairement verrouillé.'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];

        return ['ok' => true, 'user' => $user];
    }

    private static function isRateLimited(string $email): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR email = ?) AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([Security::clientIp(), $email, self::LOCKOUT_WINDOW_MIN]);
        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    private static function logAttempt(string $email): void
    {
        Database::connection()
            ->prepare('INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)')
            ->execute([Security::clientIp(), $email]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([self::id()]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            echo 'Accès refusé.';
            exit;
        }
    }
}
