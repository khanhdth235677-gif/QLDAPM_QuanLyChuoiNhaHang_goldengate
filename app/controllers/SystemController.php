<?php
// Kiểm tra sức khỏe hệ thống trước khi bàn giao hoặc chạy thật. Chỉ admin
class SystemController extends Controller
{
    // [tên bảng => file SQL cần chạy nếu thiếu]
    const TABLES = [
        'branches' => 'goldengate_db_v2_cai_moi.sql', 'roles' => 'goldengate_db_v2_cai_moi.sql', 'users' => 'goldengate_db_v2_cai_moi.sql',
        'employees' => 'goldengate_db_v2_cai_moi.sql', 'customers' => 'goldengate_db_v2_cai_moi.sql', 'categories' => 'goldengate_db_v2_cai_moi.sql',
        'menu_items' => 'goldengate_db_v2_cai_moi.sql', 'branch_menu' => 'goldengate_db_v2_cai_moi.sql', 'dining_tables' => 'goldengate_db_v2_cai_moi.sql',
        'reservations' => 'goldengate_db_v2_cai_moi.sql', 'orders' => 'goldengate_db_v2_cai_moi.sql', 'order_items' => 'goldengate_db_v2_cai_moi.sql',
        'payments' => 'goldengate_db_v2_cai_moi.sql', 'ingredients' => 'goldengate_db_v2_cai_moi.sql', 'inventory' => 'goldengate_db_v2_cai_moi.sql',
        'stock_movements' => 'goldengate_db_v2_cai_moi.sql', 'chat_conversations' => 'goldengate_db_v2_cai_moi.sql', 'chat_messages' => 'goldengate_db_v2_cai_moi.sql',
        'login_attempts' => 'migration_giai_doan_3.sql', 'recipes' => 'migration_giai_doan_6b.sql', 'loyalty_ledger' => 'migration_giai_doan_8c.sql',
    ];
    // [bảng => [cột => file SQL cần chạy nếu thiếu]]
    const COLUMNS = [
        'users' => ['must_change_password' => 'migration_giai_doan_3.sql'],
        'reservations' => ['source' => 'migration_giai_doan_8a.sql', 'ip' => 'migration_giai_doan_8a.sql'],
    ];

    public function index(): void
    {
        $this->requireRole('admin');
        $groups = $this->run();
        $count = ['ok' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($groups as $g) foreach ($g['items'] as $i) $count[$i['status']]++;
        $this->view('system/index', ['title' => 'Kiểm tra hệ thống', 'groups' => $groups, 'count' => $count]);
    }

    private function item(string $status, string $name, string $detail, string $fix = ''): array
    {
        return ['status' => $status, 'name' => $name, 'detail' => $detail, 'fix' => $fix];
    }

    private function run(): array
    {
        $pdo = Database::get();
        $groups = [];

        // ----- 1. Môi trường chạy -----
        $env = [];
        $env[] = PHP_VERSION_ID >= 80000
            ? $this->item('ok', 'Phiên bản PHP', 'PHP ' . PHP_VERSION)
            : $this->item('fail', 'Phiên bản PHP', 'PHP ' . PHP_VERSION . ' quá cũ', 'Dự án cần PHP 8.0 trở lên. Cập nhật XAMPP lên bản mới.');
        foreach (['pdo_mysql' => 'Kết nối MySQL', 'mbstring' => 'Xử lý tiếng Việt', 'fileinfo' => 'Kiểm tra ảnh tải lên', 'json' => 'JSON'] as $ext => $label) {
            $env[] = extension_loaded($ext)
                ? $this->item('ok', 'Extension ' . $ext, $label)
                : $this->item('fail', 'Extension ' . $ext, 'Chưa bật (' . $label . ')', 'Mở php.ini, bỏ dấu ; trước dòng extension=' . $ext . ' rồi khởi động lại Apache.');
        }
        $env[] = $this->item('ok', 'Múi giờ PHP', date_default_timezone_get() . ', giờ máy chủ hiện tại ' . date('H:i d/m/Y'));
        $groups[] = ['title' => 'Môi trường chạy', 'items' => $env];

        // ----- 2. Cấu hình và bảo mật -----
        $sec = [];
        $sec[] = DEBUG
            ? $this->item('warn', 'Chế độ DEBUG', 'Đang BẬT: lỗi hệ thống hiện chi tiết trên màn hình.', 'Khi chạy thật, đặt define(\'DEBUG\', false) trong config/config.php.')
            : $this->item('ok', 'Chế độ DEBUG', 'Đã tắt, lỗi không lộ chi tiết cho người dùng.');
        $sec[] = (DB_USER === 'root' && DB_PASS === '')
            ? $this->item('warn', 'Tài khoản MySQL', 'Đang dùng root không mật khẩu (mặc định của XAMPP).', 'Khi chạy thật: đặt mật khẩu cho MySQL hoặc tạo tài khoản riêng chỉ có quyền trên goldengate_db (xem tài liệu vận hành).')
            : $this->item('ok', 'Tài khoản MySQL', 'Đã dùng tài khoản/mật khẩu riêng.');
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = strtolower($_SERVER['SERVER_NAME'] ?? '');
        if ($https)                                         $sec[] = $this->item('ok', 'HTTPS', 'Đang dùng kết nối mã hóa.');
        elseif (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) $sec[] = $this->item('ok', 'HTTPS', 'Đang chạy cục bộ (localhost), chưa cần.');
        else                                                $sec[] = $this->item('fail', 'HTTPS', 'Trang đang chạy bằng HTTP trên ' . $host . ': mật khẩu và dữ liệu khách đi qua mạng không mã hóa.', 'Cài chứng chỉ SSL và chuyển sang https trước khi mở cho khách.');

        $weak = []; $demo = [];
        foreach ($pdo->query('SELECT username, password_hash, status FROM users') as $u) {
            if ((int)$u['status'] === 1 && password_verify('password', $u['password_hash'])) {
                if (strpos($u['username'], 'demo_') === 0) $demo[] = $u['username']; else $weak[] = $u['username'];
            }
        }
        $sec[] = $weak
            ? $this->item('fail', 'Mật khẩu mặc định', 'Còn tài khoản dùng mật khẩu mặc định "password": ' . implode(', ', $weak) . '.', 'Đăng nhập từng tài khoản và đổi mật khẩu, hoặc vào Tài khoản > Đặt lại MK.')
            : $this->item('ok', 'Mật khẩu mặc định', 'Không còn tài khoản thật nào dùng mật khẩu mặc định.');
        $nobranch = (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code <> 'admin' AND u.branch_id IS NULL AND u.status = 1")->fetchColumn();
        $sec[] = $nobranch
            ? $this->item('warn', 'Tài khoản chưa gán chi nhánh', $nobranch . ' tài khoản không phải admin mà chưa thuộc chi nhánh nào (sẽ không dùng được).', 'Vào Tài khoản > Sửa để gán chi nhánh.')
            : $this->item('ok', 'Tài khoản chưa gán chi nhánh', 'Không có.');

        $guard = [];
        foreach (['app', 'config', 'core', 'database', 'tools'] as $d) if (!is_file(ROOT_PATH . '/' . $d . '/.htaccess')) $guard[] = $d;
        $sec[] = $guard
            ? $this->item('fail', 'Chặn truy cập thư mục nhạy cảm', 'Thiếu file .htaccess trong: ' . implode(', ', $guard) . '.', 'Chép lại đầy đủ dự án. Mỗi thư mục này cần file .htaccess chứa dòng Require all denied.')
            : $this->item('ok', 'Chặn truy cập thư mục nhạy cảm', 'Các thư mục app, config, core, database, tools đều có .htaccess chặn truy cập từ trình duyệt.');
        $groups[] = ['title' => 'Cấu hình và bảo mật', 'items' => $sec];

        // ----- 3. Cơ sở dữ liệu -----
        $db = [];
        $have = array_flip(array_map('strtolower', $pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(PDO::FETCH_COLUMN)));
        $missingTables = [];
        foreach (self::TABLES as $t => $file) if (!isset($have[$t])) $missingTables[$t] = $file;
        $db[] = $missingTables
            ? $this->item('fail', 'Các bảng dữ liệu', 'Thiếu bảng: ' . implode(', ', array_keys($missingTables)) . '.', 'Chạy file SQL: ' . implode(', ', array_unique($missingTables)) . ' trong phpMyAdmin.')
            : $this->item('ok', 'Các bảng dữ liệu', 'Đủ ' . count(self::TABLES) . ' bảng.');

        $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $missingCols = [];
        foreach (self::COLUMNS as $t => $cols) foreach ($cols as $c => $file) {
            $st->execute([$t, $c]);
            if ((int)$st->fetchColumn() === 0) $missingCols[$t . '.' . $c] = $file;
        }
        $db[] = $missingCols
            ? $this->item('fail', 'Các cột bổ sung', 'Thiếu cột: ' . implode(', ', array_keys($missingCols)) . '.', 'Chạy file SQL: ' . implode(', ', array_unique($missingCols)) . ' trong phpMyAdmin.')
            : $this->item('ok', 'Các cột bổ sung', 'Đủ (must_change_password, reservations.source, reservations.ip).');

        $counts = [];
        foreach (['branches' => 'chi nhánh', 'users' => 'tài khoản', 'menu_items' => 'món', 'dining_tables' => 'bàn', 'orders' => 'đơn', 'customers' => 'khách'] as $t => $label) {
            if (isset($have[$t])) $counts[] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() . ' ' . $label;
        }
        $db[] = $this->item('ok', 'Số liệu hiện có', implode(', ', $counts));

        $demoUsers = isset($have['users']) ? (int)$pdo->query("SELECT COUNT(*) FROM users WHERE username LIKE 'demo\\_%'")->fetchColumn() : 0;
        $db[] = $demoUsers
            ? $this->item('warn', 'Dữ liệu demo', 'Còn ' . $demoUsers . ' tài khoản demo (demo_...) và đơn hàng mẫu.', 'Trước khi chạy thật, chạy xoa_du_lieu_demo.sql để dọn dữ liệu thử nghiệm.')
            : $this->item('ok', 'Dữ liệu demo', 'Không có dữ liệu demo.');
        $groups[] = ['title' => 'Cơ sở dữ liệu', 'items' => $db];

        // ----- 4. Thư mục và tệp -----
        $fs = [];
        $up = ROOT_PATH . '/assets/uploads/menu';
        $fs[] = (is_dir($up) && is_writable($up))
            ? $this->item('ok', 'Thư mục ảnh món', 'assets/uploads/menu ghi được.')
            : $this->item('fail', 'Thư mục ảnh món', 'assets/uploads/menu không tồn tại hoặc không ghi được.', 'Tạo thư mục này và cấp quyền ghi cho tài khoản chạy Apache.');
        $fs[] = is_file(ROOT_PATH . '/assets/uploads/.htaccess')
            ? $this->item('ok', 'Chặn chạy mã trong thư mục tải lên', 'assets/uploads/.htaccess có mặt.')
            : $this->item('fail', 'Chặn chạy mã trong thư mục tải lên', 'Thiếu assets/uploads/.htaccess: kẻ xấu có thể đội lốt file PHP thành ảnh.', 'Chép lại file assets/uploads/.htaccess từ bản dự án.');
        $fs[] = is_file(ROOT_PATH . '/tools/backup.bat')
            ? $this->item('ok', 'Công cụ sao lưu', 'tools/backup.bat có mặt. Hãy chắc chắn đã đặt lịch chạy tự động (xem tài liệu vận hành).')
            : $this->item('warn', 'Công cụ sao lưu', 'Không thấy tools/backup.bat.', 'Chép thư mục tools từ bản dự án mới nhất.');
        $groups[] = ['title' => 'Thư mục và tệp', 'items' => $fs];

        return $groups;
    }
}
