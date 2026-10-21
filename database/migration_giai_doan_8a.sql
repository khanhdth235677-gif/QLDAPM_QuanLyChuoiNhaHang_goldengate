-- GIAI ĐOẠN 8A: đặt bàn online. Cần cho trang đặt bàn của khách.
-- Chạy trong phpMyAdmin: chọn database goldengate_db -> tab SQL -> dán -> Thực hiện
-- (Dùng cho XAMPP/MariaDB. Nếu là MySQL 8, bỏ chữ "IF NOT EXISTS" ở các dòng ALTER.)
USE goldengate_db;

ALTER TABLE reservations ADD COLUMN IF NOT EXISTS source ENUM('staff','online') NOT NULL DEFAULT 'staff';
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS ip VARCHAR(45) NULL;
ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_res_ip_time (ip, created_at);
ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_res_branch_time (branch_id, reserved_at);
