<?php
// Công thức món: định lượng nguyên liệu cho 1 phần
class Recipe
{
    const MAX_ROWS = 30;

    public static function ingredients(): array
    {
        return Database::get()->query('SELECT id, name, unit FROM ingredients ORDER BY name')->fetchAll();
    }

    // Toàn bộ món kèm các dòng công thức
    public static function listWithRecipes(): array
    {
        $pdo = Database::get();
        $items = $pdo->query(
            'SELECT m.id, m.name, m.status, m.image, c.name AS category_name
             FROM menu_items m JOIN categories c ON c.id = m.category_id
             ORDER BY c.sort_order, c.id, m.name')->fetchAll();

        $lines = [];
        foreach ($pdo->query('SELECT r.menu_item_id, r.quantity, i.name, i.unit
                              FROM recipes r JOIN ingredients i ON i.id = r.ingredient_id ORDER BY i.name')->fetchAll() as $r) {
            $lines[$r['menu_item_id']][] = $r;
        }
        foreach ($items as &$it) $it['lines'] = $lines[$it['id']] ?? [];
        unset($it);
        return $items;
    }

    public static function forItem(int $itemId): array
    {
        $st = Database::get()->prepare(
            'SELECT r.ingredient_id, r.quantity, i.name, i.unit
             FROM recipes r JOIN ingredients i ON i.id = r.ingredient_id
             WHERE r.menu_item_id = ? ORDER BY i.name');
        $st->execute([$itemId]);
        return $st->fetchAll();
    }

    // Thay toàn bộ công thức của món. $rows = [[ingredient_id, '0.20'], ...]
    public static function save(int $itemId, array $rows): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM recipes WHERE menu_item_id = ?')->execute([$itemId]);
            $ins = $pdo->prepare('INSERT INTO recipes (menu_item_id, ingredient_id, quantity) VALUES (?, ?, ?)');
            foreach ($rows as [$ingredientId, $qty]) $ins->execute([$itemId, $ingredientId, $qty]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Nguyên liệu cần dùng cho cả đơn = định lượng mỗi phần x số phần đã bán
    public static function consumption(int $orderId): array
    {
        $st = Database::get()->prepare(
            'SELECT r.ingredient_id, i.name, i.unit, SUM(r.quantity * oi.quantity) AS need
             FROM order_items oi
             JOIN recipes r ON r.menu_item_id = oi.menu_item_id
             JOIN ingredients i ON i.id = r.ingredient_id
             WHERE oi.order_id = ?
             GROUP BY r.ingredient_id, i.name, i.unit
             ORDER BY i.name');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    // Nguyên liệu đang nằm trong bao nhiêu công thức (trả 0 nếu chưa tạo bảng recipes)
    public static function usedCount(int $ingredientId): int
    {
        try {
            $st = Database::get()->prepare('SELECT COUNT(*) FROM recipes WHERE ingredient_id = ?');
            $st->execute([$ingredientId]);
            return (int)$st->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
