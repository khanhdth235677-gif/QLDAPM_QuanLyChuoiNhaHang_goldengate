<?php
class Customer
{
    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, created_at, status, (password_hash IS NOT NULL) AS has_account FROM customers WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function findByPhone(string $phone): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, created_at, status, (password_hash IS NOT NULL) AS has_account FROM customers WHERE phone = ?');
        $st->execute([$phone]);
        return $st->fetch();
    }

    public static function accountByPhone(string $phone): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, password_hash, must_change_password, status FROM customers WHERE phone = ?');
        $st->execute([$phone]);
        return $st->fetch();
    }

    public static function registerAccount(string $name, string $phone, string $password): int|false
    {
        try {
            $pdo = Database::get();
            $pdo->prepare(
                'INSERT INTO customers (full_name, phone, password_hash, must_change_password, status) VALUES (?, ?, ?, 0, 1)'
            )->execute([$name, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') return false;
            throw $e;
        }
    }

    public static function accountById(int $id): array|false
    {
        $st = Database::get()->prepare('SELECT id, full_name, phone, password_hash, must_change_password, status FROM customers WHERE id = ? AND password_hash IS NOT NULL');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function setAccountPassword(int $id, string $password, bool $mustChange): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        Database::get()->prepare('UPDATE customers SET password_hash = ?, must_change_password = ? WHERE id = ?')
            ->execute([$hash, $mustChange ? 1 : 0, $id]);
        return $hash;
    }

    public static function deleteOrArchive(int $id): bool
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            if (!$st->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }
            $st = $pdo->prepare(
                'SELECT
                   EXISTS(SELECT 1 FROM orders WHERE customer_id = ?) OR
                   EXISTS(SELECT 1 FROM reservations WHERE customer_id = ?) OR
                   EXISTS(SELECT 1 FROM loyalty_ledger WHERE customer_id = ?) OR
                   EXISTS(SELECT 1 FROM voucher_redemptions WHERE customer_id = ?) OR
                   EXISTS(SELECT 1 FROM chat_conversations WHERE customer_id = ?)'
            );
            $st->execute(array_fill(0, 5, $id));
            $hasHistory = (bool)$st->fetchColumn();
            if ($hasHistory) {
                $pdo->prepare('UPDATE customers SET status = 0 WHERE id = ?')->execute([$id]);
            } else {
                $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
            }
            $pdo->commit();
            return !$hasHistory;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, int $status): void
    {
        Database::get()->prepare('UPDATE customers SET status = ? WHERE id = ?')->execute([$status ? 1 : 0, $id]);
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
        if ($id) {
            $check = $pdo->prepare('SELECT status FROM customers WHERE id = ?');
            $check->execute([(int)$id]);
            if (!(int)$check->fetchColumn()) throw new DomainException('Hồ sơ khách này đã được lưu trữ. Hãy liên hệ quản lý để khôi phục trước khi sử dụng.');
            return (int)$id;
        }

        try {
            $pdo->prepare('INSERT INTO customers (full_name, phone) VALUES (?, ?)')->execute([$name, $phone]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {            // hai người cùng lúc tạo một số điện thoại
            $st->execute([$phone]);
            $id = $st->fetchColumn();
            if ($id) {
                $check = $pdo->prepare('SELECT status FROM customers WHERE id = ?');
                $check->execute([(int)$id]);
                if (!(int)$check->fetchColumn()) throw new DomainException('Hồ sơ khách này đã được lưu trữ. Hãy liên hệ quản lý để khôi phục trước khi sử dụng.');
                return (int)$id;
            }
            throw $e;
        }
    }
}
