<?php
require_once ROOT_PATH . '/config/loyalty.php';

// Khách thân thiết: điểm tích lũy, hạng, món hay gọi, gợi ý món
class Loyalty
{
    private static ?bool $on = null;

    // Database cũ có thể chưa có bảng điểm; khi đó tắt riêng tính năng này.
    public static function enabled(): bool
    {
        if (self::$on === null) {
            try { Database::get()->query('SELECT 1 FROM loyalty_ledger LIMIT 1'); self::$on = true; }
            catch (Throwable $e) { self::$on = false; }
        }
        return self::$on;
    }

    // Hạng theo tổng điểm đã tích lũy
    public static function tier(int $earned): array
    {
        $cur = LOYALTY_TIERS[0]; $next = null;
        foreach (LOYALTY_TIERS as $t) if ($earned >= $t[1]) $cur = $t;
        foreach (LOYALTY_TIERS as $t) if ($t[1] > $earned) { $next = $t; break; }
        return ['name' => $cur[0], 'min' => $cur[1], 'mult' => $cur[2], 'cls' => $cur[3],
                'next_name' => $next[0] ?? null, 'next_need' => $next ? $next[1] - $earned : null];
    }

    // Khóa hồ sơ khách và trả về [số điểm hiện có, tổng điểm đã tích lũy]. Dùng bên trong giao dịch thanh toán
    public static function lock(PDO $pdo, int $customerId): array
    {
        $pdo->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE')->execute([$customerId]);
        $st = $pdo->prepare("SELECT COALESCE(SUM(points), 0), COALESCE(SUM(CASE WHEN type = 'earn' THEN points ELSE 0 END), 0)
                             FROM loyalty_ledger WHERE customer_id = ?");
        $st->execute([$customerId]);
        $r = $st->fetch(PDO::FETCH_NUM);
        return [(int)$r[0], (int)$r[1]];
    }

    public static function addLedger(PDO $pdo, int $customerId, ?int $orderId, string $type, int $points, ?string $note, ?int $userId): void
    {
        $pdo->prepare('INSERT INTO loyalty_ledger (customer_id, order_id, type, points, note, created_by) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$customerId, $orderId, $type, $points, $note, $userId]);
    }

    public static function setOrderCustomer(int $orderId, ?int $customerId): void
    {
        Database::get()->prepare("UPDATE orders SET customer_id = ? WHERE id = ? AND status = 'open'")->execute([$customerId, $orderId]);
    }

    // Thống kê một khách: lượt ghé, chi tiêu, điểm
    public static function summary(int $customerId): array
    {
        $pdo = Database::get();
        $st = $pdo->prepare("SELECT COUNT(*) AS visits, COALESCE(SUM(total), 0) AS spent, MIN(closed_at) AS first_visit, MAX(closed_at) AS last_visit
                             FROM orders WHERE customer_id = ? AND status = 'paid'");
        $st->execute([$customerId]);
        $r = $st->fetch();
        $r['avg'] = (int)$r['visits'] > 0 ? (int)round($r['spent'] / $r['visits']) : 0;
        $r['balance'] = 0; $r['earned'] = 0;
        if (self::enabled()) {
            $st = $pdo->prepare("SELECT COALESCE(SUM(points), 0) AS balance, COALESCE(SUM(CASE WHEN type = 'earn' THEN points ELSE 0 END), 0) AS earned
                                 FROM loyalty_ledger WHERE customer_id = ?");
            $st->execute([$customerId]);
            $l = $st->fetch();
            $r['balance'] = (int)$l['balance']; $r['earned'] = (int)$l['earned'];
        }
        return $r;
    }

    // Món khách hay gọi nhất, chỉ món đang bán tại chi nhánh (để gợi ý ngay khi gọi món)
    public static function favorites(int $customerId, int $branchId, int $limit = 6): array
    {
        $st = Database::get()->prepare(
            "SELECT m.id, m.name, m.image, COALESCE(bm.price, m.base_price) AS price, SUM(oi.quantity) AS qty
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN menu_items m ON m.id = oi.menu_item_id
             LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
             WHERE o.customer_id = ? AND o.status = 'paid' AND m.status = 1 AND COALESCE(bm.is_available, 1) = 1
             GROUP BY m.id, m.name, m.image, bm.price, m.base_price
             ORDER BY qty DESC, MAX(o.closed_at) DESC LIMIT " . (int)$limit);
        $st->execute([$branchId, $customerId]);
        return $st->fetchAll();
    }

    // Món khách đã gọi nhiều nhất từ trước đến nay (cho trang hồ sơ khách, không lọc theo chi nhánh)
    public static function topItems(int $customerId, int $limit = 10): array
    {
        $st = Database::get()->prepare(
            "SELECT m.name, SUM(oi.quantity) AS qty, MAX(o.closed_at) AS last_at
             FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN menu_items m ON m.id = oi.menu_item_id
             WHERE o.customer_id = ? AND o.status = 'paid'
             GROUP BY m.id, m.name ORDER BY qty DESC, last_at DESC LIMIT " . (int)$limit);
        $st->execute([$customerId]);
        return $st->fetchAll();
    }

    // Món bán chạy tại chi nhánh trong N ngày gần đây (gợi ý cho khách chưa có lịch sử)
    public static function bestSellers(int $branchId, int $days = 30, int $limit = 6): array
    {
        $st = Database::get()->prepare(
            "SELECT m.id, m.name, m.image, COALESCE(bm.price, m.base_price) AS price, SUM(oi.quantity) AS qty
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN menu_items m ON m.id = oi.menu_item_id
             LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
             WHERE o.branch_id = ? AND o.status = 'paid' AND o.closed_at >= (NOW() - INTERVAL " . (int)$days . " DAY)
               AND m.status = 1 AND COALESCE(bm.is_available, 1) = 1
             GROUP BY m.id, m.name, m.image, bm.price, m.base_price
             ORDER BY qty DESC LIMIT " . (int)$limit);
        $st->execute([$branchId, $branchId]);
        return $st->fetchAll();
    }

    public static function recentOrders(int $customerId, int $limit = 20): array
    {
        $st = Database::get()->prepare(
            "SELECT o.id, o.branch_id, o.total, o.closed_at, b.name AS branch_name
             FROM orders o JOIN branches b ON b.id = o.branch_id
             WHERE o.customer_id = ? AND o.status = 'paid' ORDER BY o.closed_at DESC LIMIT " . (int)$limit);
        $st->execute([$customerId]);
        return $st->fetchAll();
    }

    public static function ledger(int $customerId, int $limit = 50): array
    {
        if (!self::enabled()) return [];
        $st = Database::get()->prepare(
            'SELECT l.*, u.full_name AS user_name FROM loyalty_ledger l LEFT JOIN users u ON u.id = l.created_by
             WHERE l.customer_id = ? ORDER BY l.id DESC LIMIT ' . (int)$limit);
        $st->execute([$customerId]);
        return $st->fetchAll();
    }

    // Điều chỉnh điểm bằng tay (quản lý, admin). Trả về lỗi hoặc null
    public static function adjust(int $customerId, int $points, string $note, int $userId): ?string
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            [$balance] = self::lock($pdo, $customerId);
            if ($balance + $points < 0) { $pdo->rollBack(); return 'Không thể trừ quá số điểm khách đang có (' . $balance . ' điểm).'; }
            self::addLedger($pdo, $customerId, null, 'adjust', $points, $note, $userId);
            $pdo->commit();
            return null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Điểm của một hóa đơn đã thanh toán, để in lên hóa đơn. Trả về null nếu đơn không gắn khách
    public static function forOrder(int $orderId): ?array
    {
        if (!self::enabled()) return null;
        $pdo = Database::get();
        $st = $pdo->prepare("SELECT o.customer_id, c.full_name FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ? AND o.status = 'paid'");
        $st->execute([$orderId]);
        $c = $st->fetch();
        if (!$c) return null;

        $st = $pdo->prepare('SELECT type, points FROM loyalty_ledger WHERE order_id = ?');
        $st->execute([$orderId]);
        $earn = 0; $redeem = 0;
        foreach ($st->fetchAll() as $r) { if ($r['type'] === 'earn') $earn += (int)$r['points']; elseif ($r['type'] === 'redeem') $redeem += -(int)$r['points']; }

        $s = self::summary((int)$c['customer_id']);
        return ['name' => $c['full_name'], 'earned' => $earn, 'redeemed' => $redeem, 'balance' => $s['balance'], 'tier' => self::tier($s['earned'])];
    }

    // Danh sách khách kèm thống kê, có tìm kiếm và sắp xếp
    public static function search(string $q, string $sort): array
    {
        $ledger = self::enabled()
            ? "(SELECT customer_id, SUM(points) AS balance, SUM(CASE WHEN type = 'earn' THEN points ELSE 0 END) AS earned FROM loyalty_ledger GROUP BY customer_id)"
            : "(SELECT id AS customer_id, 0 AS balance, 0 AS earned FROM customers)";
        $sql = "SELECT c.id, c.full_name, c.phone, c.created_at, c.status,
                       COALESCE(s.visits, 0) AS visits, COALESCE(s.spent, 0) AS spent, s.last_visit,
                       COALESCE(l.balance, 0) AS balance, COALESCE(l.earned, 0) AS earned
                FROM customers c
                LEFT JOIN (SELECT customer_id, COUNT(*) AS visits, SUM(total) AS spent, MAX(closed_at) AS last_visit
                           FROM orders WHERE status = 'paid' AND customer_id IS NOT NULL GROUP BY customer_id) s ON s.customer_id = c.id
                LEFT JOIN $ledger l ON l.customer_id = c.id";
        $p = [];
        if ($q !== '') { $sql .= ' WHERE c.full_name LIKE ? OR c.phone LIKE ?'; $p = ["%$q%", "%$q%"]; }
        $order = ['points' => 'balance DESC', 'spent' => 'spent DESC', 'recent' => 's.last_visit DESC', 'name' => 'c.full_name'][$sort] ?? 'balance DESC';
        $st = Database::get()->prepare($sql . " ORDER BY $order, c.id DESC LIMIT 300");
        $st->execute($p);
        return $st->fetchAll();
    }
}
