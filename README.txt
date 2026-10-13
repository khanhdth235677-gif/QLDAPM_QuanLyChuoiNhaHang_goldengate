GOLDEN GATE - Khung dự án (đến Giai đoạn 5B: thanh toán, in hóa đơn, màn hình bếp)

1. Chép thư mục "goldengate" vào C:\xampp\htdocs\  (nếu đã sửa config/config.php thì giữ lại file của bạn)
2. Bật Apache + MySQL, import database/goldengate_db.sql (nếu chưa làm)
3. Chạy database/migration_giai_doan_3.sql trong phpMyAdmin (nếu chưa làm). Giai đoạn 4 không cần migration mới.
4. Mở http://localhost/goldengate  ->  admin
5. Nếu mọi trang báo 404: bật mod_rewrite và để AllowOverride All cho htdocs
6. Nếu tải ảnh món bị lỗi: kiểm tra extension=fileinfo đã bật trong php.ini và thư mục assets/uploads/menu ghi được

Giai đoạn 5B: chỉnh mức VAT và mức giảm giá tối đa của thu ngân trong config/billing.php (hỏi kế toán mức VAT hiện hành).
