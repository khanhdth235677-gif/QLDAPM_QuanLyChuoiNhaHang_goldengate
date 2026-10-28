<?php
require_once APP_PATH . '/models/Customer.php';
require_once APP_PATH . '/models/Voucher.php';
require_once APP_PATH . '/models/LoginAttempt.php';

class CustomerController extends Controller
{
    public function index(): void
    {
        if (customer_logged_in()) redirect('customer/account');
        redirect('customer/login');
    }

    public function register(): void
    {
        if (customer_logged_in()) redirect('customer/account');
        $form = ['full_name' => '', 'phone' => ''];
        if ($this->isPost()) {
            csrf_check();
            $name = trim($this->input('full_name'));
            $phone = normalize_phone($this->input('phone'));
            $password = $this->input('password');
            $confirm = $this->input('confirm');
            $form = ['full_name' => $name, 'phone' => $phone];

            $err = null;
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err = 'Họ tên phải có từ 2 đến 100 ký tự.';
            elseif (!valid_phone($phone)) $err = 'Số điện thoại không hợp lệ. Ví dụ: 0901234567.';
            elseif (strlen($password) < 8 || strlen($password) > 72) $err = 'Mật khẩu phải có từ 8 đến 72 ký tự.';
            elseif ($password !== $confirm) $err = 'Xác nhận mật khẩu không khớp.';

            if (!$err) {
                $customerId = Customer::registerAccount($name, $phone, $password);
                if ($customerId === false) {
                    $err = 'Số điện thoại này đã có hồ sơ khách hàng. Vui lòng liên hệ nhân viên để hỗ trợ đăng nhập hoặc liên kết tài khoản.';
                } else {
                    $account = Customer::accountById($customerId);
                    if (!$account) throw new RuntimeException('Không thể tải tài khoản khách hàng vừa tạo.');
                    session_regenerate_id(true);
                    $_SESSION['customer'] = [
                        'id' => (int)$account['id'], 'full_name' => $account['full_name'],
                        'auth_hash' => $account['password_hash'],
                    ];
                    flash('success', 'Tạo tài khoản thành công. Chào mừng ' . $account['full_name'] . '!');
                    redirect('customer/account');
                }
            }
            flash('error', $err);
        }
        $this->publicView('customer/register', ['title' => 'Tạo tài khoản khách hàng', 'form' => $form]);
    }

    public function login(): void
    {
        if (customer_logged_in()) redirect('customer/account');
        $phone = '';
        if ($this->isPost()) {
            csrf_check();
            $phone = normalize_phone((string)($_POST['phone'] ?? ''));
            $attemptKey = 'customer:' . $phone;
            $ip = client_ip();
            if (!valid_phone($phone)) {
                flash('error', 'Số điện thoại hoặc mật khẩu không đúng.');
            } elseif (LoginAttempt::isBlocked($attemptKey, $ip)) {
                flash('error', 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng đợi 15 phút.');
            } else {
                $account = Customer::accountByPhone($phone);
                if (!$account || !(int)$account['status'] || !$account['password_hash'] || !password_verify((string)($_POST['password'] ?? ''), $account['password_hash'])) {
                    LoginAttempt::record($attemptKey, $ip);
                    flash('error', 'Số điện thoại hoặc mật khẩu không đúng.');
                } else {
                    LoginAttempt::clear($attemptKey, $ip);
                    session_regenerate_id(true);
                    $_SESSION['customer'] = [
                        'id' => (int)$account['id'], 'full_name' => $account['full_name'],
                        'auth_hash' => $account['password_hash'],
                    ];
                    redirect(!empty($account['must_change_password']) ? 'customer/password' : 'customer/account');
                }
            }
        }
        $this->publicView('customer/login', ['title' => 'Khách hàng đăng nhập', 'phone' => $phone]);
    }

    public function account(): void
    {
        $account = $this->requireCustomer();
        if ((int)$account['must_change_password']) redirect('customer/password');
        $this->publicView('customer/account', [
            'title' => 'Tài khoản khách hàng',
            'customer' => $account,
            'vouchers' => Voucher::available(),
        ]);
    }

    public function password(): void
    {
        $account = $this->requireCustomer();
        if ($this->isPost()) {
            csrf_check();
            $current = (string)($_POST['current'] ?? '');
            $new = (string)($_POST['new'] ?? '');
            $confirm = (string)($_POST['confirm'] ?? '');
            $stored = Customer::accountByPhone($account['phone']);
            $err = null;
            if (!$stored || !password_verify($current, $stored['password_hash'])) $err = 'Mật khẩu hiện tại không đúng.';
            elseif (mb_strlen($new) < 8 || strlen($new) > 72) $err = 'Mật khẩu mới phải có từ 8 đến 72 ký tự.';
            elseif ($new !== $confirm) $err = 'Xác nhận mật khẩu không khớp.';
            elseif ($new === $current) $err = 'Mật khẩu mới phải khác mật khẩu hiện tại.';

            if ($err) {
                flash('error', $err);
                redirect('customer/password');
            }
            $_SESSION['customer']['auth_hash'] = Customer::setAccountPassword((int)$account['id'], $new, false);
            session_regenerate_id(true);
            flash('success', 'Đã đổi mật khẩu thành công.');
            redirect('customer/account');
        }
        $this->publicView('customer/password', [
            'title' => 'Đổi mật khẩu', 'forced' => (bool)$account['must_change_password'],
        ]);
    }

    public function logout(): void
    {
        $this->postOnly('site');
        unset($_SESSION['customer']);
        session_regenerate_id(true);
        flash('success', 'Bạn đã đăng xuất khỏi tài khoản khách hàng.');
        redirect('site');
    }

    private function requireCustomer(): array
    {
        $id = (int)(customer_user()['id'] ?? 0);
        $account = $id > 0 ? Customer::accountById($id) : false;
        if (!$account || !(int)$account['status'] || !isset(customer_user()['auth_hash']) || !hash_equals((string)$account['password_hash'], (string)customer_user()['auth_hash'])) {
            unset($_SESSION['customer']);
            redirect('customer/login');
        }
        return $account;
    }

    private function input(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_scalar($value) ? (string)$value : '';
    }
}
