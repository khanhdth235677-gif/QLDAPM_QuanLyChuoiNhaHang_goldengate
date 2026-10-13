<?php
class Employee
{
    // $branchId = null nghĩa là tất cả chi nhánh (chỉ admin dùng)
    private static function filter(?int $branchId, string $q, string $status): array
    {
        $where = ' WHERE 1=1';
        $p = [];
        if ($branchId)            { $where .= ' AND e.branch_id = ?'; $p[] = $branchId; }
        if ($status === 'active')   $where .= ' AND e.status = 1';
        if ($status === 'inactive') $where .= ' AND e.status = 0';
        if ($q !== '')            { $where .= ' AND (e.full_name LIKE ? OR e.phone LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        return [$where, $p];
    }

    public static function list(?int $branchId, string $q, string $status): array
    {
        [$where, $p] = self::filter($branchId, $q, $status);
        $st = Database::get()->prepare(
            'SELECT e.*, b.name AS branch_name, u.username
             FROM employees e
             JOIN branches b ON b.id = e.branch_id
             LEFT JOIN users u ON u.id = e.user_id' . $where . ' ORDER BY b.id, e.status DESC, e.full_name');
        $st->execute($p);
        return $st->fetchAll();
    }

    // Số nhân viên đang làm và tổng lương tháng của họ
    public static function stats(?int $branchId): array
    {
        [$where, $p] = self::filter($branchId, '', 'active');
        $st = Database::get()->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(e.salary), 0) AS total FROM employees e' . $where);
        $st->execute($p);
        return $st->fetch();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM employees WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Tài khoản có thể gắn cho nhân viên: chưa gắn với nhân viên khác
    public static function linkableUsers(?int $branchId, ?int $exceptEmployeeId, bool $manageableOnly): array
    {
        $sql = 'SELECT u.id, u.username, u.full_name, u.branch_id, r.name AS role_name
                FROM users u JOIN roles r ON r.id = u.role_id
                WHERE u.branch_id IS NOT NULL
                  AND NOT EXISTS (SELECT 1 FROM employees e WHERE e.user_id = u.id AND e.id <> ?)';
        $p = [$exceptEmployeeId ?? 0];
        if ($branchId) { $sql .= ' AND u.branch_id = ?'; $p[] = $branchId; }
        if ($manageableOnly) {
            $in = implode(',', array_fill(0, count(User::MANAGER_ROLES), '?'));
            $sql .= " AND r.code IN ($in)";
            $p = array_merge($p, User::MANAGER_ROLES);
        }
        $st = Database::get()->prepare($sql . ' ORDER BY u.username');
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function userLinked(int $userId, ?int $exceptEmployeeId): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM employees WHERE user_id = ? AND id <> ?');
        $st->execute([$userId, $exceptEmployeeId ?? 0]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function create(array $d): int
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO employees (user_id, branch_id, full_name, phone, position, salary, hired_date, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
            ->execute([$d['user_id'], $d['branch_id'], $d['full_name'], $d['phone'], $d['position'], $d['salary'], $d['hired_date']]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::get()->prepare('UPDATE employees SET user_id = ?, branch_id = ?, full_name = ?, phone = ?, position = ?, salary = ?, hired_date = ?
                                  WHERE id = ?')
            ->execute([$d['user_id'], $d['branch_id'], $d['full_name'], $d['phone'], $d['position'], $d['salary'], $d['hired_date'], $id]);
    }

    public static function setStatus(int $id, int $status): void
    {
        Database::get()->prepare('UPDATE employees SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
    }
}
