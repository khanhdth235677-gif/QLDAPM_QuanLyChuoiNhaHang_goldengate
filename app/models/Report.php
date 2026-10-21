<?php
// Các truy vấn báo cáo. Chỉ tính đơn đã thanh toán (status = paid), theo ngày đóng đơn.
class Report
{
    private static function bounds(string $from, string $to): array
    {
        return [$from . ' 00:00:00', date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
    }

    // WHERE cho đơn đã thanh toán trong khoảng ngày (và chi nhánh nếu có)
    private static function paid(?int $branchId, string $from, string $to): array
    {
        [$a, $b] = self::bounds($from, $to);
        $sql = "o.status = 'paid' AND o.closed_at >= ? AND o.closed_at < ?";
        $p = [$a, $b];
        if ($branchId) { $sql .= ' AND o.branch_id = ?'; $p[] = $branchId; }
        return [$sql, $p];
    }

    private static function rows(string $sql, array $p): array
    {
        $st = Database::get()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function summary(?int $branchId, string $from, string $to): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        $r = self::rows("SELECT COUNT(*) AS orders, COALESCE(SUM(o.total),0) AS revenue, COALESCE(SUM(o.subtotal),0) AS subtotal,
                                COALESCE(SUM(o.discount),0) AS discount, COALESCE(SUM(o.vat),0) AS vat
                         FROM orders o WHERE $w", $p)[0];

        [$a, $b] = self::bounds($from, $to);
        $sql = "SELECT COUNT(*) FROM orders o WHERE o.status = 'cancelled' AND o.closed_at >= ? AND o.closed_at < ?";
        $pc = [$a, $b];
        if ($branchId) { $sql .= ' AND o.branch_id = ?'; $pc[] = $branchId; }
        $st = Database::get()->prepare($sql);
        $st->execute($pc);
        $r['cancelled'] = (int)$st->fetchColumn();
        $r['avg'] = (int)$r['orders'] > 0 ? round($r['revenue'] / $r['orders']) : 0;
        return $r;
    }

    // Doanh thu theo ngày hoặc tháng, có đủ các kỳ (kỳ không có đơn = 0)
    public static function byPeriod(?int $branchId, string $from, string $to, string $by): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        $expr = $by === 'month' ? "DATE_FORMAT(o.closed_at, '%Y-%m')" : 'DATE(o.closed_at)';
        $found = [];
        foreach (self::rows("SELECT $expr AS period, COUNT(*) AS orders, SUM(o.subtotal) AS subtotal, SUM(o.discount) AS discount,
                                    SUM(o.vat) AS vat, SUM(o.total) AS total
                             FROM orders o WHERE $w GROUP BY period", $p) as $r) $found[$r['period']] = $r;

        $out = [];
        if ($by === 'month') {
            $d = new DateTime(date('Y-m-01', strtotime($from)));
            $end = new DateTime(date('Y-m-01', strtotime($to)));
            for (; $d <= $end; $d->modify('+1 month')) $out[] = self::fill($found, $d->format('Y-m'), $d->format('m/Y'));
        } else {
            for ($ts = strtotime($from); $ts <= strtotime($to); $ts += 86400) {
                $key = date('Y-m-d', $ts);
                $out[] = self::fill($found, $key, date('d/m', $ts));
            }
        }
        return $out;
    }

    private static function fill(array $found, string $key, string $label): array
    {
        $r = $found[$key] ?? ['orders' => 0, 'subtotal' => 0, 'discount' => 0, 'vat' => 0, 'total' => 0];
        return ['key' => $key, 'label' => $label, 'orders' => (int)$r['orders'], 'subtotal' => (int)$r['subtotal'],
                'discount' => (int)$r['discount'], 'vat' => (int)$r['vat'], 'total' => (int)$r['total']];
    }

    public static function byMethod(?int $branchId, string $from, string $to): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        return self::rows("SELECT p.method, COUNT(*) AS cnt, SUM(p.amount) AS amount
                           FROM payments p JOIN orders o ON o.id = p.order_id
                           WHERE $w GROUP BY p.method ORDER BY amount DESC", $p);
    }

    // Món bán chạy. $sort = qty | revenue. Doanh thu món là tiền theo giá niêm yết, chưa trừ giảm giá và VAT
    public static function topItems(?int $branchId, string $from, string $to, string $sort, int $limit): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        $order = $sort === 'revenue' ? 'revenue DESC, qty DESC' : 'qty DESC, revenue DESC';
        return self::rows("SELECT m.id, m.name, c.name AS category_name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.unit_price) AS revenue
                           FROM order_items oi
                           JOIN orders o ON o.id = oi.order_id
                           JOIN menu_items m ON m.id = oi.menu_item_id
                           JOIN categories c ON c.id = m.category_id
                           WHERE $w GROUP BY m.id, m.name, c.name ORDER BY $order, m.name LIMIT " . (int)$limit, $p);
    }

    public static function byCategory(?int $branchId, string $from, string $to): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        return self::rows("SELECT c.id, c.name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.unit_price) AS revenue
                           FROM order_items oi
                           JOIN orders o ON o.id = oi.order_id
                           JOIN menu_items m ON m.id = oi.menu_item_id
                           JOIN categories c ON c.id = m.category_id
                           WHERE $w GROUP BY c.id, c.name ORDER BY revenue DESC", $p);
    }

    // Theo giờ khách vào bàn (giờ mở đơn), đủ 24 giờ
    public static function byHour(?int $branchId, string $from, string $to): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        $found = [];
        foreach (self::rows("SELECT HOUR(o.created_at) AS h, COUNT(*) AS orders, SUM(o.total) AS revenue FROM orders o WHERE $w GROUP BY h", $p) as $r) $found[(int)$r['h']] = $r;
        $out = [];
        for ($h = 0; $h < 24; $h++) $out[] = ['h' => $h, 'orders' => (int)($found[$h]['orders'] ?? 0), 'revenue' => (int)($found[$h]['revenue'] ?? 0)];
        return $out;
    }

    // Theo thứ trong tuần (DAYOFWEEK: 1 = Chủ nhật ... 7 = Thứ 7), kèm số ngày thuộc thứ đó trong kỳ để tính trung bình
    public static function byWeekday(?int $branchId, string $from, string $to): array
    {
        [$w, $p] = self::paid($branchId, $from, $to);
        $found = [];
        foreach (self::rows("SELECT DAYOFWEEK(o.created_at) AS d, COUNT(*) AS orders, SUM(o.total) AS revenue FROM orders o WHERE $w GROUP BY d", $p) as $r) $found[(int)$r['d']] = $r;

        $days = array_fill(1, 7, 0);
        for ($ts = strtotime($from); $ts <= strtotime($to); $ts += 86400) $days[(int)date('w', $ts) + 1]++;

        $labels = [2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5', 6 => 'Thứ 6', 7 => 'Thứ 7', 1 => 'Chủ nhật'];
        $out = [];
        foreach ($labels as $d => $label) {
            $rev = (int)($found[$d]['revenue'] ?? 0);
            $out[] = ['label' => $label, 'orders' => (int)($found[$d]['orders'] ?? 0), 'revenue' => $rev,
                      'days' => $days[$d], 'avg' => $days[$d] > 0 ? (int)round($rev / $days[$d]) : 0];
        }
        return $out;
    }

    public static function byBranch(string $from, string $to): array
    {
        [$a, $b] = self::bounds($from, $to);
        return self::rows("SELECT b.id, b.name, COUNT(o.id) AS orders, COALESCE(SUM(o.total),0) AS revenue,
                                  COALESCE(SUM(o.discount),0) AS discount, COALESCE(SUM(o.vat),0) AS vat
                           FROM branches b
                           LEFT JOIN orders o ON o.branch_id = b.id AND o.status = 'paid' AND o.closed_at >= ? AND o.closed_at < ?
                           WHERE b.status = 1 GROUP BY b.id, b.name ORDER BY revenue DESC, b.id", [$a, $b]);
    }

    // Nguyên liệu: dự kiến theo công thức so với thực tế xuất kho. Trả về [dòng, đã có bảng recipes?]
    public static function usage(?int $branchId, string $from, string $to): array
    {
        $auto = 'Tự trừ kho khi thanh toán%';
        $theory = []; $recipesOk = true;
        try {
            [$w, $p] = self::paid($branchId, $from, $to);
            foreach (self::rows("SELECT r.ingredient_id, SUM(r.quantity * oi.quantity) AS theory
                                 FROM orders o JOIN order_items oi ON oi.order_id = o.id
                                 JOIN recipes r ON r.menu_item_id = oi.menu_item_id
                                 WHERE $w GROUP BY r.ingredient_id", $p) as $r) $theory[(int)$r['ingredient_id']] = (float)$r['theory'];
        } catch (Throwable $e) {
            $recipesOk = false;
        }

        [$a, $b] = self::bounds($from, $to);
        $sql = "SELECT m.ingredient_id,
                       SUM(CASE WHEN m.type = 'in' THEN m.quantity ELSE 0 END) AS qty_in,
                       SUM(CASE WHEN m.type = 'out' AND m.note LIKE ? THEN m.quantity ELSE 0 END) AS auto_out,
                       SUM(CASE WHEN m.type = 'out' AND (m.note IS NULL OR m.note NOT LIKE ?) THEN m.quantity ELSE 0 END) AS manual_out,
                       SUM(CASE WHEN m.type = 'adjust' THEN m.quantity ELSE 0 END) AS adjust_net
                FROM stock_movements m WHERE m.created_at >= ? AND m.created_at < ?";
        $p = [$auto, $auto, $a, $b];
        if ($branchId) { $sql .= ' AND m.branch_id = ?'; $p[] = $branchId; }
        $moves = [];
        foreach (self::rows($sql . ' GROUP BY m.ingredient_id', $p) as $r) $moves[(int)$r['ingredient_id']] = $r;

        $out = [];
        foreach (self::rows('SELECT id, name, unit FROM ingredients ORDER BY name', []) as $i) {
            $id = (int)$i['id'];
            $m = $moves[$id] ?? ['qty_in' => 0, 'auto_out' => 0, 'manual_out' => 0, 'adjust_net' => 0];
            $row = ['id' => $id, 'name' => $i['name'], 'unit' => $i['unit'], 'theory' => $theory[$id] ?? 0.0,
                    'qty_in' => (float)$m['qty_in'], 'auto_out' => (float)$m['auto_out'],
                    'manual_out' => (float)$m['manual_out'], 'adjust_net' => (float)$m['adjust_net']];
            // Hao hụt ngoài bán hàng = xuất tay + phần kiểm kê thiếu (kiểm kê dư thì trừ bớt)
            $row['loss'] = $row['manual_out'] - $row['adjust_net'];
            if ($row['theory'] != 0 || $row['qty_in'] != 0 || $row['auto_out'] != 0 || $row['manual_out'] != 0 || $row['adjust_net'] != 0) $out[] = $row;
        }
        return [$out, $recipesOk];
    }
}
