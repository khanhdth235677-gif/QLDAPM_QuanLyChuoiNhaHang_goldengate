<?php
// Mỗi vai trò thấy một trang tổng quan riêng
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $u = auth_user();
        switch ($u['role']) {
            case 'cashier':
            case 'waiter':  $this->staff($u);   break;
            case 'kitchen': $this->kitchen($u); break;
            default:        $this->management($u);
        }
    }

    // Admin tổng và quản lý chi nhánh
    private function management(array $u): void
    {
        $pdo = Database::get();
        $bid = $u['branch_id'];                  // NULL = admin, xem toàn chuỗi

        $scope  = $bid ? ' AND branch_id = :b' : '';
        $params = $bid ? [':b' => $bid] : [];
        $one = function (string $sql) use ($pdo, $params) {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchColumn();
        };

        $stats = [
            'revenue_today' => (float)$one("SELECT COALESCE(SUM(total),0) FROM orders
                                            WHERE status='paid' AND DATE(closed_at)=CURDATE()$scope"),
            'open_orders'   => (int)$one("SELECT COUNT(*) FROM orders WHERE status='open'$scope"),
            'tables_busy'   => (int)$one("SELECT COUNT(*) FROM dining_tables WHERE status<>'empty'$scope"),
            'tables_total'  => (int)$one("SELECT COUNT(*) FROM dining_tables WHERE 1=1$scope"),
            'branches'      => $bid ? null : (int)$pdo->query('SELECT COUNT(*) FROM branches WHERE status=1')->fetchColumn(),
        ];

        $branchRows = null;
        if (!$bid) {   // admin: bảng so sánh từng chi nhánh
            $branchRows = $pdo->query("SELECT b.name,
                    COALESCE(SUM(CASE WHEN o.status='paid' AND DATE(o.closed_at)=CURDATE() THEN o.total END),0) AS revenue,
                    COUNT(CASE WHEN o.status='open' THEN 1 END) AS open_orders
                FROM branches b LEFT JOIN orders o ON o.branch_id = b.id
                WHERE b.status = 1 GROUP BY b.id, b.name ORDER BY b.id")->fetchAll();
        }

        $this->view('dashboard/index', ['title' => 'Tổng quan', 'stats' => $stats, 'branchRows' => $branchRows]);
    }

    // Thu ngân, phục vụ
    private function staff(array $u): void
    {
        $tables = ['empty' => 0, 'occupied' => 0, 'reserved' => 0];
        $open = 0;
        if ($u['branch_id']) {
            $pdo = Database::get();
            $st = $pdo->prepare('SELECT status, COUNT(*) c FROM dining_tables WHERE branch_id = ? GROUP BY status');
            $st->execute([$u['branch_id']]);
            foreach ($st->fetchAll() as $r) $tables[$r['status']] = (int)$r['c'];

            $st = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE branch_id = ? AND status = 'open'");
            $st->execute([$u['branch_id']]);
            $open = (int)$st->fetchColumn();
        }
        $this->view('dashboard/staff', ['title' => 'Tổng quan', 'tables' => $tables, 'open' => $open]);
    }

    // Bếp
    private function kitchen(array $u): void
    {
        $q = ['new' => 0, 'cooking' => 0];
        if ($u['branch_id']) {
            $st = Database::get()->prepare("SELECT oi.kitchen_status s, COUNT(*) c
                FROM order_items oi JOIN orders o ON o.id = oi.order_id
                WHERE o.branch_id = ? AND o.status = 'open' AND oi.kitchen_status IN ('new','cooking')
                GROUP BY oi.kitchen_status");
            $st->execute([$u['branch_id']]);
            foreach ($st->fetchAll() as $r) $q[$r['s']] = (int)$r['c'];
        }
        $this->view('dashboard/kitchen', ['title' => 'Tổng quan', 'q' => $q]);
    }
}
