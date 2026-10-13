<?php
require_once ROOT_PATH . '/config/billing.php';
require_once APP_PATH . '/models/Order.php';
require_once APP_PATH . '/models/Category.php';
require_once APP_PATH . '/models/DiningTable.php';
require_once APP_PATH . '/models/Branch.php';

// Gọi món, thanh toán, in hóa đơn
class OrdersController extends Controller
{
    const ROLES = ['admin', 'manager', 'cashier', 'waiter'];
    const PAY_ROLES = ['admin', 'manager', 'cashier'];
    const LINE_STATUS = [
        'new'     => ['Mới', 'secondary'],
        'cooking' => ['Đang nấu', 'warning'],
        'ready'   => ['Đã xong', 'info'],
        'served'  => ['Đã phục vụ', 'success'],
    ];

    // Danh sách đơn đang mở
    public function index(): void
    {
        $this->requireRole(...self::ROLES);
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $this->view('orders/index', [
            'title' => 'Đơn đang mở', 'orders' => Order::openList($isAdmin ? null : (int)$u['branch_id']), 'isAdmin' => $isAdmin,
        ]);
    }

    // Mở bàn (tạo đơn) rồi vào màn hình gọi món
    public function open(string $tableId = ''): void
    {
        $this->requireRole(...self::ROLES);
        $this->postOnly('floor');
        $u = auth_user();
        $t = DiningTable::find((int)$tableId);
        if (!$t) $this->forbidden();
        if ($u['role'] !== 'admin' && (int)$t['branch_id'] !== (int)$u['branch_id']) $this->forbidden();

        redirect('orders/show/' . Order::open((int)$t['id'], (int)$u['id']));
    }

    // Màn hình gọi món
    public function show(string $id = ''): void
    {
        $order = $this->loadOrder($id);
        $cat   = (int)($_GET['cat'] ?? 0);
        $bid   = (int)$order['branch_id'];

        $emptyTables = array_values(array_filter(DiningTable::forBranch($bid),
            fn($t) => $t['status'] === 'empty' && (int)$t['id'] !== (int)$order['table_id']));

        $this->view('orders/show', [
            'title' => 'Gọi món - Bàn ' . $order['table_name'], 'order' => $order, 'lines' => Order::items((int)$id),
            'cats' => Category::all(), 'cat' => $cat, 'menu' => Order::menuForBranch($bid, $cat ?: null),
            'emptyTables' => $emptyTables, 'canManage' => has_role('admin', 'manager'),
        ]);
    }

    public function additem(string $id = ''): void
    {
        $order = $this->loadOrder($id);
        $this->postOnly('orders/show/' . (int)$id);
        $err = Order::addItem((int)$id, (int)$order['branch_id'], (int)($_POST['menu_item_id'] ?? 0));
        if ($err) flash('error', $err);
        $this->back((int)$id);
    }

    public function qty(string $itemId = ''): void
    {
        $item = $this->loadLine($itemId);
        $this->postOnly('orders/show/' . (int)$item['order_id']);
        $delta = (int)($_POST['delta'] ?? 0);
        if ($delta !== 1 && $delta !== -1) $this->back((int)$item['order_id']);

        $err = Order::changeQty((int)$itemId, $delta);
        if ($err) flash('error', $err);
        $this->back((int)$item['order_id']);
    }

    public function note(string $itemId = ''): void
    {
        $item = $this->loadLine($itemId);
        $this->postOnly('orders/show/' . (int)$item['order_id']);
        $note = trim($_POST['note'] ?? '');
        if (mb_strlen($note) > 255) { flash('error', 'Ghi chú tối đa 255 ký tự.'); $this->back((int)$item['order_id']); }

        Order::setNote((int)$itemId, $note !== '' ? $note : null);
        $this->back((int)$item['order_id']);
    }

    public function remove(string $itemId = ''): void
    {
        $item = $this->loadLine($itemId);
        $this->postOnly('orders/show/' . (int)$item['order_id']);
        Order::removeItem((int)$itemId, (int)$item['order_id']);
        $this->back((int)$item['order_id']);
    }

    // Phục vụ báo đã mang món (bếp đã xong) ra bàn
    public function serve(string $itemId = ''): void
    {
        $this->requireRole(...self::ROLES);
        $item = Order::findItem((int)$itemId);
        if (!$item) $this->forbidden();
        $this->loadOrder((string)$item['order_id']);
        $this->postOnly('orders/show/' . (int)$item['order_id']);
        Order::serve((int)$itemId);
        $this->back((int)$item['order_id']);
    }

    public function move(string $id = ''): void
    {
        $this->loadOrder($id);
        $this->postOnly('orders/show/' . (int)$id);
        $err = Order::moveTable((int)$id, (int)($_POST['table_id'] ?? 0));
        flash($err ? 'error' : 'success', $err ?? 'Đã chuyển đơn sang bàn mới.');
        $this->back((int)$id);
    }

    // Đơn chưa có món: ai cũng hủy được. Đơn đã có món: chỉ quản lý hoặc admin
    public function cancel(string $id = ''): void
    {
        $order = $this->loadOrder($id);
        $this->postOnly('orders/show/' . (int)$id);

        if (count(Order::items((int)$id)) > 0 && !has_role('admin', 'manager')) {
            flash('error', 'Đơn đã có món, chỉ quản lý mới được hủy.');
            $this->back((int)$id);
        }
        Order::cancel((int)$id);
        flash('success', 'Đã hủy đơn của bàn ' . $order['table_name'] . '.');
        redirect('floor' . (has_role('admin') ? '?branch=' . (int)$order['branch_id'] : ''));
    }

    // ---------- Thanh toán (admin, quản lý, thu ngân) ----------
    public function pay(string $id = ''): void
    {
        $this->requireRole(...self::PAY_ROLES);
        $order = $this->loadOrder($id);
        $oid = (int)$id;
        $lines = Order::items($oid);
        if (!$lines) { flash('error', 'Đơn chưa có món nào để thanh toán.'); redirect('orders/show/' . $oid); }

        if ($this->isPost()) {
            csrf_check();
            $mode   = $_POST['discount_mode'] ?? 'none';
            $value  = (int)preg_replace('/\D/', '', (string)($_POST['discount_value'] ?? ''));
            $vat    = (int)($_POST['vat_rate'] ?? -1);
            $method = $_POST['method'] ?? '';
            $cash   = (int)preg_replace('/\D/', '', (string)($_POST['cash_received'] ?? ''));

            $err = null;
            if (!in_array($mode, ['none', 'percent', 'amount'], true)) $err = 'Kiểu giảm giá không hợp lệ.';
            elseif ($mode === 'percent' && $value > 100)               $err = 'Giảm theo phần trăm tối đa 100%.';
            elseif (!in_array($vat, VAT_OPTIONS, true))                $err = 'Mức VAT không hợp lệ.';
            elseif (!isset(Order::METHODS[$method]))                   $err = 'Vui lòng chọn phương thức thanh toán.';

            if (!$err) {
                $max = has_role('admin', 'manager') ? null : MAX_CASHIER_DISCOUNT_PERCENT;
                [$err, $res] = Order::pay($oid, $mode, $mode === 'none' ? 0 : $value, $vat, $method, $cash, $max);
            }
            if ($err) { flash('error', $err); redirect('orders/pay/' . $oid); }

            $msg = 'Đã thanh toán bàn ' . $order['table_name'] . ': ' . money($res['total']) . '.';
            if ($res['change'] > 0) $msg .= ' Tiền thối lại: ' . money($res['change']) . '.';
            flash('success', $msg);
            redirect('orders/bill/' . $oid . '?paid=1');
        }

        $subtotal = 0; $unserved = 0;
        foreach ($lines as $l) {
            $subtotal += (int)$l['quantity'] * (int)$l['unit_price'];
            if ($l['kitchen_status'] !== 'served') $unserved += (int)$l['quantity'];
        }
        $this->view('orders/pay', [
            'title' => 'Thanh toán - Bàn ' . $order['table_name'], 'order' => $order, 'lines' => $lines,
            'subtotal' => $subtotal, 'unserved' => $unserved, 'maxDiscount' => has_role('admin', 'manager') ? null : MAX_CASHIER_DISCOUNT_PERCENT,
        ]);
    }

    // Hóa đơn in được (đơn đã thanh toán) hoặc phiếu tạm tính (đơn đang mở)
    public function bill(string $id = ''): void
    {
        $this->requireRole(...self::ROLES);
        $order = Order::find((int)$id);
        if (!$order) $this->forbidden();
        $u = auth_user();
        if ($u['role'] !== 'admin' && (int)$order['branch_id'] !== (int)$u['branch_id']) $this->forbidden();

        $this->view('orders/bill', [
            'title' => 'Hóa đơn', 'order' => $order, 'lines' => Order::items((int)$id),
            'branch' => Branch::find((int)$order['branch_id']), 'payments' => Order::payments((int)$id),
            'justPaid' => isset($_GET['paid']),
        ], false);
    }

    // ---------- nội bộ ----------
    private function back(int $orderId): void
    {
        $cat = (int)($_POST['cat'] ?? 0);
        redirect('orders/show/' . $orderId . ($cat ? '?cat=' . $cat : ''));
    }

    // Lấy đơn và kiểm tra quyền: đúng chi nhánh, đơn còn mở
    private function loadOrder(string $id): array
    {
        $this->requireRole(...self::ROLES);
        $order = Order::find((int)$id);
        if (!$order) $this->forbidden();
        $u = auth_user();
        if ($u['role'] !== 'admin' && (int)$order['branch_id'] !== (int)$u['branch_id']) $this->forbidden();
        if ($order['status'] !== 'open') {
            flash('error', 'Đơn này đã đóng.');
            redirect('floor');
        }
        return $order;
    }

    // Lấy dòng món và kiểm tra quyền sửa: nhân viên chỉ sửa món bếp chưa nhận, quản lý sửa được mọi món
    private function loadLine(string $itemId): array
    {
        $this->requireRole(...self::ROLES);
        $item = Order::findItem((int)$itemId);
        if (!$item) $this->forbidden();
        $this->loadOrder((string)$item['order_id']);
        if ($item['kitchen_status'] !== 'new' && !has_role('admin', 'manager')) {
            flash('error', 'Món này bếp đã nhận, chỉ quản lý mới được sửa.');
            redirect('orders/show/' . (int)$item['order_id']);
        }
        return $item;
    }
}
