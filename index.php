<?php
// Front controller: mọi request đi qua file này
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');

require ROOT_PATH . '/config/config.php';
require ROOT_PATH . '/core/helpers.php';
require ROOT_PATH . '/core/Database.php';
require ROOT_PATH . '/core/Controller.php';
require ROOT_PATH . '/core/Router.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Tự đăng xuất khi không hoạt động quá SESSION_TIMEOUT giây
if (isset($_SESSION['user']) && time() - ($_SESSION['last_activity'] ?? time()) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    flash('error', 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
}
$_SESSION['last_activity'] = time();

(new Router())->dispatch();
