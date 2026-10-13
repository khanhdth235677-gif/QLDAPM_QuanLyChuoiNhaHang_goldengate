<?php
require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Employee.php';

// Hồ sơ nhân viên: admin quản lý toàn chuỗi, quản lý chi nhánh chỉ quản lý nhân viên chi nhánh mình
class EmployeesController extends Controller
{
    const POSITIONS = ['Quản lý', 'Thu ngân', 'Phục vụ', 'Đầu bếp', 'Phụ bếp', 'Pha chế', 'Bảo vệ', 'Tạp vụ'];

    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $branchId = $isAdmin ? (int)($_GET['branch'] ?? 0) : (int)$u['branch_id'];
        $q  = trim($_GET['q'] ?? '');
        $st = $_GET['st'] ?? 'active';
        if (!in_array($st, ['active', 'inactive', 'all'], true)) $st = 'active';

        $this->view('employees/index', [
            'title' => 'Nhân viên', 'rows' => Employee::list($branchId ?: null, $q, $st),
            'stats' => Employee::stats($branchId ?: null), 'isAdmin' => $isAdmin,
            'branches' => $isAdmin ? Branch::activeList() : [], 'branchId' => $branchId, 'q' => $q, 'st' => $st,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin', 'manager');
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $branches = Branch::activeList();
        if (!$branches) { flash('error', 'Chưa có chi nhánh nào.'); redirect('branches'); }

        $defaultBranch = $isAdmin ? (int)($_GET['branch'] ?? $branches[0]['id']) : (int)$u['branch_id'];
        $emp = ['branch_id' => $defaultBranch, 'full_name' => '', 'phone' => '', 'position' => '',
                'salary' => '', 'hired_date' => date('Y-m-d'), 'user_id' => 0];

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read($u, $branches, null);
            $emp = array_merge($emp, $_POST);
            if (!$err) {
                Employee::create($data);
                flash('success', 'Đã thêm nhân viên ' . $data['full_name'] . '.');
                redirect('employees' . ($isAdmin ? '?branch=' . $data['branch_id'] : ''));
            }
            flash('error', $err);
        }
        $this->form('create', 'Thêm nhân viên', $emp, $branches, $u, null);
    }

    public function edit(string $id = ''): void
    {
        $emp = $this->loadEmployee($id);
        $u = auth_user();
        $branches = Branch::activeList();
        $ids = array_map(fn($b) => (int)$b['id'], $branches);
        if (!in_array((int)$emp['branch_id'], $ids, true)) {      // chi nhánh hiện tại đã ngừng hoạt động
            $cur = Branch::find((int)$emp['branch_id']);
            if ($cur) $branches[] = ['id' => $cur['id'], 'name' => $cur['name'] . ' (ngừng)'];
        }

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read($u, $branches, (int)$emp['id']);
            $emp = array_merge($emp, $_POST);
            if (!$err) {
                Employee::update((int)$id, $data);
                flash('success', 'Đã cập nhật nhân viên ' . $data['full_name'] . '.');
                redirect('employees' . ($u['role'] === 'admin' ? '?branch=' . $data['branch_id'] : ''));
            }
            flash('error', $err);
        }
        $this->form('edit', 'Sửa nhân viên', $emp, $branches, $u, (int)$emp['id']);
    }

    // Nghỉ việc / đi làm lại. Khi nghỉ việc, tài khoản đăng nhập gắn với nhân viên bị khóa
    public function toggle(string $id = ''): void
    {
        $emp = $this->loadEmployee($id);
        $this->postOnly('employees');
        $actor = auth_user();

        $new = (int)$emp['status'] === 1 ? 0 : 1;
        Employee::setStatus((int)$id, $new);

        $msg = $new ? 'Đã cho ' . $emp['full_name'] . ' đi làm lại. Nếu có tài khoản đăng nhập, hãy mở khóa ở mục Tài khoản.'
                    : 'Đã đánh dấu ' . $emp['full_name'] . ' nghỉ việc.';

        if (!$new && $emp['user_id']) {
            $linked = User::findById((int)$emp['user_id']);
            if ($linked && (int)$linked['id'] !== (int)$actor['id'] && User::canManage($actor, $linked)) {
                User::setStatus((int)$linked['id'], 0);
                $msg .= ' Tài khoản ' . $linked['username'] . ' đã bị khóa.';
            } else {
                $msg .= ' Hãy kiểm tra và khóa tài khoản đăng nhập của nhân viên này ở mục Tài khoản.';
            }
        }
        flash('success', $msg);
        redirect('employees' . ($actor['role'] === 'admin' ? '?branch=' . (int)$emp['branch_id'] : ''));
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin');
        $emp = $this->loadEmployee($id);
        $this->postOnly('employees');
        Employee::delete((int)$id);
        flash('success', 'Đã xóa hồ sơ nhân viên ' . $emp['full_name'] . '. Tài khoản đăng nhập (nếu có) vẫn được giữ.');
        redirect('employees?branch=' . (int)$emp['branch_id']);
    }

    // ---------- nội bộ ----------
    private function loadEmployee(string $id): array
    {
        $this->requireRole('admin', 'manager');
        $emp = Employee::find((int)$id);
        if (!$emp) $this->forbidden();
        $u = auth_user();
        if ($u['role'] === 'manager' && (int)$emp['branch_id'] !== (int)$u['branch_id']) $this->forbidden();
        return $emp;
    }

    private function form(string $mode, string $title, array $emp, array $branches, array $u, ?int $exceptId): void
    {
        $isAdmin = $u['role'] === 'admin';
        $linkable = Employee::linkableUsers($isAdmin ? null : (int)$u['branch_id'], $exceptId, !$isAdmin);
        $this->view('employees/form', [
            'title' => $title, 'mode' => $mode, 'emp' => $emp, 'branches' => $branches,
            'linkable' => $linkable, 'isAdmin' => $isAdmin, 'actor' => $u, 'positions' => self::POSITIONS,
        ]);
    }

    // Kiểm tra dữ liệu form. Trả về [dữ liệu sạch, lỗi]
    private function read(array $actor, array $branches, ?int $exceptId): array
    {
        $name     = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $salary   = preg_replace('/\D/', '', (string)($_POST['salary'] ?? ''));
        $hired    = trim($_POST['hired_date'] ?? '');
        $userId   = (int)($_POST['user_id'] ?? 0);

        if ($name === '' || mb_strlen($name) > 100)     return [null, 'Họ tên không hợp lệ (tối đa 100 ký tự).'];
        if ($position === '' || mb_strlen($position) > 50) return [null, 'Chức vụ không hợp lệ (tối đa 50 ký tự).'];
        if ($phone !== '' && !preg_match('/^[0-9+\-\s().]{6,20}$/', $phone)) return [null, 'Số điện thoại không hợp lệ.'];
        if ($salary === '' || (int)$salary > 1000000000) return [null, 'Lương không hợp lệ.'];

        if ($hired !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $hired);
            if (!$d || $d->format('Y-m-d') !== $hired || $d > new DateTime('today')) return [null, 'Ngày vào làm không hợp lệ.'];
        }

        // Chi nhánh: quản lý luôn là chi nhánh của mình, admin chọn trong danh sách
        if ($actor['role'] === 'manager') {
            $branchId = (int)$actor['branch_id'];
        } else {
            $branchId = (int)($_POST['branch_id'] ?? 0);
            $ok = false;
            foreach ($branches as $b) if ((int)$b['id'] === $branchId) $ok = true;
            if (!$ok) return [null, 'Vui lòng chọn chi nhánh.'];
        }

        // Tài khoản đăng nhập (không bắt buộc)
        if ($userId > 0) {
            $target = User::findById($userId);
            if (!$target || !User::canManage($actor, $target))        return [null, 'Tài khoản đăng nhập không hợp lệ.'];
            if ((int)$target['branch_id'] !== $branchId)              return [null, 'Tài khoản đăng nhập phải thuộc cùng chi nhánh với nhân viên.'];
            if (Employee::userLinked($userId, $exceptId))             return [null, 'Tài khoản này đã được gắn cho nhân viên khác.'];
        }

        return [[
            'user_id' => $userId > 0 ? $userId : null, 'branch_id' => $branchId, 'full_name' => $name,
            'phone' => $phone !== '' ? $phone : null, 'position' => $position, 'salary' => (int)$salary,
            'hired_date' => $hired !== '' ? $hired : null,
        ], null];
    }
}
