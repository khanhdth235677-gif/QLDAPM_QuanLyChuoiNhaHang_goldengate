-- GIAI ĐOẠN 3: chạy trong phpMyAdmin (tab SQL) trên database goldengate_db
-- Dùng cho XAMPP (MariaDB). Nếu bạn dùng MySQL 8, bỏ chữ "IF NOT EXISTS" ở lệnh ALTER.
USE goldengate_db;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_ip_time (username, ip, attempted_at),
  INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB;

ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0;

-- Tài khoản mẫu dùng mật khẩu "password" nên bắt buộc đổi ở lần đăng nhập đầu
UPDATE users SET must_change_password = 1 WHERE username IN ('admin', 'manager1');
