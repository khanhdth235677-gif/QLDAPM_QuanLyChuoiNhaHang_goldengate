# GOLDEN GATE

Ứng dụng web quản lý chuỗi nhà hàng, viết bằng PHP theo mô hình MVC và sử dụng MySQL/MariaDB. Hệ thống có giao diện công khai cho khách hàng và khu vực vận hành dành cho nhân viên; dữ liệu bán hàng, bàn, kho và báo cáo được phân tách theo chi nhánh.

## Mục lục

- [Chức năng](#chức-năng)
- [Liên kết giữa trang chủ và khu vực quản lý](#liên-kết-giữa-trang-chủ-và-khu-vực-quản-lý)
- [Công nghệ và yêu cầu](#công-nghệ-và-yêu-cầu)
- [Cài đặt trên XAMPP](#cài-đặt-trên-xampp)
- [Tài khoản và dữ liệu demo](#tài-khoản-và-dữ-liệu-demo)
- [Cấu hình](#cấu-hình)
- [Cấu trúc dự án](#cấu-trúc-dự-án)
- [Cơ sở dữ liệu](#cơ-sở-dữ-liệu)
- [Quy trình vận hành dữ liệu](#quy-trình-vận-hành-dữ-liệu)
- [Quy trình vận hành hằng ngày](#quy-trình-vận-hành-hằng-ngày)
- [Sao lưu và khôi phục](#sao-lưu-và-khôi-phục)
- [Xử lý sự cố](#xử-lý-sự-cố)
- [Lưu ý trước khi triển khai thật](#lưu-ý-trước-khi-triển-khai-thật)

## Chức năng

### Trang dành cho khách

- Trang giới thiệu; xem thực đơn theo danh mục, chọn chi nhánh, bấm ảnh món để xem chi tiết và các món cùng danh mục.
- Gửi đặt bàn trực tuyến, xem thông tin xác nhận và tra cứu/hủy đặt bàn theo thông tin đặt chỗ.
- Quét mã QR riêng tại bàn để mở menu trên điện thoại, chọn món và gửi món trực tiếp vào đơn đang mở.
- Tự đăng ký tài khoản bằng họ tên, số điện thoại và mật khẩu; đăng nhập để xem voucher đang hiệu lực.
- Trò chuyện với nhân viên qua widget chat trên trang công khai; khách không cần tài khoản nhân viên.

### Khu vực nhân viên

- Đăng nhập, đăng xuất, đổi mật khẩu; hỗ trợ bắt buộc đổi mật khẩu cho tài khoản được đánh dấu.
- Màn hình tổng quan theo vai trò (quản trị, quản lý, thu ngân, phục vụ, bếp) và phạm vi chi nhánh.
- Quản lý chi nhánh, tài khoản người dùng, nhân viên, trạng thái và phân quyền.
- Quản lý danh mục món, thực đơn, ảnh món, giá/tình trạng phục vụ theo chi nhánh.
- Sơ đồ khu vực/bàn; mở đơn tại bàn, thêm món, sửa số lượng/ghi chú, chuyển bàn và hủy đơn.
- Admin/quản lý tạo và in mã QR riêng cho từng bàn từ mục **Quản lý bàn**.
- Theo dõi trạng thái món theo quy trình bếp: mới, đang chế biến, đã xong, đã phục vụ.
- Thanh toán, phương thức thanh toán, VAT, chiết khấu, in/xem hóa đơn và lịch sử đơn.
- Tiếp nhận và cập nhật trạng thái đặt bàn; nhân viên có thể tạo đặt chỗ thay khách.
- Quản lý nguyên liệu, tồn kho theo chi nhánh, ngưỡng tồn tối thiểu, nhập/xuất/điều chỉnh và lịch sử biến động.
- Khai báo công thức món để tính lượng nguyên liệu sử dụng khi bán.
- Hồ sơ khách hàng, lịch sử mua hàng, điểm thưởng, đổi điểm, điều chỉnh điểm và hạng thành viên.
- Quản lý mã voucher theo phần trăm/số tiền, thời gian, giá trị đơn tối thiểu và giới hạn lượt sử dụng; áp dụng tại thanh toán.
- Xóa an toàn tài khoản, voucher, chi nhánh và hồ sơ khách: chỉ xóa hẳn khi chưa có dữ liệu liên quan; bản ghi có lịch sử được khóa/lưu trữ để giữ báo cáo và hóa đơn.
- Nhân viên tiếp nhận, trả lời, phân công, đóng hoặc mở lại cuộc trò chuyện của khách.
- Báo cáo doanh thu, món bán chạy, giờ/ngày cao điểm, hiệu quả chi nhánh và sử dụng nguyên liệu; hỗ trợ xuất báo cáo.
- Trang “Kiểm tra hệ thống” dành cho admin, kiểm tra môi trường, kết nối/bảng cơ sở dữ liệu và thư mục ảnh.

### Vai trò

Các vai trò được khai báo trong cơ sở dữ liệu gồm `admin`, `manager`, `cashier`, `waiter` và `kitchen`. Quyền thao tác và dữ liệu chi nhánh được kiểm tra ở controller; admin tổng có thể quản lý/xem phạm vi toàn chuỗi ở các màn hình hỗ trợ, còn nhân viên chi nhánh bị giới hạn theo chi nhánh được gán.

- `admin`: quản trị toàn chuỗi, tài khoản/quyền, chi nhánh, kiểm tra hệ thống và cấu hình nghiệp vụ.
- `manager`: vận hành chi nhánh được gán, thực đơn, nhân viên, kho, voucher, báo cáo, khách hàng và xử lý giảm giá không theo giới hạn thu ngân.
- `cashier`: bàn/đơn, thanh toán trong giới hạn chiết khấu, lịch sử đơn, đặt bàn và hồ sơ khách.
- `waiter`: sơ đồ bàn, mở đơn, ghi món, xử lý đặt bàn và phối hợp với bếp.
- `kitchen`: màn hình bếp và chuyển trạng thái chế biến món.

Không chia sẻ tài khoản giữa nhân viên. Admin/quản lý cần gán đúng vai trò và chi nhánh; giới hạn ở giao diện không thay thế trách nhiệm giữ mật khẩu riêng.

## Liên kết giữa trang chủ và khu vực quản lý

Trang chủ công khai (`/goldengate/` hoặc `/goldengate/site`) là nơi khách xem chi nhánh, thực đơn, đặt bàn và chat. Khu vực quản lý dùng chung ứng dụng và database, nhưng yêu cầu nhân viên đăng nhập để truy cập các màn hình nghiệp vụ.

- Khách hoặc nhân viên chưa đăng nhập có thể chọn **Nhân viên đăng nhập** trên thanh điều hướng trang công khai hoặc ở chân trang để mở `/goldengate/auth/login`.
- Sau khi đăng nhập, admin truy cập dashboard tại `/goldengate/dashboard`; vai trò và chi nhánh gán cho tài khoản quyết định quyền quản lý.
- Khách hàng có thể tự đăng ký tại `/goldengate/customer/register`, sau đó đăng nhập tại `/goldengate/customer/login` bằng số điện thoại và mật khẩu tự tạo.
- Đăng ký chỉ tạo hồ sơ mới. Nếu số điện thoại đã có hồ sơ trong hệ thống, khách cần liên hệ nhân viên để được hỗ trợ; việc này tránh người khác chiếm lịch sử/điểm của hồ sơ có sẵn.
- Admin/quản lý tạo voucher tại mục **Voucher**. Khách đưa mã cho nhân viên khi thanh toán; mỗi đơn chỉ dùng một mã, voucher không áp dụng cùng chiết khấu nhập tay hoặc đổi điểm.
- Trong khu vực quản lý, mở menu tài khoản ở góc trên bên phải và chọn **Trang chủ khách hàng** để quay lại giao diện công khai. Phiên đăng nhập vẫn được giữ; có thể trở lại dashboard qua nút **Trang quản lý**.
- Truy cập URL gốc khi chưa đăng nhập sẽ mở trang chủ công khai; khi đang đăng nhập sẽ mở dashboard. Dùng `/goldengate/site` để vào trang công khai trực tiếp trong lúc đã đăng nhập.
- Đăng xuất qua menu tài khoản sẽ kết thúc phiên; các trang quản lý sau đó yêu cầu đăng nhập lại.

## Công nghệ và yêu cầu

- PHP 8.0 trở lên.
- Apache có bật `mod_rewrite` và cho phép `.htaccess` (`AllowOverride All`).
- MySQL hoặc MariaDB; khuyến nghị MariaDB đi kèm XAMPP.
- Các extension PHP cần có: `pdo_mysql`, `mbstring`, `fileinfo`.
- Trình duyệt hiện đại có bật JavaScript.
- Quyền ghi cho `assets/uploads/menu` để tải ảnh món lên.

Ứng dụng hiện không có bước cài thư viện qua Composer/npm được khai báo trong dự án. Cấu hình database và URL được quản lý trực tiếp trong các file PHP ở `config/`.

## Cài đặt trên XAMPP

### 1. Đặt mã nguồn

Chép/clon thư mục dự án thành:

```text
C:\xampp\htdocs\goldengate
```

Nếu đặt ở đường dẫn hoặc tên thư mục khác, cập nhật đồng bộ `BASE_URL` trong `config/config.php` và `RewriteBase` trong `.htaccess`.

### 2. Cấu hình kết nối

Mở `config/config.php`, kiểm tra các giá trị:

```php
define('BASE_URL', '/goldengate');
define('PUBLIC_BASE_URL', 'http://localhost/goldengate');
define('DB_HOST', 'localhost');
define('DB_NAME', 'goldengate_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Điều chỉnh user/mật khẩu MySQL theo máy cài đặt. Các giá trị `root` không mật khẩu chỉ phù hợp với cấu hình XAMPP cục bộ mặc định, không dùng cho môi trường thật.

`PUBLIC_BASE_URL` phải là địa chỉ mà điện thoại khách truy cập được. Khi dùng trong cùng mạng Wi-Fi, đổi `localhost` thành IP LAN hoặc tên miền của máy chủ (ví dụ `http://192.168.1.20/goldengate`); không dùng `localhost` trong QR in cho điện thoại. Máy chủ cũng cần cho phép thiết bị khách truy cập cổng Apache.

### 3. Khởi tạo database

1. Mở XAMPP Control Panel và bật Apache, MySQL.
2. Truy cập phpMyAdmin, tab **Import**, chọn `database/goldengate_db.sql` và thực thi. File này tạo database `goldengate_db`, các bảng nền, vai trò, hai chi nhánh, thực đơn và tài khoản khởi tạo.
3. Đây là file SQL nền duy nhất hiện có. Nó bao gồm các bảng và dữ liệu khởi tạo cho đăng nhập, đặt bàn online, công thức, QR, tài khoản khách, voucher và điểm thành viên.

> Chỉ import file này vào database mới/trống. Không import lại lên database đang có dữ liệu: schema tạo bảng và thêm dữ liệu mẫu ban đầu.

**Nếu đã có database đang dùng:** không import lại file SQL nền, không xóa database cũ và không chạy các câu `ALTER TABLE` lấy từ README cũ. Hãy sao lưu cả database lẫn ảnh trước; tạo database thử nghiệm riêng để import schema nền và đối chiếu với database hiện tại. Repository hiện không có công cụ migration tự động/được đánh phiên bản cho nâng cấp tại chỗ. Cần xác định chính xác chênh lệch schema và dữ liệu, chuẩn bị câu lệnh nâng cấp có thể khôi phục, thử trên bản sao trước rồi mới áp dụng. Nếu chưa chắc chắn, dừng nâng cấp và nhờ người phụ trách kỹ thuật.

Sau khi import mới, mở **Kiểm tra hệ thống** bằng tài khoản admin và xử lý mọi lỗi cấu hình trước khi sử dụng. Token QR được tạo khi admin/quản lý mở trang QR của từng bàn.

### 4. Bật extension và thư mục ảnh

- Trong `php.ini`, bật các extension `pdo_mysql`, `mbstring`, `fileinfo` nếu chưa bật; khởi động lại Apache sau khi sửa.
- Đảm bảo thư mục `assets/uploads/menu` tồn tại và tài khoản chạy Apache có quyền ghi.
- Ảnh tải lên nhận JPG, PNG, WebP và giới hạn 2 MB mỗi ảnh.

### 5. Đăng nhập và mở ứng dụng

- Trang công khai: `http://localhost/goldengate/`
- Trang đăng nhập nhân viên: `http://localhost/goldengate/auth/login`
- Đăng ký tài khoản khách: `http://localhost/goldengate/customer/register`; đăng nhập: `http://localhost/goldengate/customer/login`.
- Tài khoản ban đầu: `admin` / `password`; tài khoản quản lý mẫu: `manager1` / `password`.
- Cả hai tài khoản khởi tạo được đánh dấu phải đổi mật khẩu ở lần đăng nhập đầu. Đổi mật khẩu mặc định ngay sau khi cài.

Đường dẫn gốc tự hiển thị trang công khai khi chưa đăng nhập và chuyển sang dashboard khi đã đăng nhập. Nếu Apache chạy cổng khác, thay URL cho phù hợp (ví dụ `http://localhost:8080/goldengate/`).

## Tài khoản và dữ liệu demo

File `database/goldengate_db.sql` tạo dữ liệu mẫu ban đầu gồm hai chi nhánh, vai trò, tài khoản admin/manager, danh mục/món, bàn, nguyên liệu và công thức mẫu. Tài khoản ban đầu là `admin` / `password` và `manager1` / `password`; cả hai được yêu cầu đổi mật khẩu ở lần đăng nhập đầu.

Các đơn hàng demo cho báo cáo không được đóng gói trong file cài đặt. Không thêm seed demo vào database sản xuất.

## Cấu hình

| File | Nội dung |
|---|---|
| `config/config.php` | Tên ứng dụng, URL gốc, kết nối database, chế độ debug, thời gian hết hạn phiên, múi giờ. |
| `config/billing.php` | VAT mặc định 10%, các mức VAT cho phép 0/5/8/10%, giới hạn chiết khấu thu ngân 10%. Xác nhận mức thuế hiện hành với kế toán trước khi sử dụng. |
| `config/booking.php` | Giờ nhận đặt 09:00–21:00, bước giờ 30 phút, đặt trước tối thiểu 60 phút, giới hạn 60 ngày/20 khách, điều kiện hủy và giới hạn chống spam theo IP/số điện thoại. |
| `config/loyalty.php` | Quy đổi 10.000 ₫ thành 1 điểm; 1 điểm giảm 1.000 ₫; giới hạn đổi điểm 50% tạm tính, tối thiểu 10 điểm và ngưỡng các hạng thành viên. |
| `PUBLIC_BASE_URL` trong `config/config.php` | Địa chỉ tuyệt đối được mã hóa vào QR từng bàn; phải truy cập được từ điện thoại khách. |

Phiên đăng nhập nhân viên và khách hàng hết hạn sau 30 phút không hoạt động (cấu hình `SESSION_TIMEOUT`). Các request polling chat không được tính là hoạt động tương tác để phiên vẫn hết hạn đúng thời gian.

### Gọi món bằng QR tại bàn

1. Đặt `PUBLIC_BASE_URL` trong `config/config.php` thành địa chỉ server mà điện thoại khách truy cập được.
2. Nhân viên mở bàn/tạo đơn theo quy trình hiện tại.
3. Admin hoặc quản lý vào **Quản lý bàn**, chọn **Mã QR** ở dòng bàn cần in, rồi chọn **In mã QR**.
4. Khách quét mã để mở menu đúng bàn/chi nhánh, chọn số lượng, ghi chú và gửi món. Món được thêm trực tiếp vào đơn đang mở với trạng thái bếp “Mới”, không cần nhân viên xác nhận lại.

Nếu bàn chưa có đơn đang mở (hoặc đơn đã thanh toán), khách vẫn xem được trang nhưng không thể gửi món; cần nhân viên mở bàn trước. QR được tạo trong trình duyệt bằng thư viện qrcodejs từ jsDelivr, vì vậy trang in QR cần kết nối Internet. Việc gửi món yêu cầu session/CSRF hợp lệ và giới hạn số món mỗi lần gửi; QR là mã truy cập công khai nên chỉ đặt tại bàn tương ứng.

### Voucher và tài khoản khách hàng

- Admin/quản lý vào **Voucher** để tạo mã dùng chung, chọn giảm theo phần trăm hoặc số tiền, đặt thời gian hiệu lực, mức đơn tối thiểu, mức giảm tối đa tùy chọn và giới hạn tổng lượt tùy chọn.
- Khách tự tạo tài khoản tại `/goldengate/customer/register` bằng họ tên, số điện thoại và mật khẩu (tối thiểu 8 ký tự); tài khoản được đăng nhập ngay sau khi tạo.
- Để tránh chiếm hồ sơ khách hiện có, số điện thoại đã đăng ký trong hệ thống không thể tự tạo tài khoản mới hoặc tự liên kết. Nhân viên/admin cần hỗ trợ các hồ sơ cũ chưa có mật khẩu hoặc khách không truy cập được tài khoản.
- Khách đăng nhập bằng số điện thoại tại `/goldengate/customer/login` để xem các voucher đang còn hiệu lực. Mỗi đơn chỉ dùng một voucher; tổng lượt và thời hạn được kiểm tra khi thanh toán.
- Khi thanh toán, gắn hồ sơ khách vào đơn rồi nhập mã voucher. Server kiểm tra lại hiệu lực, lượt dùng và giá trị đơn trong transaction; voucher không cộng dồn với chiết khấu thủ công hoặc đổi điểm.

### Xóa và lưu trữ dữ liệu quản lý

- Tài khoản nhân viên: có thể xóa hẳn nếu chưa gắn với nhân sự, đơn, kho, voucher, chat hoặc sổ điểm. Nếu đã được dùng, thao tác xóa sẽ khóa tài khoản để bảo toàn thông tin người thực hiện; không thể xóa tài khoản đang đăng nhập hoặc admin hoạt động cuối cùng.
- Voucher: xóa hẳn nếu chưa áp dụng cho đơn nào; nếu đã có lượt dùng thì hệ thống khóa mã và giữ lịch sử.
- Chi nhánh: xóa hẳn nếu chưa phát sinh tài khoản, bàn, đặt chỗ, đơn, tồn kho, biến động kho hoặc chat. Nếu có dữ liệu liên quan thì chỉ ngừng hoạt động; không thể xóa chi nhánh hoạt động cuối cùng.
- Hồ sơ khách: xóa hẳn nếu chưa có đơn hàng, đặt bàn, điểm, voucher đổi hoặc chat. Nếu đã có lịch sử thì chuyển sang lưu trữ; có thể khôi phục từ trang khách hàng. Hồ sơ lưu trữ không thể đăng nhập, đặt bàn/gắn đơn mới cho đến khi được khôi phục.
- Các nút xóa gửi bằng POST và yêu cầu CSRF; hộp xác nhận giải thích khi nào thao tác sẽ lưu trữ thay vì xóa hẳn.

## Cấu trúc dự án

```text
goldengate/
├── index.php                 # Front controller: khởi tạo session và dispatch request
├── .htaccess                 # Rewrite URL đẹp về index.php
├── app/
│   ├── controllers/          # Xử lý route, xác thực/phân quyền, điều phối nghiệp vụ
│   ├── models/               # Truy vấn và nghiệp vụ dữ liệu
│   └── views/                # Giao diện nhân viên, trang công khai và layout
├── assets/
│   ├── css/                  # Giao diện
│   ├── js/                   # Tương tác giao diện và chat
│   ├── images/               # Hình/ảnh tĩnh
│   └── uploads/menu/         # Ảnh món được tải lên
├── config/                   # Cấu hình ứng dụng, thanh toán, đặt bàn, điểm
├── core/
│   ├── Router.php            # Phân tích URL theo controller/method/parameter
│   ├── Controller.php        # Render view/layout và kiểm tra đăng nhập/vai trò
│   ├── Database.php          # Kết nối PDO singleton
│   ├── Upload.php            # Kiểm tra và lưu ảnh
│   └── helpers.php           # URL, CSRF, session, định dạng và helper chung
├── database/                 # Schema cài mới và dữ liệu khởi tạo
└── tools/                    # Script Windows sao lưu/khôi phục và hướng dẫn
```

URL nội bộ có dạng `/goldengate/<controller>/<method>/<tham-số>`, ví dụ `/goldengate/auth/login`. `index.php` làm điểm vào duy nhất; router ánh xạ URL tới lớp trong `app/controllers/`. Controller gọi model, sau đó render view qua layout quản trị hoặc layout công khai.

## Cơ sở dữ liệu

Database mặc định là `goldengate_db`, kết nối qua PDO với charset `utf8mb4`. File SQL nền hiện có, `database/goldengate_db.sql`, tạo các bảng:

- Tổ chức và phân quyền: `branches`, `roles`, `users`, `employees`.
- Khách hàng và thực đơn: `customers`, `categories`, `menu_items`, `branch_menu`.
- Voucher: `vouchers`, `voucher_redemptions`; lịch sử lượt dùng được lưu theo đơn.
- Bàn và đặt chỗ: `dining_tables` (có token QR riêng cho từng bàn), `reservations`.
- Bán hàng: `orders`, `order_items`, `payments`.
- Kho: `ingredients`, `inventory`, `stock_movements`.
- Chat khách hàng: `chat_conversations`, `chat_messages`.
- Bảo vệ đăng nhập và tính năng mở rộng: `login_attempts`, `recipes`, `loyalty_ledger`.

Các trường đặt bàn online, cờ đổi mật khẩu, trạng thái lưu trữ, QR, voucher và điểm khách đã có trong schema chính. Schema là bản khởi tạo cho database mới, không phải migration an toàn cho dữ liệu đang chạy.

## Quy trình vận hành dữ liệu

Ứng dụng dùng một database MySQL/MariaDB tên mặc định `goldengate_db`. `core/Database.php` kết nối bằng PDO với `utf8mb4`; request đi qua `index.php`, controller kiểm tra phiên đăng nhập/vai trò/phạm vi chi nhánh, model đọc/ghi dữ liệu và view chỉ hiển thị kết quả. Tài khoản nhân viên và khách dùng chung session của ứng dụng nhưng có vùng đăng nhập riêng. PHP được cấu hình múi giờ `Asia/Ho_Chi_Minh`; hãy đồng bộ múi giờ máy chủ PHP và MySQL để thời gian đặt bàn, thanh toán và báo cáo nhất quán.

### Các nhóm bảng và quan hệ dữ liệu

| Nhóm | Bảng | Dữ liệu chính và liên kết |
|---|---|---|
| Cấu hình chuỗi | `branches`, `roles`, `users`, `employees` | Chi nhánh là phạm vi dữ liệu vận hành. Nhân viên đăng nhập qua `users`, vai trò nằm ở `roles`; hồ sơ làm việc nằm ở `employees`. `users.branch_id` có thể rỗng với admin tổng. |
| Khách hàng | `customers` | Hồ sơ theo số điện thoại; có thể chỉ là hồ sơ nghiệp vụ hoặc có thêm `password_hash` khi khách tự đăng ký. `status` điều khiển hoạt động/lưu trữ. Đơn, đặt bàn, điểm, voucher và chat có thể tham chiếu khách. |
| Thực đơn | `categories`, `menu_items`, `branch_menu` | Món thuộc một danh mục. `base_price` là giá gốc; `branch_menu` lưu giá/tình trạng phục vụ riêng cho từng chi nhánh. Giá món đã thêm vào đơn được chụp lại ở `order_items.unit_price`, vì vậy đổi giá menu không sửa hóa đơn cũ. |
| Bàn và đặt chỗ | `dining_tables`, `reservations` | Bàn thuộc chi nhánh và có `qr_token` riêng. Đặt chỗ có thể được tạo bởi nhân viên hoặc qua trang công khai; yêu cầu online ban đầu chưa được gán bàn và ở trạng thái chờ xác nhận. |
| Bán hàng | `orders`, `order_items`, `payments` | Đơn thuộc chi nhánh, có thể gắn bàn/khách/voucher. Dòng món lưu số lượng, giá tại thời điểm bán, ghi chú và trạng thái bếp. Thanh toán được ghi ở `payments`; đơn đã thanh toán/hủy trở thành lịch sử. |
| Voucher | `vouchers`, `voucher_redemptions` | Mã giảm có thời gian, giá trị đơn tối thiểu và giới hạn lượt tùy chọn. Mỗi lần dùng được lưu cùng khách, đơn và số tiền giảm để kiểm soát lượt dùng và giữ lịch sử. |
| Kho | `ingredients`, `inventory`, `stock_movements`, `recipes` | Danh mục nguyên liệu dùng chung; số tồn và ngưỡng tối thiểu theo từng chi nhánh; mọi nhập/xuất/kiểm kê ghi lịch sử. Công thức nối món với lượng nguyên liệu cần dùng. |
| Điểm thành viên | `loyalty_ledger` | Sổ biến động cộng/trừ/điều chỉnh điểm. Số dư được tính từ tổng `points`; không sửa số dư trực tiếp. Hạng dựa trên tổng điểm đã tích lũy, không giảm hạng khi đổi điểm. |
| Chat và bảo vệ | `chat_conversations`, `chat_messages`, `login_attempts` | Hội thoại/tin nhắn lưu riêng; khách vãng lai có token thay vì bắt buộc tài khoản. `login_attempts` phục vụ giới hạn thử đăng nhập/tra cứu. |

### Luồng dữ liệu nghiệp vụ

1. **Danh mục và giá:** quản lý tạo danh mục, món, ảnh và giá gốc. Món có thể được bật/tắt bán toàn hệ thống; giá và tình trạng có thể điều chỉnh riêng theo chi nhánh. Trang public chỉ hiển thị món đang hoạt động và được phục vụ tại chi nhánh đã chọn.
2. **Đặt bàn:** khách gửi thông tin qua website; hệ thống chuẩn hóa số điện thoại, kiểm tra giờ/sức chứa/giới hạn spam rồi tạo hồ sơ khách nếu số đó chưa có. Yêu cầu online lưu trong `reservations` ở trạng thái `pending`; nhân viên xác nhận, xếp bàn hoặc hủy tại trang quản lý. Khách tra cứu/hủy bằng mã lượt đặt và số điện thoại.
3. **Mở bàn và gọi món:** nhân viên mở bàn để tạo `orders` trạng thái `open`; thêm món sinh `order_items`, lưu giá tại thời điểm thêm và cập nhật tạm tính. Bếp chuyển trạng thái từng dòng `new` → `cooking` → `ready`; nhân viên đánh dấu `served`. Món đã bếp nhận bị giới hạn chỉnh sửa với các vai trò không phải quản lý/admin.
4. **Gọi món qua QR:** token gắn với bàn xác định bàn/chi nhánh. QR không tự mở đơn; nhân viên phải mở đơn trước. Khi khách gửi món, máy chủ xác nhận token, đơn mở, món còn bán và ghi món vào chính đơn đang chạy. Giữ QR ở bàn tương ứng vì URL là quyền truy cập công khai vào chức năng gửi món.
5. **Thanh toán:** server đọc lại các dòng món và tính lại số tiền, giảm giá, VAT và phương thức thanh toán; không tin tổng tiền từ trình duyệt. Trong transaction, đơn được chuyển `paid`, phiếu thu được thêm, bàn được giải phóng và nếu có thì voucher/biến động điểm cũng được ghi. Giá thực đơn là giá chưa VAT; VAT tính trên tạm tính sau giảm giá. Thu ngân chịu giới hạn giảm giá cấu hình; admin/quản lý có quyền cao hơn. Tiền mặt phải đủ số phải thu.
6. **Voucher và điểm:** muốn dùng voucher/điểm thì đơn phải gắn hồ sơ khách. Một đơn dùng voucher không đồng thời dùng giảm giá nhập tay hoặc đổi điểm. Tích điểm và đổi điểm được ghi vào sổ trong transaction thanh toán; điều chỉnh thủ công phải có ghi chú. Các con số quy đổi nằm trong `config/loyalty.php`.
7. **Trừ kho:** sau khi thanh toán hoàn tất, ứng dụng lấy công thức của các món, trừ nguyên liệu còn tồn và ghi `stock_movements`. Thiếu tồn không chặn thanh toán: phần có sẵn được trừ, phần thiếu được báo cảnh báo để nhân viên kiểm kê/nhập kho. Nếu không khai báo công thức thì món không tự tiêu hao nguyên liệu. Nhập kho, xuất kho thủ công và kiểm kê đều cần ghi biến động.
8. **Báo cáo:** doanh thu và số đơn tính từ `orders` đã thanh toán, theo `closed_at`; đơn hủy được thống kê riêng. Biểu đồ giờ/ngày cao điểm căn cứ thời điểm mở đơn (`created_at`). Doanh thu món dựa trên giá đã chụp trong `order_items`, chưa phân bổ chiết khấu/VAT cho từng món. Báo cáo nguyên liệu so sánh mức tiêu hao công thức với phiếu xuất thực tế.

### Bảo toàn và sửa dữ liệu

- Dùng giao diện quản lý để thêm/sửa nghiệp vụ; tránh sửa trực tiếp các bảng lịch sử như `orders`, `order_items`, `payments`, `voucher_redemptions`, `stock_movements` và `loyalty_ledger`.
- Không xóa đơn đã thanh toán để sửa sai số liệu. Đối chiếu hóa đơn, lịch sử và quy trình điều chỉnh nội bộ của nhà hàng; thay đổi SQL không tự cập nhật các sổ phụ liên quan.
- Xóa danh mục/món/nguyên liệu/chi nhánh có thể bị khóa bởi khóa ngoại hoặc làm mất quan hệ lịch sử. Tắt trạng thái bán/hoạt động thay vì xóa nếu dữ liệu đã được sử dụng.
- Xóa nhân viên, voucher, chi nhánh và khách hàng qua chức năng được cung cấp: bản ghi chưa liên quan có thể xóa cứng; bản ghi có lịch sử được khóa/lưu trữ để giữ quan hệ. Hồ sơ khách lưu trữ có thể khôi phục từ trang khách hàng.
- Không xóa trực tiếp dòng `inventory` để đặt lại tồn. Dùng thao tác kiểm kê để cập nhật tồn thực tế và tạo dấu vết trong `stock_movements`.
- Không tự chỉnh `loyalty_ledger` để đặt số dư. Dùng chức năng điều chỉnh điểm để lưu người thao tác, lý do và số điểm thay đổi.
- Trước mọi sửa SQL hoặc xóa nhiều bản ghi: sao lưu, thử trên bản sao, xác nhận khóa ngoại và ghi lại người thao tác/lý do.

## Quy trình vận hành hằng ngày

### Đầu ca

1. Kiểm tra Apache/MySQL đang chạy, đăng nhập tài khoản đúng chi nhánh và mở **Kiểm tra hệ thống** nếu có cảnh báo.
2. Kiểm tra sơ đồ bàn, lượt đặt trong ngày và yêu cầu online đang chờ; xác nhận đặt chỗ và phân bàn.
3. Kiểm tra nguyên liệu sắp hết; đối chiếu tồn thực tế và ghi phiếu nhập/kiểm kê thay vì sửa trực tiếp database.
4. Kiểm tra thực đơn, giá và tình trạng bán tại chi nhánh; cập nhật khi món tạm ngừng phục vụ.

### Trong ca

1. Nhân viên mở bàn để tạo đơn, ghi món và ghi chú yêu cầu khách; bếp cập nhật trạng thái món.
2. Nếu gọi bằng QR, mở đơn tại bàn trước. Khi khách báo đã gửi món nhưng chưa thấy trên bếp, kiểm tra đúng bàn và đơn đang mở.
3. Khi thanh toán, chọn khách nếu cần voucher/điểm, kiểm tra mã giảm, VAT, phương thức và tiền khách đưa; đối chiếu số tiền trước khi hoàn tất.
4. Kiểm tra cảnh báo trừ kho sau thanh toán; xử lý thiếu kho bằng nhập/kiểm kê và rà công thức món.
5. Với sự cố đơn/đặt bàn, tra lịch sử trước khi chỉnh; không xóa dữ liệu lịch sử.

### Cuối ca

1. Đối chiếu báo cáo doanh thu, phương thức thanh toán, đơn hủy và hóa đơn với chứng từ thực tế.
2. Xem biến động kho; ghi nhận chênh lệch bằng kiểm kê và ghi chú nguyên nhân.
3. Kiểm tra yêu cầu đặt bàn và hội thoại khách còn mở.
4. Xác nhận bản sao lưu gần nhất thành công, gồm cả file SQL và ảnh tải lên; định kỳ kiểm thử khôi phục trên bản sao riêng.

## Sao lưu và khôi phục

Công cụ Windows nằm trong `tools/`; đọc thêm `tools/README.txt` và chỉnh các biến đường dẫn, tài khoản MySQL ở đầu từng file trước khi chạy.

- `tools/backup.bat`: dump database và nén ảnh trong `assets/uploads` vào `C:\goldengate_backups`, giữ bản sao lưu 30 ngày.
- `tools/restore.bat`: khôi phục database từ file SQL. Thao tác này **ghi đè dữ liệu hiện tại** và yêu cầu gõ `YES` để xác nhận. Script không tự bung ảnh; nếu cần, bung đúng file ZIP vào `assets/uploads` sau khi database phục hồi thành công.
- `tools/schedule_backup.bat`: đặt lịch chạy sao lưu hằng ngày lúc 03:00; cần chạy bằng quyền Administrator.

### Sao lưu thủ công

1. Mở `tools/backup.bat`, chỉnh `MYSQL_BIN`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DIR` theo máy. Không lưu mật khẩu thật trong nơi chia sẻ công khai.
2. Chạy file và kiểm tra trong `C:\goldengate_backups` có cả `goldengate_YYYYMMDD_HHMM.sql` lẫn `uploads_YYYYMMDD_HHMM.zip`. File SQL chứa database; file ZIP chứa ảnh người dùng đã tải trong `assets/uploads`.
3. Nếu script báo lỗi hoặc thiếu một trong hai file, không coi lần sao lưu đó là đầy đủ. Sao chép bản sao ra ổ đĩa hoặc nơi lưu độc lập; không để bản duy nhất trên cùng máy.
4. Mã nguồn, `config/config.php` và các file cấu hình không nằm trong bộ SQL/ZIP này. Sao lưu chúng riêng, nhưng giữ bí mật các thông tin kết nối database.

### Khôi phục có kiểm soát

1. Dừng thao tác ghi dữ liệu của nhân viên và chạy một bản sao lưu database hiện tại trước khi khôi phục.
2. Mở Command Prompt tại `tools` hoặc gọi `restore.bat` với đường dẫn file SQL làm tham số, ví dụ:
   `restore.bat "C:\goldengate_backups\goldengate_20261028_0300.sql"`
3. Chỉ gõ `YES` sau khi xác nhận đúng file và hiểu rằng database đích sẽ bị ghi đè. File backup được tạo bằng `mysqldump --databases`, nên chứa lệnh chọn/tạo database.
4. Bung ZIP ảnh tương ứng (nếu có) vào thư mục ứng dụng `assets/uploads`, giữ nguyên cấu trúc thư mục; không giải nén đè vào chỗ khác.
5. Mở trang công khai và trang quản lý, kiểm tra ảnh, đăng nhập, đơn gần nhất, khách và báo cáo. Ghi lại thời điểm và người thực hiện.

Script giữ file SQL/ZIP trong 30 ngày theo tuổi từng loại file; kiểm tra rằng cả hai loại còn tồn tại cho các ngày cần khôi phục. Đặt lịch chạy bằng quyền Administrator; máy phải bật và MySQL phải hoạt động đúng giờ chạy. Định kỳ thử khôi phục trên máy/database thử nghiệm — không thử trên database sản xuất.

## Xử lý sự cố

| Hiện tượng | Hướng kiểm tra |
|---|---|
| Các đường dẫn nội bộ trả 404 | Bật Apache `mod_rewrite`, cho phép `AllowOverride All`, kiểm tra `.htaccess` và `RewriteBase`. |
| Không kết nối được database | Kiểm tra MySQL đang chạy và `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` trong `config/config.php`. |
| Thiếu bảng hoặc báo lỗi cột | Kiểm tra ứng dụng kết nối đúng `goldengate_db`. Chỉ import schema nền vào database mới; với database đang dùng, sao lưu và làm migration có đối chiếu, không import lại schema nền. |
| Đăng nhập được nhưng bị yêu cầu đổi mật khẩu | Đây là hành vi dự kiến với tài khoản mẫu `admin`/`manager1`; đổi mật khẩu theo yêu cầu hoặc nhờ quản trị viên hỗ trợ. |
| Tải ảnh món thất bại | Bật `fileinfo`, bảo đảm `assets/uploads/menu` ghi được và file là JPG/PNG/WebP không quá 2 MB. |
| Trang kiểm tra hệ thống báo lỗi | Đăng nhập bằng admin, mở menu **Kiểm tra hệ thống** để xem extension, database/bảng và quyền ghi thư mục. |
| Thay CSS/JS nhưng trình duyệt còn bản cũ | Tải lại trang; helper asset tự thêm phiên bản theo thời gian sửa file. |

Khi đang phát triển, `DEBUG` trong `config/config.php` bật hiển thị lỗi. Tắt debug trên môi trường thật và không hiển thị thông tin exception database cho người dùng cuối.

## Lưu ý trước khi triển khai thật

- Đổi mật khẩu mặc định của tất cả tài khoản; không giữ `password` hoặc tài khoản `demo_`.
- Đặt `DEBUG` thành `false`, dùng mật khẩu database mạnh và tài khoản database có quyền tối thiểu cần thiết.
- Cấu hình HTTPS, sao lưu database lẫn ảnh tải lên, và kiểm tra quy trình khôi phục trước khi đưa vào vận hành.
- Không dùng dữ liệu mẫu/tài khoản mặc định cho hoạt động thật; cấp tài khoản riêng theo vai trò và chi nhánh.
- Xác nhận mức VAT, quyền chiết khấu, chính sách đặt bàn và quy đổi điểm với người phụ trách nghiệp vụ.
- Không import lại schema khởi tạo trên database sản xuất. Không có migration tự động cho database cũ; mỗi lần nâng cấp cần kế hoạch sao lưu, thử nghiệm, đối chiếu và hoàn nguyên.
