-- GIAI ĐOẠN 6B: công thức món (mỗi món dùng bao nhiêu nguyên liệu cho 1 phần)
-- Chạy trong phpMyAdmin: chọn database goldengate_db -> tab SQL -> dán -> Thực hiện
USE goldengate_db;

CREATE TABLE IF NOT EXISTS recipes (
  menu_item_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,          -- định lượng nguyên liệu cho 1 phần, theo đơn vị của nguyên liệu
  PRIMARY KEY (menu_item_id, ingredient_id),
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB;
