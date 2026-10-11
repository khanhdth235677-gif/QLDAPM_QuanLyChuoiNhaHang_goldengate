<?php
require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/LoginAttempt.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if (is_logged_in()) redirect('dashboard');

        if ($this->isPost()) {
            csrf_check();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $key = mb_substr(strtolower($username), 0, 50);
            $ip  = client_ip();

            if (LoginAttempt::isBlocked($key, $ip)) {
                flash('error', 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ' . LoginAttempt::WINDOW_MIN . ' phút.');
                redirect('auth/login');
            }

            $u = $username !== '' ? User::findByUsername($username) : false;
            if ($u && (int)$u['status'] === 1 && password_verify($password, $u['password_hash'])) {
                LoginAttempt::clear($key, $ip);
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'          => (int)$u['id'],
                    'username'    => $u['username'],
                    'full_name'   => $u['full_name'],
                    'role'        => $u['role_code'],
                    'role_name'   => $u['role_name'],
                    'branch_id'   => $u['branch_id'] !== null ? (int)$u['branch_id'] : null,
                    'branch_name' => $u['branch_name'],
                    'must_change' => !empty($u['must_change_password']),
                ];
                User::touchLogin((int)$u['id']);
                redirect($_SESSION['user']['must_change'] ? 'account/password' : 'dashboard');
            }

            LoginAttempt::record($key, $ip);   // ghi cả khi tên đăng nhập không tồn tại
            flash('error', 'Sai tên đăng nhập hoặc mật khẩu.');
            redirect('auth/login');
        }

        $this->view('auth/login', ['title' => 'Đăng nhập'], false);
    }

    public function logout(): void
    {
        $this->postOnly();
        session_unset();
        session_destroy();
        session_start();
        flash('success', 'Bạn đã đăng xuất.');
        redirect('auth/login');
    }
}
