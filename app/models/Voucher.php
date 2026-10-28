<?php
class Voucher
{
    public static function all(): array
    {
        return Database::get()->query(
            "SELECT v.*, u.full_name AS creator_name,
                    (SELECT COUNT(*) FROM voucher_redemptions r WHERE r.voucher_id = v.id) AS usage_count
             FROM vouchers v LEFT JOIN users u ON u.id = v.created_by
             ORDER BY v.created_at DESC, v.id DESC"
        )->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM vouchers WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function save(?int $id, array $data, ?int $createdBy): void
    {
        $pdo = Database::get();
        if ($id === null) {
            $pdo->prepare(
                'INSERT INTO vouchers
                 (code, description, discount_type, discount_value, min_subtotal, max_discount, starts_at, ends_at, usage_limit, is_active, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
                $data['min_subtotal'], $data['max_discount'], $data['starts_at'], $data['ends_at'],
                $data['usage_limit'], $data['is_active'], $createdBy,
            ]);
            return;
        }

        $pdo->prepare(
            'UPDATE vouchers SET code = ?, description = ?, discount_type = ?, discount_value = ?,
             min_subtotal = ?, max_discount = ?, starts_at = ?, ends_at = ?, usage_limit = ?, is_active = ?
             WHERE id = ?'
        )->execute([
            $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
            $data['min_subtotal'], $data['max_discount'], $data['starts_at'], $data['ends_at'],
            $data['usage_limit'], $data['is_active'], $id,
        ]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::get()->prepare('UPDATE vouchers SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public static function deleteOrDeactivate(int $id): bool
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id FROM vouchers WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            if (!$st->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }

            $st = $pdo->prepare(
                'SELECT EXISTS(SELECT 1 FROM voucher_redemptions WHERE voucher_id = ?)
                     OR EXISTS(SELECT 1 FROM orders WHERE voucher_id = ?)'
            );
            $st->execute([$id, $id]);
            $hasHistory = (bool)$st->fetchColumn();

            if ($hasHistory) {
                $pdo->prepare('UPDATE vouchers SET is_active = 0 WHERE id = ?')->execute([$id]);
            } else {
                $pdo->prepare('DELETE FROM vouchers WHERE id = ?')->execute([$id]);
            }
            $pdo->commit();
            return !$hasHistory;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function available(): array
    {
        $st = Database::get()->prepare(
            "SELECT v.*,
                    (SELECT COUNT(*) FROM voucher_redemptions r WHERE r.voucher_id = v.id) AS usage_count
             FROM vouchers v
             WHERE v.is_active = 1 AND v.starts_at <= NOW() AND v.ends_at >= NOW()
               AND (v.usage_limit IS NULL OR
                    (SELECT COUNT(*) FROM voucher_redemptions r WHERE r.voucher_id = v.id) < v.usage_limit)
             ORDER BY v.ends_at, v.id"
        );
        $st->execute();
        return $st->fetchAll();
    }

    public static function validateForPayment(PDO $pdo, string $code, ?int $customerId, int $subtotal): array
    {
        if (!$customerId) return ['Đơn cần gắn hồ sơ khách hàng để sử dụng voucher.', null];
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen($code) > 40) return ['Mã voucher không hợp lệ.', null];

        $st = $pdo->prepare(
            'SELECT v.*, (v.starts_at <= NOW()) AS is_started, (v.ends_at >= NOW()) AS is_current
             FROM vouchers v WHERE v.code = ? FOR UPDATE'
        );
        $st->execute([$code]);
        $voucher = $st->fetch();
        if (!$voucher || !(int)$voucher['is_active'] || !(int)$voucher['is_started'] || !(int)$voucher['is_current']) {
            return ['Voucher không tồn tại, đã hết hạn hoặc đang tạm khóa.', null];
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM voucher_redemptions WHERE voucher_id = ?');
        $st->execute([(int)$voucher['id']]);
        $used = (int)$st->fetchColumn();
        if ($voucher['usage_limit'] !== null && $used >= (int)$voucher['usage_limit']) {
            return ['Voucher đã hết lượt sử dụng.', null];
        }

        if ($subtotal < (int)$voucher['min_subtotal']) {
            return ['Đơn hàng chưa đạt giá trị tối thiểu ' . number_format((int)$voucher['min_subtotal'], 0, ',', '.') . ' ₫ của voucher.', null];
        }

        $discount = $voucher['discount_type'] === 'percent'
            ? (int)round($subtotal * (int)$voucher['discount_value'] / 100)
            : (int)$voucher['discount_value'];
        if ($voucher['max_discount'] !== null) $discount = min($discount, (int)$voucher['max_discount']);
        $discount = min($subtotal, max(0, $discount));
        if ($discount === 0) return ['Voucher không tạo được khoản giảm giá cho đơn này.', null];

        return [null, ['id' => (int)$voucher['id'], 'code' => $voucher['code'], 'discount' => $discount]];
    }

    public static function redeem(PDO $pdo, int $voucherId, int $customerId, int $orderId, int $discount): void
    {
        $pdo->prepare(
            'INSERT INTO voucher_redemptions (voucher_id, customer_id, order_id, discount_amount)
             VALUES (?, ?, ?, ?)'
        )->execute([$voucherId, $customerId, $orderId, $discount]);
    }
}
