GOLDEN GATE - Khung dự án (đến Giai đoạn 8C: khách thân thiết, tích điểm)

1. Chép thư mục "goldengate" vào C:\xampp\htdocs\  (nếu đã sửa config/config.php thì giữ lại file của bạn)
2. Bật Apache + MySQL, import database/goldengate_db.sql (nếu chưa làm)
3. Chạy database/migration_giai_doan_3.sql trong phpMyAdmin (nếu chưa làm). Giai đoạn 4 không cần migration mới.
4. Mở http://localhost/goldengate  ->  admin
5. Nếu mọi trang báo 404: bật mod_rewrite và để AllowOverride All cho htdocs
6. Nếu tải ảnh món bị lỗi: kiểm tra extension=fileinfo đã bật trong php.ini và thư mục assets/uploads/menu ghi được

Giai đoạn 5B: chỉnh mức VAT và mức giảm giá tối đa của thu ngân trong config/billing.php (hỏi kế toán mức VAT hiện hành).

Giai đoạn 6B: chạy database/migration_giai_doan_6b.sql trong phpMyAdmin (tạo bảng recipes).
Tùy chọn: chạy database/seed_cong_thuc_mau.sql để có công thức mẫu thử tự trừ kho.

Giai đoạn 8A: chạy database/migration_giai_doan_8a.sql (thêm cột source, ip cho bảng reservations).
Trang công khai của khách: http://localhost:8080/goldengate/  (khi chưa đăng nhập). Đăng nhập nhân viên: .../auth/login
Chỉnh giờ nhận đặt, số khách tối đa, giới hạn chống spam trong config/booking.php.

Giai đoạn 8B: chat với khách hàng, KHÔNG cần SQL mới (dùng bảng chat_conversations, chat_messages có sẵn).
Khách chat từ khung nổi trên trang công khai. Nhân viên trả lời ở menu "Chat khách hàng".

Giai đoạn 8C: chạy database/migration_giai_doan_8c.sql (tạo bảng loyalty_ledger). Chỉnh quy tắc điểm trong config/loyalty.php.

Giai đoạn 9: bàn giao.
- Menu "Kiểm tra hệ thống" (admin) kiểm tra môi trường, bảo mật, CSDL, thư mục.
- tools/backup.bat, restore.bat, schedule_backup.bat: sao lưu và khôi phục (xem tools/README.txt).
- database/seed_du_lieu_demo.sql: dữ liệu demo để thử nghiệm; xoa_du_lieu_demo.sql để dọn trước khi chạy thật.
