<?php
require_once APP_PATH . '/models/User.php';

// Quản lý tài khoản: admin quản lý tất cả, quản lý chi nhánh chỉ quản lý nhân viên chi nhánh mình
class UsersController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        $actor = auth_user();
        $this->view('users/index', [
            'title' => 'Quản lý tài khoản',
            'users' => User::listFor($actor),
            'me'    => $actor['id'],
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin', 'manager');
        $actor    = auth_user();
        $roles    = User::rolesFor($actor);
        $branches = User::branchesFor($actor);
        $user     = ['username' => '', 'full_name' => '', 'role_id' => 0, 'branch_id' => 0];

        if ($this->isPost()) {
            csrf_check();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = ['username' => $username, 'full_name' => trim($_POST['full_name'] ?? ''),
                     'role_id' => (int)($_POST['role_id'] ?? 0), 'branch_id' => (int)($_POST['branch_id'] ?? 0)];

            [$data, $err] = $this->validate($actor, $roles, $branches);
            if (!$err && !preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)) $err = 'Tên đăng nhập 3-50 ký tự, chỉ gồm chữ không dấu, số, dấu chấm hoặc gạch dưới.';
            if (!$err && User::usernameExists($username)) $err = 'Tên đăng nhập đã tồn tại.';
            if (!$err && mb_strlen($password) < 8) $err = 'Mật khẩu ban đầu phải có ít nhất 8 ký tự.';

            if (!$err) {
                User::create($data + ['username' => $username, 'password' => $password]);
                flash('success', 'Đã tạo tài khoản ' . $username . '. Người dùng sẽ phải đổi mật khẩu ở lần đăng nhập đầu.');
                redirect('users');
            }
            flash('error', $err);
        }

        $this->view('users/form', ['title' => 'Thêm tài khoản', 'mode' => 'create', 'user' => $user,
                                   'roles' => $roles, 'branches' => $branches, 'actor' => $actor, 'isSelf' => false]);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $actor  = auth_user();
        $target = User::findById((int)$id);
        if (!$target || !User::canManage($actor, $target)) $this->forbidden();

        $roles    = User::rolesFor($actor);
        $branches = User::branchesFor($actor);
        $isSelf   = (int)$target['id'] === (int)$actor['id'];
        $user     = $target;

        if ($this->isPost()) {
            csrf_check();
            if ($isSelf) {   // không cho tự đổi vai trò hoặc chi nhánh của chính mình
                $_POST['role_id']   = $target['role_id'];
                $_POST['branch_id'] = $target['branch_id'] ?? 0;
            }
            $user['full_name'] = trim($_POST['full_name'] ?? '');
            $user['role_id']   = (int)($_POST['role_id'] ?? 0);
            $user['branch_id'] = (int)($_POST['branch_id'] ?? 0);

            [$data, $err] = $this->validate($actor, $roles, $branches);
            if (!$err) {
                User::update((int)$target['id'], $data);
                flash('success', 'Đã cập nhật tài khoản ' . $target['username'] . '.');
                redirect('users');
            }
            flash('error', $err);
        }

        $this->view('users/form', ['title' => 'Sửa tài khoản', 'mode' => 'edit', 'user' => $user,
                                   'roles' => $roles, 'branches' => $branches, 'actor' => $actor, 'isSelf' => $isSelf]);
    }

    public function toggle(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('users');
        $actor  = auth_user();
        $target = User::findById((int)$id);
        if (!$target || !User::canManage($actor, $target)) $this->forbidden();

        if ((int)$target['id'] === (int)$actor['id']) {
            flash('error', 'Bạn không thể tự khóa tài khoản của mình.');
            redirect('users');
        }
        $new = (int)$target['status'] === 1 ? 0 : 1;
        User::setStatus((int)$target['id'], $new);
        flash('success', ($new ? 'Đã mở khóa ' : 'Đã khóa ') . 'tài khoản ' . $target['username'] . '.');
        redirect('users');
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('users');
        $actor = auth_user();
        $target = User::findById((int)$id);
        if (!$target || !User::canManage($actor, $target)) $this->forbidden();
        if ((int)$target['id'] === (int)$actor['id']) {
            flash('error', 'Bạn không thể xóa tài khoản đang đăng nhập.');
            redirect('users');
        }

        if ($target['role_code'] === 'admin' && (int)$target['status'] === 1) {
            $activeAdmins = (int)Database::get()->query(
                "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'admin' AND u.status = 1"
            )->fetchColumn();
            if ($activeAdmins <= 1) {
                flash('error', 'Không thể xóa tài khoản admin đang hoạt động cuối cùng.');
                redirect('users');
            }
        }

        $deleted = User::deleteOrDeactivate((int)$target['id']);
        flash('success', $deleted
            ? 'Đã xóa tài khoản ' . $target['username'] . '.'
            : 'Tài khoản ' . $target['username'] . ' đã có dữ liệu liên quan nên được khóa để giữ lịch sử.');
        redirect('users');
    }

    public function resetpassword(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('users');
        $actor  = auth_user();
        $target = User::findById((int)$id);
        if (!$target || !User::canManage($actor, $target)) $this->forbidden();

        if ((int)$target['id'] === (int)$actor['id']) {
            flash('error', 'Hãy dùng chức năng Đổi mật khẩu cho tài khoản của chính bạn.');
            redirect('users');
        }
        $temp = random_password();
        User::setPassword((int)$target['id'], $temp, true);
        flash('success', 'Mật khẩu tạm của ' . $target['username'] . ' là: ' . $temp . ' (chỉ hiện một lần, người dùng phải đổi khi đăng nhập).');
        redirect('users');
    }

    // Kiểm tra họ tên, vai trò, chi nhánh. Trả về [dữ liệu sạch, lỗi]
    private function validate(array $actor, array $roles, array $branches): array
    {
        $fullName = trim($_POST['full_name'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 0);

        if ($fullName === '' || mb_strlen($fullName) > 100) return [null, 'Họ tên không hợp lệ.'];

        $role = null;
        foreach ($roles as $r) if ((int)$r['id'] === $roleId) $role = $r;
        if (!$role) return [null, 'Vai trò không hợp lệ.'];

        if ($role['code'] === 'admin') {
            $branchId = null;                       // admin tổng không thuộc chi nhánh nào
        } else {
            if ($actor['role'] === 'manager') $branchId = (int)$actor['branch_id'];
            $ok = false;
            foreach ($branches as $b) if ((int)$b['id'] === $branchId) $ok = true;
            if (!$ok) return [null, 'Vui lòng chọn chi nhánh.'];
        }
        return [['full_name' => $fullName, 'role_id' => $roleId, 'branch_id' => $branchId], null];
    }
}
