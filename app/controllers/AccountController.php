<?php
require_once APP_PATH . '/models/User.php';

// Tài khoản của chính người đang đăng nhập
class AccountController extends Controller
{
    public function password(): void
    {
        $this->requireLogin();
        $forced = !empty(auth_user()['must_change']);

        if ($this->isPost()) {
            csrf_check();
            $current = $_POST['current'] ?? '';
            $new     = $_POST['new'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            $row     = User::findById(auth_user()['id']);

            $err = null;
            if (!$row || !password_verify($current, $row['password_hash'])) $err = 'Mật khẩu hiện tại không đúng.';
            elseif (mb_strlen($new) < 8)   $err = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
            elseif ($new !== $confirm)     $err = 'Xác nhận mật khẩu không khớp.';
            elseif ($new === $current)     $err = 'Mật khẩu mới phải khác mật khẩu cũ.';

            if ($err) { flash('error', $err); redirect('account/password'); }

            User::setPassword((int)$row['id'], $new, false);
            session_regenerate_id(true);
            $_SESSION['user']['must_change'] = false;
            flash('success', 'Đã đổi mật khẩu thành công.');
            redirect('dashboard');
        }

        $this->view('account/password', ['title' => 'Đổi mật khẩu', 'forced' => $forced]);
    }
}
