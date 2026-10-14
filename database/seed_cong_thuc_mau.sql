-- (TÙY CHỌN) Công thức mẫu cho 5 món và 5 nguyên liệu mẫu, để thử tính năng tự trừ kho.
-- Chạy SAU migration_giai_doan_6b.sql. Chạy lại nhiều lần không bị trùng.
USE goldengate_db;

INSERT IGNORE INTO recipes (menu_item_id, ingredient_id, quantity)
SELECT m.id, i.id, v.q
FROM (
  SELECT 'Gỏi cuốn tôm thịt' AS mn, 'Tôm' AS ing, 0.05 AS q UNION ALL
  SELECT 'Gỏi cuốn tôm thịt', 'Rau xà lách', 0.03 UNION ALL
  SELECT 'Bò lúc lắc',        'Thịt bò',      0.20 UNION ALL
  SELECT 'Bò lúc lắc',        'Rau xà lách',  0.05 UNION ALL
  SELECT 'Lẩu thái hải sản',  'Tôm',          0.30 UNION ALL
  SELECT 'Lẩu thái hải sản',  'Rau xà lách',  0.20 UNION ALL
  SELECT 'Trà đào cam sả',    'Trà túi lọc',  0.05 UNION ALL
  SELECT 'Trà đào cam sả',    'Đường',        0.02 UNION ALL
  SELECT 'Chè khúc bạch',     'Đường',        0.03
) v
JOIN menu_items m ON m.name = v.mn  COLLATE utf8mb4_unicode_ci
JOIN ingredients i ON i.name = v.ing COLLATE utf8mb4_unicode_ci;
