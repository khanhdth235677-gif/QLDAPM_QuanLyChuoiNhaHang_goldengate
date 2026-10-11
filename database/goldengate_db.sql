-- =====================================================
-- GOLDEN GATE - CSDL quản lý chuỗi nhà hàng (MySQL/MariaDB)
-- Import qua phpMyAdmin: tab Import -> chọn file này
-- =====================================================
CREATE DATABASE IF NOT EXISTS goldengate_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goldengate_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------- 1. Chi nhánh ----------
CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- 2. Phân quyền, tài khoản ----------
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,   -- admin, manager, cashier, waiter, kitchen
  name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  branch_id INT NULL,                 -- NULL = admin tổng
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

-- ---------- 3. Nhân viên ----------
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  branch_id INT NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  position VARCHAR(50) NOT NULL,
  salary DECIMAL(12,0) NOT NULL DEFAULT 0,
  hired_date DATE,
  status TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

-- ---------- 4. Khách hàng ----------
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) UNIQUE,
  email VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255) NULL,    -- NULL nếu khách chưa tạo tài khoản
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- 5. Thực đơn ----------
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  sort_order INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  image VARCHAR(255),
  base_price DECIMAL(12,0) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- Giá/tình trạng món riêng theo từng chi nhánh
CREATE TABLE branch_menu (
  branch_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  price DECIMAL(12,0) NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (branch_id, menu_item_id),
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- 6. Bàn, đặt bàn ----------
CREATE TABLE dining_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  table_name VARCHAR(30) NOT NULL,
  seats INT NOT NULL DEFAULT 4,
  status ENUM('empty','occupied','reserved') NOT NULL DEFAULT 'empty',
  UNIQUE KEY uq_branch_table (branch_id, table_name),
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  table_id INT NULL,
  customer_id INT NULL,
  customer_name VARCHAR(100) NOT NULL,
  customer_phone VARCHAR(20) NOT NULL,
  guests INT NOT NULL,
  reserved_at DATETIME NOT NULL,
  note VARCHAR(255),
  status ENUM('pending','confirmed','cancelled','done') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE SET NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- 7. Đơn hàng, thanh toán ----------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  table_id INT NULL,
  customer_id INT NULL,
  created_by INT NOT NULL,            -- users.id
  status ENUM('open','paid','cancelled') NOT NULL DEFAULT 'open',
  subtotal DECIMAL(12,0) NOT NULL DEFAULT 0,
  discount DECIMAL(12,0) NOT NULL DEFAULT 0,
  vat DECIMAL(12,0) NOT NULL DEFAULT 0,
  total DECIMAL(12,0) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (table_id) REFERENCES dining_tables(id) ON DELETE SET NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,0) NOT NULL,  -- lưu giá tại thời điểm bán
  note VARCHAR(255),
  kitchen_status ENUM('new','cooking','ready','served') NOT NULL DEFAULT 'new',
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  method ENUM('cash','card','transfer','ewallet') NOT NULL,
  amount DECIMAL(12,0) NOT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- 8. Kho ----------
CREATE TABLE ingredients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  unit VARCHAR(20) NOT NULL           -- kg, lít, cái...
) ENGINE=InnoDB;

CREATE TABLE inventory (
  branch_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  min_quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (branch_id, ingredient_id),
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  type ENUM('in','out','adjust') NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  note VARCHAR(255),
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- 9. CHAT VỚI KHÁCH HÀNG ----------
-- Mỗi cuộc trò chuyện = 1 khách + (tùy chọn) 1 chi nhánh + nhân viên phụ trách
CREATE TABLE chat_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,                       -- khách đã đăng nhập
  guest_name VARCHAR(100) NULL,               -- khách vãng lai
  guest_token CHAR(40) NULL,                  -- định danh khách vãng lai (lưu cookie)
  branch_id INT NULL,                         -- khách chọn chi nhánh cần hỏi
  assigned_user_id INT NULL,                  -- nhân viên đang xử lý
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_message_at DATETIME NULL,
  INDEX idx_status_last (status, last_message_at),
  INDEX idx_guest_token (guest_token),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_type ENUM('customer','staff') NOT NULL,
  sender_user_id INT NULL,                    -- có giá trị khi sender_type = 'staff'
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conv_id (conversation_id, id),
  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================
INSERT INTO roles (code, name) VALUES
('admin','Quản trị tổng'),
('manager','Quản lý chi nhánh'),
('cashier','Thu ngân'),
('waiter','Phục vụ'),
('kitchen','Bếp');

INSERT INTO branches (name, address, phone) VALUES
('Golden Gate Quận 1', '12 Lê Lợi, Quận 1, TP.HCM', '0281234567'),
('Golden Gate Dĩ An',  '88 Nguyễn Thị Minh Khai, Dĩ An, Bình Dương', '0274123456');

-- Mật khẩu mẫu cho cả 2 tài khoản: password  (ĐỔI NGAY sau khi cài)
INSERT INTO users (role_id, branch_id, username, password_hash, full_name) VALUES
(1, NULL, 'admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên'),
(2, 1,    'manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản lý Quận 1');

INSERT INTO categories (name, sort_order) VALUES
('Khai vị',1),('Món chính',2),('Lẩu',3),('Đồ uống',4),('Tráng miệng',5);

INSERT INTO menu_items (category_id, name, description, base_price) VALUES
(1,'Gỏi cuốn tôm thịt','Gỏi cuốn tươi ăn kèm nước chấm',45000),
(2,'Bò lúc lắc','Bò Úc xào tiêu đen, khoai tây chiên',165000),
(3,'Lẩu thái hải sản','Dành cho 3-4 người',389000),
(4,'Trà đào cam sả','Ly 500ml',39000),
(5,'Chè khúc bạch','Chè mát lạnh',35000);

INSERT INTO branch_menu (branch_id, menu_item_id, price)
SELECT b.id, m.id, m.base_price FROM branches b CROSS JOIN menu_items m;

INSERT INTO dining_tables (branch_id, table_name, seats) VALUES
(1,'B01',4),(1,'B02',4),(1,'B03',6),(1,'B04',8),
(2,'B01',4),(2,'B02',4),(2,'B03',6);

INSERT INTO ingredients (name, unit) VALUES
('Thịt bò','kg'),('Tôm','kg'),('Rau xà lách','kg'),('Đường','kg'),('Trà túi lọc','hộp');

INSERT INTO inventory (branch_id, ingredient_id, quantity, min_quantity)
SELECT b.id, i.id, 20, 5 FROM branches b CROSS JOIN ingredients i;
