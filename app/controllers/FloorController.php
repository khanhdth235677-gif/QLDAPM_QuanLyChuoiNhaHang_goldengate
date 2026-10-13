<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Order.php';

// Sơ đồ bàn: nhìn nhanh bàn nào trống, đang dùng, đã đặt
class FloorController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager', 'cashier', 'waiter');
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        $branches = [];

        if ($isAdmin) {
            $branches = Branch::activeList();
            $bid = (int)($_GET['branch'] ?? 0);
            $valid = false;
            foreach ($branches as $b) if ((int)$b['id'] === $bid) $valid = true;
            if (!$valid) $bid = $branches ? (int)$branches[0]['id'] : 0;
        } else {
            if (!$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }
            $bid = (int)$u['branch_id'];
        }

        $tables = $bid ? Order::floor($bid) : [];
        $count = ['empty' => 0, 'occupied' => 0, 'reserved' => 0];
        foreach ($tables as $t) $count[$t['order_id'] ? 'occupied' : $t['status']]++;

        $this->view('floor/index', [
            'title' => 'Sơ đồ bàn', 'tables' => $tables, 'count' => $count,
            'bid' => $bid, 'branches' => $branches, 'isAdmin' => $isAdmin,
        ]);
    }
}
