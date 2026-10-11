<?php
class User
{
    // Quản lý chi nhánh chỉ được quản lý các vai trò này, trong chi nhánh của mình
    const MANAGER_ROLES = ['cashier', 'waiter', 'kitchen'];

    private const BASE = 'SELECT u.*, r.code AS role_code, r.name AS role_name, b.name AS branch_name
                          FROM users u
                          JOIN roles r ON r.id = u.role_id
                          LEFT JOIN branches b ON b.id = u.branch_id';

    public static function findByUsername(string $username): array|false
    {
        $st = Database::get()->prepare(self::BASE . ' WHERE u.username = ? LIMIT 1');
        $st->execute([$username]);
        return $st->fetch();
    }

    public static function findById(int $id): array|false
    {
        $st = Database::get()->prepare(self::BASE . ' WHERE u.id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function touchLogin(int $id): void
    {
        Database::get()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$id]);
    }

    // ----- Danh sách và phạm vi quản lý theo người đang đăng nhập -----
    public static function listFor(array $actor): array
    {
        $sql = self::BASE;
        $params = [];
        if ($actor['role'] !== 'admin') {
            $in = implode(',', array_fill(0, count(self::MANAGER_ROLES), '?'));
            $sql .= " WHERE u.branch_id = ? AND r.code IN ($in)";
            $params = array_merge([$actor['branch_id']], self::MANAGER_ROLES);
        }
        $st = Database::get()->prepare($sql . ' ORDER BY r.id, u.username');
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function rolesFor(array $actor): array
    {
        $pdo = Database::get();
        if ($actor['role'] === 'admin') {
            return $pdo->query('SELECT id, code, name FROM roles ORDER BY id')->fetchAll();
        }
        $in = implode(',', array_fill(0, count(self::MANAGER_ROLES), '?'));
        $st = $pdo->prepare("SELECT id, code, name FROM roles WHERE code IN ($in) ORDER BY id");
        $st->execute(self::MANAGER_ROLES);
        return $st->fetchAll();
    }

    public static function branchesFor(array $actor): array
    {
        $pdo = Database::get();
        if ($actor['role'] === 'admin') {
            return $pdo->query('SELECT id, name FROM branches WHERE status = 1 ORDER BY id')->fetchAll();
        }
        $st = $pdo->prepare('SELECT id, name FROM branches WHERE id = ?');
        $st->execute([$actor['branch_id']]);
        return $st->fetchAll();
    }

    public static function canManage(array $actor, array $target): bool
    {
        if ($actor['role'] === 'admin') return true;
        if ($actor['role'] === 'manager') {
            return $actor['branch_id'] !== null
                && (int)$target['branch_id'] === (int)$actor['branch_id']
                && in_array($target['role_code'], self::MANAGER_ROLES, true);
        }
        return false;
    }

    // ----- Ghi dữ liệu -----
    public static function usernameExists(string $username): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $st->execute([$username]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function create(array $d): int
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO users (role_id, branch_id, username, password_hash, full_name, status, must_change_password)
                       VALUES (?, ?, ?, ?, ?, 1, 1)')
            ->execute([$d['role_id'], $d['branch_id'], $d['username'], password_hash($d['password'], PASSWORD_DEFAULT), $d['full_name']]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::get()->prepare('UPDATE users SET role_id = ?, branch_id = ?, full_name = ? WHERE id = ?')
            ->execute([$d['role_id'], $d['branch_id'], $d['full_name'], $id]);
    }

    public static function setStatus(int $id, int $status): void
    {
        Database::get()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function setPassword(int $id, string $plain, bool $mustChange): void
    {
        Database::get()->prepare('UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?')
            ->execute([password_hash($plain, PASSWORD_DEFAULT), $mustChange ? 1 : 0, $id]);
    }
}
