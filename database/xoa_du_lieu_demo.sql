-- DỌN DỮ LIỆU DEMO: xóa mọi thứ do seed_du_lieu_demo.sql tạo ra. Không đụng tới dữ liệu thật.
USE goldengate_db;

DELETE FROM loyalty_ledger WHERE customer_id IN (SELECT id FROM customers WHERE full_name LIKE 'DEMO%' AND phone LIKE '09000000%');
DELETE FROM reservations WHERE customer_name LIKE 'DEMO%';
DELETE FROM orders WHERE id BETWEEN 900001 AND 999999;      -- món và thanh toán xóa theo
DELETE FROM employees WHERE full_name LIKE 'DEMO%';
DELETE FROM customers WHERE full_name LIKE 'DEMO%' AND phone LIKE '09000000%';
DELETE FROM users WHERE username LIKE 'demo\_%';
