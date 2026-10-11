<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/DiningTable.php';

// Bàn ăn theo chi nhánh. Mọi vai trò nghiệp vụ được xem; chỉ admin và quản lý được thêm, sửa, xóa
class TablesController extends Controller
{
    const STATUS = ['empty' => 'Trống', 'occupied' => 'Đang dùng', 'reserved' => 'Đã đặt'];

    public function index(): void
    {
        $this->requireRole('admin', 'manager', 'cashier', 'waiter');
        [$bid, $branches, $canPick] = $this->context();
        $this->view('tables/index', [
            'title' => 'Bàn', 'bid' => $bid, 'branches' => $branches, 'canPick' => $canPick,
            'tables' => $bid ? DiningTable::forBranch($bid) : [], 'canEdit' => has_role('admin', 'manager'),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin', 'manager');
        [$bid] = $this->context();
        if (!$bid) { flash('error', 'Chưa có chi nhánh nào.'); redirect('branches'); }
        $table = ['table_name' => '', 'seats' => 4, 'status' => 'empty'];

        if ($this->isPost()) {
            csrf_check();
            [$name, $seats, $err] = $this->read($bid, null);
            $table = array_merge($table, $_POST);
            if (!$err) {
                DiningTable::create($bid, $name, $seats);
                flash('success', 'Đã thêm bàn ' . $name . '.');
                redirect('tables?branch=' . $bid);
            }
            flash('error', $err);
        }
        $this->view('tables/form', ['title' => 'Thêm bàn', 'mode' => 'create', 'table' => $table, 'bid' => $bid, 'status' => self::STATUS]);
    }

    public function edit(string $id = ''): void
    {
        $table = $this->loadTable($id);
        $bid = (int)$table['branch_id'];

        if ($this->isPost()) {
            csrf_check();
            [$name, $seats, $err] = $this->read($bid, (int)$table['id']);
            $status = $_POST['status'] ?? '';
            if (!$err && !isset(self::STATUS[$status])) $err = 'Trạng thái không hợp lệ.';
            $table = array_merge($table, $_POST);
            if (!$err) {
                DiningTable::update((int)$id, $name, $seats, $status);
                flash('success', 'Đã cập nhật bàn ' . $name . '.');
                redirect('tables?branch=' . $bid);
            }
            flash('error', $err);
        }
        $this->view('tables/form', ['title' => 'Sửa bàn', 'mode' => 'edit', 'table' => $table, 'bid' => $bid, 'status' => self::STATUS]);
    }

    public function delete(string $id = ''): void
    {
        $table = $this->loadTable($id);
        $this->postOnly('tables');
        $bid = (int)$table['branch_id'];

        if ($table['status'] !== 'empty') {
            flash('error', 'Bàn ' . $table['table_name'] . ' đang có khách hoặc đã được đặt, không thể xóa.');
        } else {
            DiningTable::delete((int)$id);
            flash('success', 'Đã xóa bàn ' . $table['table_name'] . '.');
        }
        redirect('tables?branch=' . $bid);
    }

    public function bulk(): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('tables');
        [$bid] = $this->context();
        if (!$bid) redirect('branches');

        $prefix = trim($_POST['prefix'] ?? '');
        $count  = (int)($_POST['count'] ?? 0);
        $seats  = (int)($_POST['seats'] ?? 0);

        if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $prefix)) flash('error', 'Tiền tố gồm 1-10 chữ hoặc số không dấu, ví dụ B hoặc VIP.');
        elseif ($count < 1 || $count > 50)                flash('error', 'Số lượng bàn phải từ 1 đến 50.');
        elseif ($seats < 1 || $seats > 50)                flash('error', 'Số chỗ ngồi phải từ 1 đến 50.');
        else {
            $added = DiningTable::bulk($bid, $prefix, $count, $seats);
            flash('success', 'Đã thêm ' . $added . ' bàn' . ($added < $count ? ' (bỏ qua ' . ($count - $added) . ' tên đã tồn tại)' : '') . '.');
        }
        redirect('tables?branch=' . $bid);
    }

    // Xác định chi nhánh đang làm việc: admin chọn qua ?branch=, người khác dùng chi nhánh của mình
    private function context(): array
    {
        $u = auth_user();
        if ($u['role'] === 'admin') {
            $branches = Branch::activeList();
            $bid = (int)($_GET['branch'] ?? 0);
            $valid = false;
            foreach ($branches as $b) if ((int)$b['id'] === $bid) $valid = true;
            if (!$valid) $bid = $branches ? (int)$branches[0]['id'] : 0;
            return [$bid, $branches, true];
        }
        if (!$u['branch_id']) {
            flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.');
            redirect('dashboard');
        }
        return [(int)$u['branch_id'], [], false];
    }

    private function loadTable(string $id): array
    {
        $this->requireRole('admin', 'manager');
        $table = DiningTable::find((int)$id);
        if (!$table) $this->forbidden();
        if (has_role('manager') && (int)$table['branch_id'] !== (int)auth_user()['branch_id']) $this->forbidden();
        return $table;
    }

    private function read(int $bid, ?int $exceptId): array
    {
        $name  = trim($_POST['table_name'] ?? '');
        $seats = (int)($_POST['seats'] ?? 0);
        $err = null;
        if ($name === '' || mb_strlen($name) > 30)               $err = 'Tên bàn không hợp lệ (tối đa 30 ký tự).';
        elseif ($seats < 1 || $seats > 50)                       $err = 'Số chỗ ngồi phải từ 1 đến 50.';
        elseif (DiningTable::nameExists($bid, $name, $exceptId)) $err = 'Tên bàn đã tồn tại trong chi nhánh này.';
        return [$name, $seats, $err];
    }
}
