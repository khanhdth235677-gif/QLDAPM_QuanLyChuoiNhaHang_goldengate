<?php
// Chống dò mật khẩu: khóa tạm khi đăng nhập sai nhiều lần
class LoginAttempt
{
    const MAX_PER_USER = 5;    // sai 5 lần với cùng tên đăng nhập + cùng IP
    const MAX_PER_IP   = 20;   // hoặc 20 lần từ cùng một IP
    const WINDOW_MIN   = 15;   // trong vòng 15 phút

    public static function isBlocked(string $username, string $ip): bool
    {
        $pdo = Database::get();
        $win = (int)self::WINDOW_MIN;

        $st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts
                             WHERE username = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL $win MINUTE)");
        $st->execute([$username, $ip]);
        if ((int)$st->fetchColumn() >= self::MAX_PER_USER) return true;

        $st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts
                             WHERE ip = ? AND attempted_at > (NOW() - INTERVAL $win MINUTE)");
        $st->execute([$ip]);
        return (int)$st->fetchColumn() >= self::MAX_PER_IP;
    }

    public static function record(string $username, string $ip): void
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO login_attempts (username, ip) VALUES (?, ?)')->execute([$username, $ip]);
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clear(string $username, string $ip): void
    {
        Database::get()->prepare('DELETE FROM login_attempts WHERE username = ? AND ip = ?')->execute([$username, $ip]);
    }
}
