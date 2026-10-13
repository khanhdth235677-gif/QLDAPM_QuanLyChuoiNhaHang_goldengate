<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Order.php';

// Lịch sử đơn đã đóng theo ngày, để đối chiếu doanh thu và in lại hóa đơn
class HistoryController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager', 'cashier');
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $date = trim($_GET['date'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) $date = date('Y-m-d');

        $branchId = $isAdmin ? (int)($_GET['branch'] ?? 0) : (int)$u['branch_id'];
        $rows = Order::history($branchId ?: null, $date);

        $paid = 0; $revenue = 0; $cancelled = 0;
        foreach ($rows as $r) {
            if ($r['status'] === 'paid') { $paid++; $revenue += (int)$r['total']; } else $cancelled++;
        }

        $this->view('history/index', [
            'title' => 'Lịch sử đơn', 'rows' => $rows, 'date' => $date, 'isAdmin' => $isAdmin,
            'branches' => $isAdmin ? Branch::activeList() : [], 'branchId' => $branchId,
            'paid' => $paid, 'revenue' => $revenue, 'cancelled' => $cancelled,
        ]);
    }
}
