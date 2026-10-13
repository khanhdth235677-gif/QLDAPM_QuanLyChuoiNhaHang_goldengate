<?php
// Đơn hàng (hóa đơn đang mở) và các dòng món trong đơn
class Order
{
    const MAX_QTY = 99;

    // ---------- Đơn ----------
    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare(
            'SELECT o.*, t.table_name, b.name AS branch_name, u.full_name AS created_by_name,
                    TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes
             FROM orders o
             LEFT JOIN dining_tables t ON t.id = o.table_id
             JOIN branches b ON b.id = o.branch_id
             JOIN users u ON u.id = o.created_by
             WHERE o.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Mở bàn: tạo đơn mới và chuyển bàn sang "đang dùng". Bàn đã có đơn mở thì trả về đơn đó
    public static function open(int $tableId, int $userId): int
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, branch_id FROM dining_tables WHERE id = ? FOR UPDATE');
            $st->execute([$tableId]);
            $t = $st->fetch();
            if (!$t) throw new RuntimeException('Bàn không tồn tại.');

            $st = $pdo->prepare("SELECT id FROM orders WHERE table_id = ? AND status = 'open' LIMIT 1");
            $st->execute([$tableId]);
            $existing = $st->fetchColumn();
            if ($existing) { $pdo->commit(); return (int)$existing; }

            $pdo->prepare("INSERT INTO orders (branch_id, table_id, created_by, status) VALUES (?, ?, ?, 'open')")
                ->execute([$t['branch_id'], $tableId, $userId]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE dining_tables SET status = 'occupied' WHERE id = ?")->execute([$tableId]);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function cancel(int $orderId): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("UPDATE orders SET status = 'cancelled', closed_at = NOW() WHERE id = ? AND status = 'open'");
            $st->execute([$orderId]);
            if ($st->rowCount() > 0) {
                $pdo->prepare("UPDATE dining_tables SET status = 'empty'
                               WHERE id = (SELECT table_id FROM (SELECT table_id FROM orders WHERE id = ?) x)")
                    ->execute([$orderId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Chuyển đơn sang bàn trống khác. Trả về thông báo lỗi hoặc null nếu thành công
    public static function moveTable(int $orderId, int $toTableId): ?string
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $o = $pdo->prepare("SELECT id, branch_id, table_id FROM orders WHERE id = ? AND status = 'open' FOR UPDATE");
            $o->execute([$orderId]);
            $order = $o->fetch();
            if (!$order) { $pdo->rollBack(); return 'Đơn không còn mở.'; }

            $t = $pdo->prepare('SELECT id, branch_id, status FROM dining_tables WHERE id = ? FOR UPDATE');
            $t->execute([$toTableId]);
            $to = $t->fetch();
            if (!$to || (int)$to['branch_id'] !== (int)$order['branch_id']) { $pdo->rollBack(); return 'Bàn đích không hợp lệ.'; }
            if ((int)$to['id'] === (int)$order['table_id'])                  { $pdo->rollBack(); return 'Đơn đang ở bàn này rồi.'; }

            $c = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status = 'open'");
            $c->execute([$toTableId]);
            if ($to['status'] !== 'empty' || (int)$c->fetchColumn() > 0) { $pdo->rollBack(); return 'Bàn đích không còn trống.'; }

            $pdo->prepare('UPDATE orders SET table_id = ? WHERE id = ?')->execute([$toTableId, $orderId]);
            if ($order['table_id']) $pdo->prepare("UPDATE dining_tables SET status = 'empty' WHERE id = ?")->execute([$order['table_id']]);
            $pdo->prepare("UPDATE dining_tables SET status = 'occupied' WHERE id = ?")->execute([$toTableId]);
            $pdo->commit();
            return null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Tính lại tiền tạm tính và tổng của đơn (VAT, giảm giá sẽ xử lý ở giai đoạn 5B)
    public static function recalc(int $orderId): void
    {
        $pdo = Database::get();
        $st = $pdo->prepare('SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?');
        $st->execute([$orderId]);
        $sub = (int)$st->fetchColumn();
        $pdo->prepare('UPDATE orders SET subtotal = ?, total = GREATEST(? - discount + vat, 0) WHERE id = ?')
            ->execute([$sub, $sub, $orderId]);
    }

    // ---------- Danh sách ----------
    public static function floor(int $branchId): array
    {
        $st = Database::get()->prepare(
            "SELECT t.*, o.id AS order_id, o.total AS order_total,
                    TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes,
                    (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM dining_tables t
             LEFT JOIN orders o ON o.table_id = t.id AND o.status = 'open'
             WHERE t.branch_id = ?
             ORDER BY LENGTH(t.table_name), t.table_name");
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    public static function openList(?int $branchId): array
    {
        $sql = "SELECT o.id, o.total, t.table_name, b.name AS branch_name,
                       TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes,
                       (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS item_count
                FROM orders o
                LEFT JOIN dining_tables t ON t.id = o.table_id
                JOIN branches b ON b.id = o.branch_id
                WHERE o.status = 'open'";
        $p = [];
        if ($branchId) { $sql .= ' AND o.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql . ' ORDER BY o.created_at');
        $st->execute($p);
        return $st->fetchAll();
    }

    // ---------- Món trong đơn ----------
    public static function items(int $orderId): array
    {
        $st = Database::get()->prepare(
            'SELECT oi.*, m.name, m.image FROM order_items oi
             JOIN menu_items m ON m.id = oi.menu_item_id
             WHERE oi.order_id = ? ORDER BY oi.id');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function findItem(int $itemId): array|false
    {
        $st = Database::get()->prepare(
            'SELECT oi.*, o.branch_id, o.status AS order_status
             FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.id = ?');
        $st->execute([$itemId]);
        return $st->fetch();
    }

    // Món đang bán tại chi nhánh (đã ẩn hoặc tắt bán thì không hiện)
    public static function menuForBranch(int $branchId, ?int $categoryId): array
    {
        $sql = 'SELECT m.id, m.name, m.image, m.category_id, c.name AS category_name,
                       COALESCE(bm.price, m.base_price) AS price
                FROM menu_items m
                JOIN categories c ON c.id = m.category_id
                LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
                WHERE m.status = 1 AND COALESCE(bm.is_available, 1) = 1';
        $p = [$branchId];
        if ($categoryId) { $sql .= ' AND m.category_id = ?'; $p[] = $categoryId; }
        $st = Database::get()->prepare($sql . ' ORDER BY c.sort_order, c.id, m.name');
        $st->execute($p);
        return $st->fetchAll();
    }

    // Thêm 1 phần. Cùng món, cùng giá, chưa ghi chú và bếp chưa nhận thì cộng dồn vào dòng cũ
    public static function addItem(int $orderId, int $branchId, int $menuItemId): ?string
    {
        $pdo = Database::get();
        $st = $pdo->prepare(
            'SELECT m.id, m.status, COALESCE(bm.price, m.base_price) AS price, COALESCE(bm.is_available, 1) AS avail
             FROM menu_items m
             LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
             WHERE m.id = ?');
        $st->execute([$branchId, $menuItemId]);
        $m = $st->fetch();
        if (!$m || (int)$m['status'] !== 1 || (int)$m['avail'] !== 1) return 'Món này hiện không bán tại chi nhánh.';

        $pdo->beginTransaction();
        try {
            $f = $pdo->prepare("SELECT id, quantity FROM order_items
                                WHERE order_id = ? AND menu_item_id = ? AND unit_price = ? AND kitchen_status = 'new'
                                  AND (note IS NULL OR note = '') ORDER BY id LIMIT 1 FOR UPDATE");
            $f->execute([$orderId, $menuItemId, $m['price']]);
            $line = $f->fetch();
            if ($line) {
                if ((int)$line['quantity'] >= self::MAX_QTY) { $pdo->rollBack(); return 'Tối đa ' . self::MAX_QTY . ' phần mỗi dòng.'; }
                $pdo->prepare('UPDATE order_items SET quantity = quantity + 1 WHERE id = ?')->execute([$line['id']]);
            } else {
                $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, 1, ?)')
                    ->execute([$orderId, $menuItemId, $m['price']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        self::recalc($orderId);
        return null;
    }

    // Tăng hoặc giảm số lượng; về 0 thì xóa dòng
    public static function changeQty(int $itemId, int $delta): ?string
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, order_id, quantity FROM order_items WHERE id = ? FOR UPDATE');
            $st->execute([$itemId]);
            $line = $st->fetch();
            if (!$line) { $pdo->rollBack(); return 'Không tìm thấy dòng món.'; }

            $new = (int)$line['quantity'] + $delta;
            if ($new > self::MAX_QTY) { $pdo->rollBack(); return 'Tối đa ' . self::MAX_QTY . ' phần mỗi dòng.'; }
            if ($new < 1) $pdo->prepare('DELETE FROM order_items WHERE id = ?')->execute([$itemId]);
            else          $pdo->prepare('UPDATE order_items SET quantity = ? WHERE id = ?')->execute([$new, $itemId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        self::recalc((int)$line['order_id']);
        return null;
    }

    public static function setNote(int $itemId, ?string $note): void
    {
        Database::get()->prepare('UPDATE order_items SET note = ? WHERE id = ?')->execute([$note, $itemId]);
    }

    public static function removeItem(int $itemId, int $orderId): void
    {
        Database::get()->prepare('DELETE FROM order_items WHERE id = ?')->execute([$itemId]);
        self::recalc($orderId);
    }

    // =====================================================
    // GIAI ĐOẠN 5B: thanh toán, bếp, lịch sử
    // =====================================================
    const METHODS = ['cash' => 'Tiền mặt', 'card' => 'Thẻ', 'transfer' => 'Chuyển khoản', 'ewallet' => 'Ví điện tử'];

    // Giá trong thực đơn là giá chưa VAT. VAT tính trên (tạm tính - giảm giá)
    public static function compute(int $sub, string $mode, int $value, int $vatRate): array
    {
        $discount = 0;
        if ($mode === 'percent')    $discount = (int)round($sub * $value / 100);
        elseif ($mode === 'amount') $discount = $value;
        $discount = max(0, min($discount, $sub));
        $base = $sub - $discount;
        $vat  = (int)round($base * $vatRate / 100);
        return ['discount' => $discount, 'base' => $base, 'vat' => $vat, 'total' => $base + $vat];
    }

    // Thanh toán và đóng đơn. Trả về [lỗi|null, kết quả|null]. Mọi số tiền đều tính lại ở server
    public static function pay(int $orderId, string $mode, int $value, int $vatRate, string $method, int $cash, ?int $maxDiscountPct): array
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id, table_id, status FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $o = $st->fetch();
            if (!$o || $o['status'] !== 'open') { $pdo->rollBack(); return ['Đơn đã được đóng hoặc không tồn tại.', null]; }

            $st = $pdo->prepare('SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?');
            $st->execute([$orderId]);
            $sub = (int)$st->fetchColumn();
            if ($sub <= 0) { $pdo->rollBack(); return ['Đơn chưa có món nào để thanh toán.', null]; }

            $c = self::compute($sub, $mode, $value, $vatRate);
            if ($maxDiscountPct !== null && $c['discount'] * 100 > $sub * $maxDiscountPct) {
                $pdo->rollBack();
                return ['Thu ngân chỉ được giảm tối đa ' . $maxDiscountPct . '% tạm tính. Hãy nhờ quản lý xử lý mức giảm lớn hơn.', null];
            }
            if ($method === 'cash' && $cash < $c['total']) {
                $pdo->rollBack();
                return ['Tiền khách đưa chưa đủ (cần ' . number_format($c['total'], 0, ',', '.') . ' ₫).', null];
            }

            $pdo->prepare("UPDATE orders SET status = 'paid', subtotal = ?, discount = ?, vat = ?, total = ?, closed_at = NOW() WHERE id = ?")
                ->execute([$sub, $c['discount'], $c['vat'], $c['total'], $orderId]);
            $pdo->prepare('INSERT INTO payments (order_id, method, amount) VALUES (?, ?, ?)')
                ->execute([$orderId, $method, $c['total']]);
            if ($o['table_id']) $pdo->prepare("UPDATE dining_tables SET status = 'empty' WHERE id = ?")->execute([$o['table_id']]);

            $pdo->commit();
            return [null, ['total' => $c['total'], 'change' => $method === 'cash' ? $cash - $c['total'] : 0]];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function payments(int $orderId): array
    {
        $st = Database::get()->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    // Phục vụ báo đã mang món ra bàn
    public static function serve(int $itemId): void
    {
        Database::get()->prepare("UPDATE order_items SET kitchen_status = 'served' WHERE id = ? AND kitchen_status = 'ready'")
            ->execute([$itemId]);
    }

    // Bếp: mới -> đang nấu -> đã xong
    public static function advanceKitchen(int $itemId): void
    {
        Database::get()->prepare(
            "UPDATE order_items
             SET kitchen_status = CASE kitchen_status WHEN 'new' THEN 'cooking' WHEN 'cooking' THEN 'ready' ELSE kitchen_status END
             WHERE id = ? AND kitchen_status IN ('new', 'cooking')")->execute([$itemId]);
    }

    public static function kitchenQueue(int $branchId): array
    {
        $st = Database::get()->prepare(
            "SELECT oi.id, oi.quantity, oi.note, oi.kitchen_status, m.name,
                    o.id AS order_id, t.table_name, TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) AS minutes
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN menu_items m ON m.id = oi.menu_item_id
             LEFT JOIN dining_tables t ON t.id = o.table_id
             WHERE o.branch_id = ? AND o.status = 'open' AND oi.kitchen_status IN ('new', 'cooking', 'ready')
             ORDER BY o.created_at, oi.id");
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    // Đơn đã đóng (thanh toán hoặc hủy) trong một ngày
    public static function history(?int $branchId, string $date): array
    {
        $sql = "SELECT o.id, o.status, o.total, o.closed_at, t.table_name, b.name AS branch_name, p.method
                FROM orders o
                LEFT JOIN dining_tables t ON t.id = o.table_id
                JOIN branches b ON b.id = o.branch_id
                LEFT JOIN payments p ON p.order_id = o.id
                WHERE o.status IN ('paid', 'cancelled') AND DATE(o.closed_at) = ?";
        $p = [$date];
        if ($branchId) { $sql .= ' AND o.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql . ' ORDER BY o.closed_at DESC');
        $st->execute($p);
        return $st->fetchAll();
    }
}
