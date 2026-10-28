<?php
class Branch
{
    public static function all(): array
    {
        return Database::get()->query(
            'SELECT b.*,
                    (SELECT COUNT(*) FROM dining_tables t WHERE t.branch_id = b.id) AS tables_count,
                    (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id) AS users_count
             FROM branches b ORDER BY b.id')->fetchAll();
    }

    public static function activeList(): array
    {
        return Database::get()->query('SELECT id, name FROM branches WHERE status = 1 ORDER BY id')->fetchAll();
    }

    public static function activeCount(): int
    {
        return (int)Database::get()->query('SELECT COUNT(*) FROM branches WHERE status = 1')->fetchColumn();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM branches WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Chi nhánh mới tự có đủ toàn bộ món trong thực đơn với giá gốc
    public static function create(array $d): int
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO branches (name, address, phone, status) VALUES (?, ?, ?, 1)')
                ->execute([$d['name'], $d['address'], $d['phone']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->exec("INSERT INTO branch_menu (branch_id, menu_item_id, price, is_available)
                        SELECT $id, id, base_price, 1 FROM menu_items");
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $d): void
    {
        Database::get()->prepare('UPDATE branches SET name = ?, address = ?, phone = ? WHERE id = ?')
            ->execute([$d['name'], $d['address'], $d['phone'], $id]);
    }

    public static function setStatus(int $id, int $status): void
    {
        Database::get()->prepare('UPDATE branches SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function deleteOrDeactivate(int $id): bool
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id FROM branches WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            if (!$st->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }

            $st = $pdo->prepare(
                'SELECT
                   EXISTS(SELECT 1 FROM users WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM employees WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM dining_tables WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM reservations WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM orders WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM inventory WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM stock_movements WHERE branch_id = ?) OR
                   EXISTS(SELECT 1 FROM chat_conversations WHERE branch_id = ?)'
            );
            $st->execute(array_fill(0, 8, $id));
            $hasHistory = (bool)$st->fetchColumn();

            if ($hasHistory) {
                $pdo->prepare('UPDATE branches SET status = 0 WHERE id = ?')->execute([$id]);
            } else {
                $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([$id]);
            }
            $pdo->commit();
            return !$hasHistory;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
