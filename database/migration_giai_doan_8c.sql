-- GIAI ĐOẠN 8C: khách hàng thân thiết, tích điểm. Chạy trong phpMyAdmin (database goldengate_db -> tab SQL).
-- (Dùng cho XAMPP/MariaDB. Nếu là MySQL 8, bỏ chữ "IF NOT EXISTS" ở dòng ALTER.)
USE goldengate_db;

CREATE TABLE IF NOT EXISTS loyalty_ledger (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_id INT NULL,
  type ENUM('earn','redeem','adjust') NOT NULL,
  points INT NOT NULL,                       -- dương = cộng điểm, âm = trừ điểm
  note VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer (customer_id, id),
  UNIQUE KEY uq_order_type (order_id, type), -- mỗi đơn chỉ cộng và trừ điểm tối đa một lần
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_customer_status (customer_id, status);
