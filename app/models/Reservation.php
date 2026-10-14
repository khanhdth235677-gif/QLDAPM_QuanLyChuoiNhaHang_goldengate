<?php
class Reservation
{
    const DURATION_MIN = 120;   // mỗi lượt đặt giữ bàn 2 tiếng, hai lượt cách nhau dưới 2 tiếng coi là trùng

    // $filter: active (chờ + đã xác nhận), done, cancelled, all
    public static function list(?int $branchId, string $date, string $filter): array
    {
        $sql = 'SELECT r.*, t.table_name, t.seats, b.name AS branch_name
                FROM reservations r
                LEFT JOIN dining_tables t ON t.id = r.table_id
                JOIN branches b ON b.id = r.branch_id
                WHERE DATE(r.reserved_at) = ?';
        $p = [$date];
        if ($branchId) { $sql .= ' AND r.branch_id = ?'; $p[] = $branchId; }
        if ($filter === 'active')         $sql .= " AND r.status IN ('pending', 'confirmed')";
        elseif ($filter === 'done')       $sql .= " AND r.status = 'done'";
        elseif ($filter === 'cancelled')  $sql .= " AND r.status = 'cancelled'";
        $st = Database::get()->prepare($sql . ' ORDER BY r.reserved_at');
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare(
            'SELECT r.*, t.table_name, b.name AS branch_name
             FROM reservations r
             LEFT JOIN dining_tables t ON t.id = r.table_id
             JOIN branches b ON b.id = r.branch_id
             WHERE r.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Bàn của một chi nhánh, hoặc của mọi chi nhánh đang hoạt động (null)
    public static function tablesFor(?int $branchId): array
    {
        $sql = 'SELECT t.id, t.branch_id, t.table_name, t.seats
                FROM dining_tables t JOIN branches b ON b.id = t.branch_id WHERE b.status = 1';
        $p = [];
        if ($branchId) { $sql .= ' AND t.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql . ' ORDER BY t.branch_id, LENGTH(t.table_name), t.table_name');
        $st->execute($p);
        return $st->fetchAll();
    }

    // Bàn đã có lượt đặt khác cách thời điểm này dưới DURATION_MIN phút?
    public static function hasConflict(int $tableId, string $at, ?int $exceptId): bool
    {
        $st = Database::get()->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE table_id = ? AND status IN ('pending', 'confirmed') AND id <> ?
               AND ABS(TIMESTAMPDIFF(MINUTE, reserved_at, ?)) < " . (int)self::DURATION_MIN);
        $st->execute([$tableId, $exceptId ?? 0, $at]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function create(array $d): int
    {
        $pdo = Database::get();
        $pdo->prepare("INSERT INTO reservations (branch_id, table_id, customer_name, customer_phone, guests, reserved_at, note, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')")
            ->execute([$d['branch_id'], $d['table_id'], $d['customer_name'], $d['customer_phone'], $d['guests'], $d['reserved_at'], $d['note']]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::get()->prepare('UPDATE reservations SET branch_id = ?, table_id = ?, customer_name = ?, customer_phone = ?,
                                  guests = ?, reserved_at = ?, note = ? WHERE id = ?')
            ->execute([$d['branch_id'], $d['table_id'], $d['customer_name'], $d['customer_phone'], $d['guests'], $d['reserved_at'], $d['note'], $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::get()->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function markArrived(int $id, int $tableId): void
    {
        Database::get()->prepare("UPDATE reservations SET status = 'done', table_id = ? WHERE id = ?")->execute([$tableId, $id]);
    }
}
