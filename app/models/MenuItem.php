<?php
class MenuItem
{
    public static function list(?int $categoryId): array
    {
        $sql = 'SELECT m.*, c.name AS category_name FROM menu_items m JOIN categories c ON c.id = m.category_id';
        $p = [];
        if ($categoryId) { $sql .= ' WHERE m.category_id = ?'; $p[] = $categoryId; }
        $st = Database::get()->prepare($sql . ' ORDER BY c.sort_order, c.id, m.name');
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Món mới tự xuất hiện ở mọi chi nhánh với giá gốc
    public static function create(array $d): int
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO menu_items (category_id, name, description, image, base_price, status)
                           VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$d['category_id'], $d['name'], $d['description'], $d['image'], $d['base_price'], $d['status']]);
            $id    = (int)$pdo->lastInsertId();
            $price = (int)$d['base_price'];
            $pdo->exec("INSERT INTO branch_menu (branch_id, menu_item_id, price, is_available)
                        SELECT id, $id, $price, 1 FROM branches");
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $d, bool $applyAll): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE menu_items SET category_id = ?, name = ?, description = ?, image = ?, base_price = ?, status = ?
                           WHERE id = ?')
                ->execute([$d['category_id'], $d['name'], $d['description'], $d['image'], $d['base_price'], $d['status'], $id]);
            if ($applyAll) {
                $pdo->prepare('UPDATE branch_menu SET price = ? WHERE menu_item_id = ?')->execute([$d['base_price'], $id]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, int $status): void
    {
        Database::get()->prepare('UPDATE menu_items SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function hasOrders(int $id): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?');
        $st->execute([$id]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
    }

    // Giá và tình trạng bán của toàn bộ món tại một chi nhánh
    public static function branchPrices(int $branchId): array
    {
        $st = Database::get()->prepare(
            'SELECT m.id, m.name, m.image, m.status, m.base_price, c.name AS category_name,
                    COALESCE(bm.price, m.base_price) AS price, COALESCE(bm.is_available, 1) AS is_available
             FROM menu_items m
             JOIN categories c ON c.id = m.category_id
             LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
             ORDER BY c.sort_order, c.id, m.name');
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    public static function saveBranchPrices(int $branchId, array $rows): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO branch_menu (branch_id, menu_item_id, price, is_available) VALUES (?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE price = VALUES(price), is_available = VALUES(is_available)');
            foreach ($rows as $itemId => $r) $st->execute([$branchId, $itemId, $r['price'], $r['avail']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
