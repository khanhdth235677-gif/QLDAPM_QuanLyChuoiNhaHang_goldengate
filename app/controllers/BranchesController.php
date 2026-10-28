<?php
require_once APP_PATH . '/models/Branch.php';

// Quản lý chi nhánh: chỉ admin tổng
class BranchesController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');
        $this->view('branches/index', ['title' => 'Chi nhánh', 'branches' => Branch::all()]);
    }

    public function create(): void
    {
        $this->requireRole('admin');
        $branch = ['name' => '', 'address' => '', 'phone' => ''];

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read();
            $branch = $_POST + $branch;
            if (!$err) {
                Branch::create($data);
                flash('success', 'Đã thêm chi nhánh ' . $data['name'] . '. Thực đơn đã được sao chép sang chi nhánh mới, hãy thêm bàn và tài khoản cho chi nhánh.');
                redirect('branches');
            }
            flash('error', $err);
        }
        $this->view('branches/form', ['title' => 'Thêm chi nhánh', 'mode' => 'create', 'branch' => $branch]);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin');
        $branch = Branch::find((int)$id);
        if (!$branch) $this->forbidden();

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read();
            $branch = array_merge($branch, $_POST);
            if (!$err) {
                Branch::update((int)$id, $data);
                flash('success', 'Đã cập nhật chi nhánh ' . $data['name'] . '.');
                redirect('branches');
            }
            flash('error', $err);
        }
        $this->view('branches/form', ['title' => 'Sửa chi nhánh', 'mode' => 'edit', 'branch' => $branch]);
    }

    public function toggle(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('branches');
        $branch = Branch::find((int)$id);
        if (!$branch) $this->forbidden();

        $new = (int)$branch['status'] === 1 ? 0 : 1;
        Branch::setStatus((int)$id, $new);
        flash('success', ($new ? 'Đã kích hoạt lại ' : 'Đã ngừng hoạt động ') . $branch['name'] . '.');
        redirect('branches');
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('branches');
        $branch = Branch::find((int)$id);
        if (!$branch) $this->forbidden();
        if ((int)$branch['status'] === 1 && Branch::activeCount() <= 1) {
            flash('error', 'Không thể xóa chi nhánh đang hoạt động cuối cùng.');
            redirect('branches');
        }

        $deleted = Branch::deleteOrDeactivate((int)$id);
        flash('success', $deleted
            ? 'Đã xóa chi nhánh ' . $branch['name'] . ' vì chưa có dữ liệu phát sinh.'
            : 'Chi nhánh ' . $branch['name'] . ' có dữ liệu liên quan nên đã được ngừng hoạt động để giữ lịch sử.');
        redirect('branches');
    }

    private function read(): array
    {
        $name    = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');

        if ($name === '' || mb_strlen($name) > 100)       return [null, 'Tên chi nhánh không hợp lệ (tối đa 100 ký tự).'];
        if ($address === '' || mb_strlen($address) > 255) return [null, 'Địa chỉ không hợp lệ (tối đa 255 ký tự).'];
        if ($phone !== '' && !preg_match('/^[0-9+\-\s().]{6,20}$/', $phone)) return [null, 'Số điện thoại không hợp lệ.'];
        return [['name' => $name, 'address' => $address, 'phone' => $phone !== '' ? $phone : null], null];
    }
}
