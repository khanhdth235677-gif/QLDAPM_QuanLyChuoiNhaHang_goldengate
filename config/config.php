<?php
define('APP_NAME', 'Golden Gate');
define('BASE_URL', '/goldengate');        // đổi nếu đặt tên thư mục khác trong htdocs

define('DB_HOST', 'localhost');
define('DB_NAME', 'goldengate_db');
define('DB_USER', 'root');                // XAMPP mặc định
define('DB_PASS', '');                    // XAMPP mặc định để trống

define('DEBUG', true);                    // đặt false khi đưa lên môi trường thật
define('SESSION_TIMEOUT', 1800);          // 30 phút

date_default_timezone_set('Asia/Ho_Chi_Minh');
mb_internal_encoding('UTF-8');

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
