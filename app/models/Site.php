<?php
// Dữ liệu cho trang công khai
class Site
{
    const STATUS = [
        'pending'   => ['Chờ nhà hàng xác nhận', 'warning'],
        'confirmed' => ['Đã xác nhận', 'success'],
        'done'      => ['Đã hoàn tất', 'secondary'],
        'cancelled' => ['Đã hủy', 'secondary'],
    ];

    public static function activeBranches(): array
    {
        return Database::get()->query('SELECT id, name, address, phone FROM branches WHERE status = 1 ORDER BY id')->fetchAll();
    }

    public static function branch(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT id, name, address, phone FROM branches WHERE id = ? AND status = 1');
        $st->execute([$id]);
        return $st->fetch();
    }

    // Món nổi bật: bán chạy nhất, chưa có đơn nào thì theo thứ tự thêm vào
    public static function featured(int $limit = 6): array
    {
        return Database::get()->query(
            'SELECT m.id, m.name, m.description, m.image, m.base_price
             FROM menu_items m WHERE m.status = 1
             ORDER BY (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.menu_item_id = m.id) DESC, m.id
             LIMIT ' . (int)$limit)->fetchAll();
    }

    // Thực đơn công khai của một chi nhánh: chỉ món đang bán, giá của chi nhánh đó
    public static function menu(int $branchId): array
    {
        $st = Database::get()->prepare(
            'SELECT m.id, m.name, m.description, m.image, c.id AS category_id, c.name AS category_name,
                    COALESCE(bm.price, m.base_price) AS price
             FROM menu_items m
             JOIN categories c ON c.id = m.category_id
             LEFT JOIN branch_menu bm ON bm.menu_item_id = m.id AND bm.branch_id = ?
             WHERE m.status = 1 AND COALESCE(bm.is_available, 1) = 1
             ORDER BY c.sort_order, c.id, m.name');
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    // Các khung giờ nhận đặt: 09:00, 09:30, ... theo cấu hình
    public static function slots(): array
    {
        $out = [];
        for ($m = BOOK_FIRST_HOUR * 60; $m <= BOOK_LAST_HOUR * 60; $m += BOOK_SLOT_MIN) $out[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        return $out;
    }
}
