<?php
require_once APP_PATH . '/models/Customer.php';
require_once APP_PATH . '/models/Loyalty.php';

// Hồ sơ khách hàng thân thiết: danh sách, lịch sử, điểm. Khách dùng chung cho cả chuỗi
class CustomersController extends Controller
{
    const ROLES = ['admin', 'manager', 'cashier'];

    public function index(): void
    {
        $this->requireRole(...self::ROLES);
        $q = trim($_GET['q'] ?? '');
        $sort = in_array($_GET['sort'] ?? '', ['points', 'spent', 'recent', 'name'], true) ? $_GET['sort'] : 'points';
        $tierFilter = (isset($_GET['tier']) && $_GET['tier'] !== '') ? (int)$_GET['tier'] : -1;

        $rows = [];
        $points = 0;
        foreach (Loyalty::search($q, $sort) as $r) {
            $r['tier'] = Loyalty::tier((int)$r['earned']);
            if ($tierFilter >= 0 && (LOYALTY_TIERS[$tierFilter][0] ?? null) !== $r['tier']['name']) continue;
            if ((int)$r['status']) $points += (int)$r['balance'];
            $rows[] = $r;
        }
        $this->view('customers/index', ['title' => 'Khách hàng', 'rows' => $rows, 'q' => $q, 'sort' => $sort, 'tierFilter' => $tierFilter,
                                        'points' => $points, 'on' => Loyalty::enabled()]);
    }

    public function show(string $id = ''): void
    {
        $this->requireRole(...self::ROLES);
        $c = Customer::find((int)$id);
        if (!$c) $this->forbidden();
        $cid = (int)$c['id'];
        $sum = Loyalty::summary($cid);
        $u = auth_user();

        $this->view('customers/show', [
            'title' => 'Khách - ' . $c['full_name'], 'c' => $c, 'sum' => $sum, 'tier' => Loyalty::tier($sum['earned']), 'on' => Loyalty::enabled(),
            'favorites' => Loyalty::topItems($cid, 10), 'orders' => Loyalty::recentOrders($cid, 20), 'ledger' => Loyalty::ledger($cid, 50),
            'canAdjust' => has_role('admin', 'manager') && (int)$c['status'] === 1,
            'myBranch' => (int)($u['branch_id'] ?? 0), 'isAdmin' => $u['role'] === 'admin',
        ]);
    }

    public function account(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('customers/show/' . (int)$id);
        $c = Customer::find((int)$id);
        if (!$c) $this->forbidden();
        $temporaryPassword = random_password(12);
        Customer::setAccountPassword((int)$id, $temporaryPassword, true);
        flash('success', 'Tài khoản khách đã được kích hoạt/cấp lại. Số điện thoại đăng nhập: ' . $c['phone'] . ' · Mật khẩu tạm thời (chỉ hiển thị lần này): ' . $temporaryPassword);
        redirect('customers/show/' . (int)$id);
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('customers');
        $c = Customer::find((int)$id);
        if (!$c) $this->forbidden();

        if (!(int)$c['status']) {
            Customer::setStatus((int)$id, 1);
            flash('success', 'Đã khôi phục hồ sơ khách ' . $c['full_name'] . '.');
        } else {
            $deleted = Customer::deleteOrArchive((int)$id);
            flash('success', $deleted
                ? 'Đã xóa hồ sơ khách ' . $c['full_name'] . ' vì chưa có đơn hàng hoặc dữ liệu liên quan.'
                : 'Hồ sơ khách ' . $c['full_name'] . ' có lịch sử liên quan nên đã được lưu trữ, lịch sử và điểm vẫn được giữ.');
        }
        redirect('customers');
    }

    public function create(): void
    {
        $this->requireRole(...self::ROLES);
        $c = ['full_name' => '', 'phone' => ''];
        if ($this->isPost()) {
            csrf_check();
            [$name, $phone, $err] = $this->read(null);
            $c = ['full_name' => trim($_POST['full_name'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')];
            if (!$err) {
                try {
                    $id = Customer::findOrCreate($name, $phone);
                    flash('success', 'Đã thêm khách ' . $name . '.');
                    redirect('customers/show/' . $id);
                } catch (DomainException $e) {
                    $err = $e->getMessage();
                }
            }
            flash('error', $err);
        }
        $this->view('customers/form', ['title' => 'Thêm khách hàng', 'mode' => 'create', 'c' => $c]);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $c = Customer::find((int)$id);
        if (!$c) $this->forbidden();

        if ($this->isPost()) {
            csrf_check();
            [$name, $phone, $err] = $this->read((int)$id);
            $c = array_merge($c, ['full_name' => trim($_POST['full_name'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')]);
            if (!$err) {
                Customer::update((int)$id, $name, $phone);
                flash('success', 'Đã cập nhật hồ sơ khách.');
                redirect('customers/show/' . (int)$id);
            }
            flash('error', $err);
        }
        $this->view('customers/form', ['title' => 'Sửa khách hàng', 'mode' => 'edit', 'c' => $c]);
    }

    // Cộng hoặc trừ điểm bằng tay (quản lý, admin). Bắt buộc ghi lý do
    public function adjust(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('customers');
        $c = Customer::find((int)$id);
        if (!$c) $this->forbidden();

        $points = (int)($_POST['points'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        $err = null;
        if (!Loyalty::enabled())                 $err = 'Chưa bật tính năng điểm (database chưa có bảng sổ điểm khách hàng).';
        elseif ($points === 0 || abs($points) > 100000) $err = 'Số điểm phải khác 0 và không quá 100.000.';
        elseif (mb_strlen($note) < 3 || mb_strlen($note) > 200) $err = 'Vui lòng ghi lý do điều chỉnh (3 đến 200 ký tự).';
        else $err = Loyalty::adjust((int)$id, $points, $note, (int)auth_user()['id']);

        if ($err) flash('error', $err); else flash('success', ($points > 0 ? 'Đã cộng ' : 'Đã trừ ') . abs($points) . ' điểm.');
        redirect('customers/show/' . (int)$id);
    }

    private function read(?int $exceptId): array
    {
        $name = trim($_POST['full_name'] ?? '');
        $phone = normalize_phone((string)($_POST['phone'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) return [$name, $phone, 'Họ tên không hợp lệ (2 đến 100 ký tự).'];
        if (!valid_phone($phone)) return [$name, $phone, 'Số điện thoại không hợp lệ. Ví dụ: 0901234567.'];
        if (Customer::phoneTaken($phone, $exceptId)) return [$name, $phone, 'Số điện thoại này đã có trong hệ thống.'];
        return [$name, $phone, null];
    }
}
