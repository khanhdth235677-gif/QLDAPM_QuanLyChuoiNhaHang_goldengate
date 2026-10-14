<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/DiningTable.php';
require_once APP_PATH . '/models/Order.php';
require_once APP_PATH . '/models/Reservation.php';

// Đặt bàn trước: nhập lượt đặt, xếp bàn, kiểm tra trùng giờ, khi khách đến thì mở bàn
class ReservationsController extends Controller
{
    const ROLES = ['admin', 'manager', 'cashier', 'waiter'];
    const STATUS = [
        'pending'   => ['Chờ xác nhận', 'warning'],
        'confirmed' => ['Đã xác nhận', 'primary'],
        'done'      => ['Khách đã đến', 'success'],
        'cancelled' => ['Đã hủy', 'secondary'],
    ];

    public function index(): void
    {
        $this->requireRole(...self::ROLES);
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $date = trim($_GET['date'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) $date = date('Y-m-d');

        $filter   = $_GET['st'] ?? 'active';
        if (!in_array($filter, ['active', 'done', 'cancelled', 'all'], true)) $filter = 'active';
        $branchId = $isAdmin ? (int)($_GET['branch'] ?? 0) : (int)$u['branch_id'];

        $rows = Reservation::list($branchId ?: null, $date, $filter);

        // Bàn có thể xếp cho khách đến (chưa có khách), nhóm theo chi nhánh
        $free = [];
        foreach ($rows as $r) {
            $bid = (int)$r['branch_id'];
            if (!isset($free[$bid])) {
                $free[$bid] = array_values(array_filter(DiningTable::forBranch($bid), fn($t) => $t['status'] !== 'occupied'));
            }
        }

        $waiting = 0; $guests = 0;
        foreach ($rows as $r) if (in_array($r['status'], ['pending', 'confirmed'], true)) { $waiting++; $guests += (int)$r['guests']; }

        $this->view('reservations/index', [
            'title' => 'Đặt bàn', 'rows' => $rows, 'date' => $date, 'filter' => $filter, 'branchId' => $branchId,
            'isAdmin' => $isAdmin, 'branches' => $isAdmin ? Branch::activeList() : [], 'free' => $free,
            'waiting' => $waiting, 'guests' => $guests, 'nowTs' => time(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(...self::ROLES);
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $branches = $isAdmin ? Branch::activeList() : [];
        if ($isAdmin && !$branches) { flash('error', 'Chưa có chi nhánh nào.'); redirect('branches'); }
        $tables = Reservation::tablesFor($isAdmin ? null : (int)$u['branch_id']);

        $res = [
            'branch_id' => $isAdmin ? (int)($_GET['branch'] ?? $branches[0]['id']) : (int)$u['branch_id'],
            'table_id' => 0, 'customer_name' => '', 'customer_phone' => '', 'guests' => 2, 'note' => '',
            'at_input' => date('Y-m-d\TH:i', strtotime('+1 hour')),
        ];

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read($u, $branches, $tables, null, true);
            $res = array_merge($res, $_POST, ['at_input' => trim($_POST['reserved_at'] ?? $res['at_input'])]);
            if (!$err) {
                Reservation::create($data);
                flash('success', 'Đã ghi nhận đặt bàn của ' . $data['customer_name'] . ' lúc ' . date('H:i d/m/Y', strtotime($data['reserved_at'])) . '.');
                redirect('reservations?date=' . date('Y-m-d', strtotime($data['reserved_at'])) . ($isAdmin ? '&branch=' . $data['branch_id'] : ''));
            }
            flash('error', $err);
        }
        $this->view('reservations/form', ['title' => 'Thêm đặt bàn', 'mode' => 'create', 'res' => $res,
                                          'branches' => $branches, 'tables' => $tables, 'isAdmin' => $isAdmin, 'actor' => $u]);
    }

    public function edit(string $id = ''): void
    {
        $r = $this->loadReservation($id);
        if (!in_array($r['status'], ['pending', 'confirmed'], true)) {
            flash('error', 'Lượt đặt này đã đóng, không sửa được.');
            $this->backToList($r);
        }
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        $branches = $isAdmin ? Branch::activeList() : [];
        $tables = Reservation::tablesFor($isAdmin ? null : (int)$u['branch_id']);
        $res = $r + ['at_input' => date('Y-m-d\TH:i', strtotime($r['reserved_at']))];

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->read($u, $branches, $tables, (int)$r['id'], false);
            $res = array_merge($res, $_POST, ['at_input' => trim($_POST['reserved_at'] ?? $res['at_input'])]);
            if (!$err) {
                Reservation::update((int)$r['id'], $data);
                flash('success', 'Đã cập nhật lượt đặt của ' . $data['customer_name'] . '.');
                redirect('reservations?date=' . date('Y-m-d', strtotime($data['reserved_at'])) . ($isAdmin ? '&branch=' . $data['branch_id'] : ''));
            }
            flash('error', $err);
        }
        $this->view('reservations/form', ['title' => 'Sửa đặt bàn', 'mode' => 'edit', 'res' => $res,
                                          'branches' => $branches, 'tables' => $tables, 'isAdmin' => $isAdmin, 'actor' => $u]);
    }

    public function confirm(string $id = ''): void
    {
        $r = $this->loadReservation($id);
        $this->postOnly('reservations');
        if ($r['status'] === 'pending') {
            Reservation::setStatus((int)$r['id'], 'confirmed');
            flash('success', 'Đã xác nhận đặt bàn của ' . $r['customer_name'] . '.');
        }
        $this->backToList($r);
    }

    public function cancel(string $id = ''): void
    {
        $r = $this->loadReservation($id);
        $this->postOnly('reservations');
        if (in_array($r['status'], ['pending', 'confirmed'], true)) {
            Reservation::setStatus((int)$r['id'], 'cancelled');
            flash('success', 'Đã hủy đặt bàn của ' . $r['customer_name'] . '.');
        }
        $this->backToList($r);
    }

    // Khách đến: mở bàn (tạo đơn) và đánh dấu lượt đặt đã hoàn tất
    public function arrive(string $id = ''): void
    {
        $r = $this->loadReservation($id);
        $this->postOnly('reservations');
        $u = auth_user();

        if (!in_array($r['status'], ['pending', 'confirmed'], true)) {
            flash('error', 'Lượt đặt này đã đóng.');
            $this->backToList($r);
        }

        $tableId = (int)($r['table_id'] ?: ($_POST['table_id'] ?? 0));
        $t = $tableId ? DiningTable::find($tableId) : false;
        if (!$t || (int)$t['branch_id'] !== (int)$r['branch_id']) {
            flash('error', 'Vui lòng chọn bàn cho khách.');
            $this->backToList($r);
        }
        if ($t['status'] === 'occupied' || Order::hasOpen($tableId)) {
            flash('error', 'Bàn ' . $t['table_name'] . ' đang có khách. Hãy xếp khách sang bàn khác.');
            $this->backToList($r);
        }

        $orderId = Order::open($tableId, (int)$u['id']);
        Reservation::markArrived((int)$r['id'], $tableId);
        flash('success', 'Đã mở bàn ' . $t['table_name'] . ' cho khách ' . $r['customer_name'] . '.');
        redirect('orders/show/' . $orderId);
    }

    // ---------- nội bộ ----------
    private function backToList(array $r): void
    {
        redirect('reservations?date=' . date('Y-m-d', strtotime($r['reserved_at'])) . (has_role('admin') ? '&branch=' . (int)$r['branch_id'] : ''));
    }

    private function loadReservation(string $id): array
    {
        $this->requireRole(...self::ROLES);
        $r = Reservation::find((int)$id);
        if (!$r) $this->forbidden();
        $u = auth_user();
        if ($u['role'] !== 'admin' && (int)$r['branch_id'] !== (int)$u['branch_id']) $this->forbidden();
        return $r;
    }

    // Kiểm tra form. Trả về [dữ liệu sạch, lỗi]
    private function read(array $u, array $branches, array $tables, ?int $exceptId, bool $isCreate): array
    {
        $name    = trim($_POST['customer_name'] ?? '');
        $phone   = trim($_POST['customer_phone'] ?? '');
        $guests  = (int)($_POST['guests'] ?? 0);
        $atRaw   = trim($_POST['reserved_at'] ?? '');
        $tableId = (int)($_POST['table_id'] ?? 0);
        $note    = trim($_POST['note'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) return [null, 'Tên khách không hợp lệ (tối đa 100 ký tự).'];
        if (!preg_match('/^[0-9+\-\s().]{6,20}$/', $phone)) return [null, 'Số điện thoại không hợp lệ.'];
        if ($guests < 1 || $guests > 200) return [null, 'Số khách phải từ 1 đến 200.'];
        if (mb_strlen($note) > 255) return [null, 'Ghi chú tối đa 255 ký tự.'];

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $atRaw);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $atRaw) return [null, 'Thời gian đặt bàn không hợp lệ.'];
        if ($isCreate && $dt < new DateTime('-5 minutes')) return [null, 'Thời gian đặt bàn phải ở hiện tại hoặc tương lai.'];
        if ($dt > new DateTime('+180 days')) return [null, 'Chỉ nhận đặt bàn trong vòng 180 ngày tới.'];
        $at = $dt->format('Y-m-d H:i:00');

        // Chi nhánh
        if ($u['role'] === 'admin') {
            $branchId = (int)($_POST['branch_id'] ?? 0);
            $ok = false;
            foreach ($branches as $b) if ((int)$b['id'] === $branchId) $ok = true;
            if (!$ok) return [null, 'Vui lòng chọn chi nhánh.'];
        } else {
            $branchId = (int)$u['branch_id'];
        }

        // Bàn (không bắt buộc: có thể xếp bàn sau khi khách đến)
        if ($tableId > 0) {
            $t = null;
            foreach ($tables as $x) if ((int)$x['id'] === $tableId && (int)$x['branch_id'] === $branchId) $t = $x;
            if (!$t) return [null, 'Bàn đã chọn không thuộc chi nhánh này.'];
            if ((int)$t['seats'] < $guests) return [null, 'Bàn ' . $t['table_name'] . ' chỉ có ' . $t['seats'] . ' chỗ, không đủ cho ' . $guests . ' khách.'];
            if (Reservation::hasConflict($tableId, $at, $exceptId)) {
                return [null, 'Bàn ' . $t['table_name'] . ' đã có người đặt quanh giờ này (mỗi lượt giữ bàn ' . Reservation::DURATION_MIN . ' phút). Hãy chọn bàn hoặc giờ khác.'];
            }
        }

        return [[
            'branch_id' => $branchId, 'table_id' => $tableId > 0 ? $tableId : null, 'customer_name' => $name,
            'customer_phone' => $phone, 'guests' => $guests, 'reserved_at' => $at, 'note' => $note !== '' ? $note : null,
        ], null];
    }
}
