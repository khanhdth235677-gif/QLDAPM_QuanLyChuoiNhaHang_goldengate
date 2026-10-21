<?php
class Customer
{
    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, created_at FROM customers WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function findByPhone(string $phone): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, created_at FROM customers WHERE phone = ?');
        $st->execute([$phone]);
        return $st->fetch();
    }

    public static function phoneTaken(string $phone, ?int $exceptId): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM customers WHERE phone = ? AND id <> ?');
        $st->execute([$phone, $exceptId ?? 0]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function update(int $id, string $name, string $phone): void
    {
        Database::get()->prepare('UPDATE customers SET full_name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $id]);
    }

    // Tìm khách theo số điện thoại (đã chuẩn hóa), chưa có thì tạo mới. Không ghi đè tên của khách cũ
    public static function findOrCreate(string $name, string $phone): int
    {
        $pdo = Database::get();
        $st = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
        $st->execute([$phone]);
        $id = $st->fetchColumn();
        if ($id) return (int)$id;

        try {
            $pdo->prepare('INSERT INTO customers (full_name, phone) VALUES (?, ?)')->execute([$name, $phone]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {            // hai người cùng lúc tạo một số điện thoại
            $st->execute([$phone]);
            $id = $st->fetchColumn();
            if ($id) return (int)$id;
            throw $e;
        }
    }
}
