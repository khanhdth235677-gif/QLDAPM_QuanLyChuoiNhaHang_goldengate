<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Order.php';

// Màn hình bếp: nhận món, báo xong
class KitchenController extends Controller
{
    const ROLES = ['admin', 'manager', 'kitchen'];

    public function index(): void
    {
        $this->requireRole(...self::ROLES);
        [$bid, $branches] = $this->context();

        // Gom các món theo đơn (bàn)
        $orders = [];
        foreach ($bid ? Order::kitchenQueue($bid) : [] as $r) {
            $oid = (int)$r['order_id'];
            if (!isset($orders[$oid])) $orders[$oid] = ['table' => $r['table_name'], 'minutes' => (int)$r['minutes'], 'lines' => []];
            $orders[$oid]['lines'][] = $r;
        }

        $this->view('kitchen/index', [
            'title' => 'Màn hình bếp', 'orders' => $orders, 'bid' => $bid, 'branches' => $branches, 'isAdmin' => has_role('admin'),
        ]);
    }

    public function advance(string $itemId = ''): void
    {
        $this->requireRole(...self::ROLES);
        $this->postOnly('kitchen');
        $item = Order::findItem((int)$itemId);
        if (!$item || $item['order_status'] !== 'open') $this->forbidden();
        $u = auth_user();
        if ($u['role'] !== 'admin' && (int)$item['branch_id'] !== (int)$u['branch_id']) $this->forbidden();

        Order::advanceKitchen((int)$itemId);
        redirect('kitchen' . ($u['role'] === 'admin' ? '?branch=' . (int)$item['branch_id'] : ''));
    }

    private function context(): array
    {
        $u = auth_user();
        if ($u['role'] === 'admin') {
            $branches = Branch::activeList();
            $bid = (int)($_GET['branch'] ?? 0);
            $valid = false;
            foreach ($branches as $b) if ((int)$b['id'] === $bid) $valid = true;
            if (!$valid) $bid = $branches ? (int)$branches[0]['id'] : 0;
            return [$bid, $branches];
        }
        if (!$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }
        return [(int)$u['branch_id'], []];
    }
}
