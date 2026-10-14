<?php
require_once __DIR__ . '/Recipe.php';
// Kho nguyên liệu: tồn kho theo chi nhánh, phiếu nhập/xuất/kiểm kê
class Inventory
{
    const TYPES = ['in' => 'Nhập kho', 'out' => 'Xuất kho', 'adjust' => 'Kiểm kê'];

    // Tồn kho của một chi nhánh. Nguyên liệu chưa có dòng tồn được tính là 0
    public static function stock(int $branchId, bool $onlyLow = false): array
    {
        $sql = 'SELECT i.id, i.name, i.unit,
                       COALESCE(v.quantity, 0) AS quantity, COALESCE(v.min_quantity, 0) AS min_quantity
                FROM ingredients i
                LEFT JOIN inventory v ON v.ingredient_id = i.id AND v.branch_id = ?';
        if ($onlyLow) $sql .= ' WHERE COALESCE(v.min_quantity, 0) > 0 AND COALESCE(v.quantity, 0) <= COALESCE(v.min_quantity, 0)';
        $st = Database::get()->prepare($sql . ' ORDER BY i.name');
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    // Số nguyên liệu đã đặt mức tối thiểu mà tồn kho chạm hoặc thấp hơn mức đó
    public static function lowCount(?int $branchId): int
    {
        $sql = 'SELECT COUNT(*) FROM inventory v JOIN branches b ON b.id = v.branch_id
                WHERE b.status = 1 AND v.min_quantity > 0 AND v.quantity <= v.min_quantity';
        $p = [];
        if ($branchId) { $sql .= ' AND v.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function setMin(int $branchId, int $ingredientId, string $min): void
    {
        Database::get()->prepare(
            'INSERT INTO inventory (branch_id, ingredient_id, quantity, min_quantity) VALUES (?, ?, 0, ?)
             ON DUPLICATE KEY UPDATE min_quantity = VALUES(min_quantity)')
            ->execute([$branchId, $ingredientId, $min]);
    }

    // Nhập, xuất hoặc kiểm kê. Trả về thông báo lỗi hoặc null nếu thành công.
    // $qty là chuỗi số thập phân đã kiểm tra: nhập/xuất = số lượng, kiểm kê = số lượng thực tế đếm được
    public static function move(int $branchId, int $ingredientId, string $type, string $qty, ?string $note, int $userId): ?string
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT IGNORE INTO inventory (branch_id, ingredient_id, quantity, min_quantity) VALUES (?, ?, 0, 0)')
                ->execute([$branchId, $ingredientId]);

            $st = $pdo->prepare('SELECT quantity FROM inventory WHERE branch_id = ? AND ingredient_id = ? FOR UPDATE');
            $st->execute([$branchId, $ingredientId]);
            $current = (string)$st->fetchColumn();

            if ($type === 'in') {
                $pdo->prepare('UPDATE inventory SET quantity = quantity + ? WHERE branch_id = ? AND ingredient_id = ?')
                    ->execute([$qty, $branchId, $ingredientId]);
                $logQty = $qty;
            } elseif ($type === 'out') {
                $up = $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE branch_id = ? AND ingredient_id = ? AND quantity >= ?');
                $up->execute([$qty, $branchId, $ingredientId, $qty]);
                if ($up->rowCount() === 0) {
                    $pdo->rollBack();
                    return 'Tồn kho không đủ để xuất (hiện còn ' . qty_fmt($current) . ').';
                }
                $logQty = $qty;
            } else {   // adjust: đặt tồn kho bằng số thực tế, ghi lại chênh lệch
                $d = $pdo->prepare('SELECT (? - quantity) FROM inventory WHERE branch_id = ? AND ingredient_id = ?');
                $d->execute([$qty, $branchId, $ingredientId]);
                $logQty = (string)$d->fetchColumn();
                if (abs((float)$logQty) < 0.005) {
                    $pdo->rollBack();
                    return 'Số thực tế trùng với tồn kho hiện tại, không có gì thay đổi.';
                }
                $pdo->prepare('UPDATE inventory SET quantity = ? WHERE branch_id = ? AND ingredient_id = ?')
                    ->execute([$qty, $branchId, $ingredientId]);
                $auto = 'Kiểm kê: từ ' . qty_fmt($current) . ' còn ' . qty_fmt($qty);
                $note = $note !== null && $note !== '' ? $auto . '. ' . $note : $auto;
                $note = mb_substr($note, 0, 255);
            }

            $pdo->prepare('INSERT INTO stock_movements (branch_id, ingredient_id, type, quantity, note, created_by) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$branchId, $ingredientId, $type, $logQty, $note, $userId]);
            $pdo->commit();
            return null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function history(?int $branchId, ?int $ingredientId, string $type, string $from, string $to, int $limit = 300): array
    {
        $sql = 'SELECT m.*, i.name AS ingredient_name, i.unit, b.name AS branch_name, u.full_name AS user_name
                FROM stock_movements m
                JOIN ingredients i ON i.id = m.ingredient_id
                JOIN branches b ON b.id = m.branch_id
                LEFT JOIN users u ON u.id = m.created_by
                WHERE DATE(m.created_at) BETWEEN ? AND ?';
        $p = [$from, $to];
        if ($branchId)     { $sql .= ' AND m.branch_id = ?';     $p[] = $branchId; }
        if ($ingredientId) { $sql .= ' AND m.ingredient_id = ?'; $p[] = $ingredientId; }
        if (isset(self::TYPES[$type])) { $sql .= ' AND m.type = ?'; $p[] = $type; }
        $st = Database::get()->prepare($sql . ' ORDER BY m.id DESC LIMIT ' . (int)$limit);
        $st->execute($p);
        return $st->fetchAll();
    }

    // ---------- Danh mục nguyên liệu (dùng chung toàn chuỗi) ----------
    public static function ingredients(): array
    {
        return Database::get()->query(
            'SELECT i.*, (SELECT COUNT(*) FROM stock_movements m WHERE m.ingredient_id = i.id) AS moves,
                    (SELECT COALESCE(SUM(v.quantity), 0) FROM inventory v WHERE v.ingredient_id = i.id) AS total_stock
             FROM ingredients i ORDER BY i.name')->fetchAll();
    }

    public static function findIngredient(int $id): array|false
    {
        $st = Database::get()->prepare(
            'SELECT i.*, (SELECT COUNT(*) FROM stock_movements m WHERE m.ingredient_id = i.id) AS moves,
                    (SELECT COALESCE(SUM(v.quantity), 0) FROM inventory v WHERE v.ingredient_id = i.id) AS total_stock
             FROM ingredients i WHERE i.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function nameExists(string $name, ?int $exceptId): bool
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM ingredients WHERE name = ? AND id <> ?');
        $st->execute([$name, $exceptId ?? 0]);
        return (int)$st->fetchColumn() > 0;
    }

    public static function createIngredient(string $name, string $unit): void
    {
        Database::get()->prepare('INSERT INTO ingredients (name, unit) VALUES (?, ?)')->execute([$name, $unit]);
    }

    public static function updateIngredient(int $id, string $name, string $unit): void
    {
        Database::get()->prepare('UPDATE ingredients SET name = ?, unit = ? WHERE id = ?')->execute([$name, $unit, $id]);
    }

    public static function deleteIngredient(int $id): void
    {
        Database::get()->prepare('DELETE FROM ingredients WHERE id = ?')->execute([$id]);
    }

    // Tự trừ kho theo công thức sau khi đơn đã thanh toán. Không bao giờ chặn việc thanh toán:
    // nếu kho không đủ thì trừ hết phần đang có và báo thiếu. Trả về cảnh báo hoặc null nếu ổn.
    public static function consumeForOrder(int $orderId, int $branchId, int $userId): ?string
    {
        $marker = 'Tự trừ kho khi thanh toán HD' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
        $pdo = Database::get();
        try {
            $needs = Recipe::consumption($orderId);
            if (!$needs) return null;                       // không món nào có công thức

            $pdo->beginTransaction();
            $chk = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE branch_id = ? AND type = 'out' AND note LIKE ?");
            $chk->execute([$branchId, $marker . '%']);
            if ((int)$chk->fetchColumn() > 0) { $pdo->rollBack(); return null; }   // đã trừ rồi

            $short = [];
            foreach ($needs as $n) {
                $ing  = (int)$n['ingredient_id'];
                $need = (string)$n['need'];

                $pdo->prepare('INSERT IGNORE INTO inventory (branch_id, ingredient_id, quantity, min_quantity) VALUES (?, ?, 0, 0)')
                    ->execute([$branchId, $ing]);
                $lock = $pdo->prepare('SELECT quantity FROM inventory WHERE branch_id = ? AND ingredient_id = ? FOR UPDATE');
                $lock->execute([$branchId, $ing]);
                $lock->fetchColumn();

                $t = $pdo->prepare('SELECT LEAST(CAST(? AS DECIMAL(14,2)), quantity) FROM inventory WHERE branch_id = ? AND ingredient_id = ?');
                $t->execute([$need, $branchId, $ing]);
                $take = (string)$t->fetchColumn();
                $lack = (float)$need - (float)$take;

                if ((float)$take > 0) {
                    $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE branch_id = ? AND ingredient_id = ?')
                        ->execute([$take, $branchId, $ing]);
                    $note = $marker . ($lack > 0.004 ? ' (thiếu ' . qty_fmt($lack) . ' ' . $n['unit'] . ' so với công thức)' : '');
                    $pdo->prepare("INSERT INTO stock_movements (branch_id, ingredient_id, type, quantity, note, created_by) VALUES (?, ?, 'out', ?, ?, ?)")
                        ->execute([$branchId, $ing, $take, $note, $userId]);
                }
                if ($lack > 0.004) $short[] = $n['name'] . ' thiếu ' . qty_fmt($lack) . ' ' . $n['unit'];
            }
            $pdo->commit();
            return $short ? 'Đã thanh toán, nhưng kho không đủ theo công thức: ' . implode('; ', $short) . '. Hãy kiểm kê và nhập kho.' : null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return 'Đã thanh toán nhưng chưa tự trừ được kho. Hãy kiểm tra đã chạy migration_giai_doan_6b.sql chưa'
                 . (defined('DEBUG') && DEBUG ? ' (' . $e->getMessage() . ')' : '') . '.';
        }
    }
}
