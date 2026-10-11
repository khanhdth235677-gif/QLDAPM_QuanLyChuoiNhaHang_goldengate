<?php
class DiningTable
{
    public static function forBranch(int $branchId): array
    {
        $st = Database::get()->prepare('SELECT * FROM dining_tables WHERE branch_id = ? ORDER BY LENGTH(table_name), table_name');
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM dining_tables WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function nameExists(int $branchId, string $name, ?int $exceptId = null): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM dining_tables WHERE branch_id = ? AND table_name = ? AND id <> ?');
        $st->execute([$branchId, $name, $exceptId ?? 0]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function create(int $branchId, string $name, int $seats): void
    {
        Database::get()->prepare('INSERT INTO dining_tables (branch_id, table_name, seats) VALUES (?, ?, ?)')
            ->execute([$branchId, $name, $seats]);
    }

    public static function update(int $id, string $name, int $seats, string $status): void
    {
        Database::get()->prepare('UPDATE dining_tables SET table_name = ?, seats = ?, status = ? WHERE id = ?')
            ->execute([$name, $seats, $status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM dining_tables WHERE id = ?')->execute([$id]);
    }

    // Thêm nhanh nhiều bàn: B01, B02, ... bỏ qua tên đã tồn tại. Trả về số bàn thêm được
    public static function bulk(int $branchId, string $prefix, int $count, int $seats): int
    {
        $st = Database::get()->prepare('INSERT IGNORE INTO dining_tables (branch_id, table_name, seats) VALUES (?, ?, ?)');
        $added = 0;
        for ($i = 1; $i <= $count; $i++) {
            $st->execute([$branchId, $prefix . str_pad((string)$i, 2, '0', STR_PAD_LEFT), $seats]);
            $added += $st->rowCount();
        }
        return $added;
    }
}
