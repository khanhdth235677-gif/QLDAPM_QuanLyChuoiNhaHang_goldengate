<?php
require_once ROOT_PATH . '/config/booking.php';
require_once APP_PATH . '/models/Site.php';
require_once APP_PATH . '/models/Customer.php';
require_once APP_PATH . '/models/Reservation.php';
require_once APP_PATH . '/models/LoginAttempt.php';
require_once APP_PATH . '/models/Order.php';

// Trang công khai cho khách: giới thiệu, thực đơn, đặt bàn online, tra cứu đặt bàn. Không cần đăng nhập
class SiteController extends Controller
{
    public function index(): void
    {
        $branches = Site::activeBranches();
        $homeBranch = $branches[0] ?? false;
        $groups = [];
        if ($homeBranch) {
            foreach (Site::menu((int)$homeBranch['id']) as $item) {
                $categoryId = (int)$item['category_id'];
                if (!isset($groups[$categoryId])) {
                    $groups[$categoryId] = ['name' => $item['category_name'], 'items' => []];
                }
                $groups[$categoryId]['items'][] = $item;
            }
        }

        $this->publicView('site/index', [
            'title' => 'Trang chủ',
            'branches' => $branches,
            'homeBranch' => $homeBranch,
            'featured' => Site::featured(6),
            'groups' => $groups,
        ]);
    }

    public function item(string $id = ''): void
    {
        $branches = Site::activeBranches();
        $branchId = (int)($_GET['branch'] ?? ($branches[0]['id'] ?? 0));
        $branch = $branchId ? Site::branch($branchId) : false;
        if (!$branch && $branches) {
            $branch = $branches[0];
            $branchId = (int)$branch['id'];
        }

        $item = Site::item((int)$id, $branchId);
        if (!$item) http_response_code(404);
        $related = $item
            ? Site::relatedItems((int)$item['category_id'], (int)$item['id'], $branchId)
            : [];

        $this->publicView('site/item', [
            'title' => $item ? $item['name'] : 'Không tìm thấy món',
            'item' => $item,
            'related' => $related,
            'branch' => $branch,
        ]);
    }

    public function menu(string $bid = ''): void
    {
        $branches = Site::activeBranches();
        $branchId = (int)$bid ?: (int)($_GET['branch'] ?? 0);
        $branch = $branchId ? Site::branch($branchId) : false;
        if (!$branch && $branches) $branch = $branches[0];

        $groups = [];
        if ($branch) {
            foreach (Site::menu((int)$branch['id']) as $m) {
                $categoryId = (int)$m['category_id'];
                if (!isset($groups[$categoryId])) {
                    $groups[$categoryId] = ['name' => $m['category_name'], 'items' => []];
                }
                $groups[$categoryId]['items'][] = $m;
            }
        }

        $this->publicView('site/menu', ['title' => 'Thực đơn', 'branches' => $branches, 'branch' => $branch, 'groups' => $groups]);
    }

    public function order(string $tableId = ''): void
    {
        $table = Site::tableForOrder((int)$tableId);
        $rawToken = $_GET['token'] ?? $_POST['token'] ?? '';
        $token = is_string($rawToken) ? $rawToken : '';
        if (!$table || !is_string($table['qr_token']) || $table['qr_token'] === ''
            || strlen($token) !== 64 || !hash_equals($table['qr_token'], $token)) {
            http_response_code(404);
            $this->publicView('site/order_invalid', ['title' => 'Mã QR không hợp lệ']);
            return;
        }

        $orderUrl = url('site/order/' . (int)$table['id']) . '?token=' . rawurlencode($token);
        $menu = Site::menu((int)$table['branch_id']);
        $error = null;
        $values = [];
        if ($this->isPost()) {
            csrf_check();
            if (!empty($_POST['website'])) redirect($orderUrl);
            if (time() - (int)($_SESSION['qr_order_last'][$table['id']] ?? 0) < 3) {
                $error = 'Vui lòng đợi một chút trước khi gửi thêm món.';
            }

            $posted = $_POST['items'] ?? [];
            $notes = $_POST['notes'] ?? [];
            if (!$error && (!is_array($posted) || !is_array($notes))) {
                $error = 'Thông tin giỏ món không hợp lệ.';
            } elseif (!$error) {
                $items = [];
                foreach ($posted as $id => $rawQuantity) {
                    if (!preg_match('/^\d+$/', (string)$id) || !is_scalar($rawQuantity) || !preg_match('/^\d{1,2}$/', (string)$rawQuantity)) {
                        $error = 'Số lượng món không hợp lệ.';
                        break;
                    }
                    $quantity = (int)$rawQuantity;
                    if ($quantity === 0) continue;
                    $note = $notes[$id] ?? '';
                    if (!is_scalar($note)) {
                        $error = 'Ghi chú món không hợp lệ.';
                        break;
                    }
                    $items[] = ['id' => (int)$id, 'quantity' => $quantity, 'note' => (string)$note];
                    $values[(int)$id] = $quantity;
                }
                if (!$error && !$items) $error = 'Vui lòng chọn ít nhất một món.';
                if (!$error && count($items) > 30) $error = 'Mỗi lần gửi tối đa 30 món khác nhau.';
            }

            if (!$error) {
                [$error, $orderId] = Order::addQrItems((int)$table['id'], $items);
                if (!$error) {
                    $_SESSION['qr_order_last'][$table['id']] = time();
                    $_SESSION['qr_order_success'] = ['table_id' => (int)$table['id'], 'order_id' => $orderId];
                    redirect($orderUrl . '&sent=1');
                }
            }
        }

        $sent = false;
        if (isset($_GET['sent'], $_SESSION['qr_order_success'])
            && (int)$_SESSION['qr_order_success']['table_id'] === (int)$table['id']) {
            $sent = true;
            unset($_SESSION['qr_order_success']);
        }

        $this->publicView('site/order', [
            'title' => 'Gọi món - Bàn ' . $table['table_name'],
            'table' => $table,
            'menu' => $menu,
            'orderOpen' => Order::hasOpen((int)$table['id']),
            'orderUrl' => $orderUrl,
            'error' => $error,
            'sent' => $sent,
            'values' => $values,
        ]);
    }

    public function book(): void
    {
        $branches = Site::activeBranches();
        $slots = Site::slots();
        $first = (int)($_GET['branch'] ?? ($branches[0]['id'] ?? 0));
        $form = ['branch_id' => $first, 'date' => date('Y-m-d', strtotime('+1 day')), 'time' => '19:00', 'guests' => 2,
                 'name' => '', 'phone' => '', 'note' => ''];
        $error = null;

        if ($this->isPost()) {
            csrf_check();
            if (!empty($_POST['website'])) redirect('site');                       // ô bẫy dành cho bot
            $form = array_merge($form, [
                'branch_id' => (int)($_POST['branch_id'] ?? 0), 'date' => trim($_POST['date'] ?? ''), 'time' => trim($_POST['time'] ?? ''),
                'guests' => (int)($_POST['guests'] ?? 0), 'name' => trim($_POST['name'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''),
                'note' => trim($_POST['note'] ?? ''),
            ]);

            if (time() - (int)($_SESSION['book_form_at'] ?? 0) < 3) {
                $error = 'Vui lòng điền thông tin và gửi lại.';
            } else {
                [$data, $error] = $this->readBooking($form, $branches, $slots);
            }

            if (!$error) {
                try {
                    $data['customer_id'] = Customer::findOrCreate($data['customer_name'], $data['customer_phone']);
                    $id = Reservation::createOnline($data);
                    unset($_SESSION['book_form_at']);
                    $_SESSION['last_booking'] = $id;
                    redirect('site/booked');
                } catch (DomainException $e) {
                    $error = $e->getMessage();
                } catch (PDOException $e) {
                    $error = 'Chưa gửi được yêu cầu đặt bàn. Vui lòng thử lại sau hoặc gọi điện cho nhà hàng.'
                           . (defined('DEBUG') && DEBUG ? ' [' . $e->getMessage() . ' - hãy kiểm tra cấu trúc database đặt bàn online.]' : '');
                }
            }
        }

        $_SESSION['book_form_at'] = time();
        $this->publicView('site/book', ['title' => 'Đặt bàn', 'branches' => $branches, 'slots' => $slots, 'form' => $form, 'error' => $error]);
    }

    // Trang xác nhận sau khi đặt (chỉ người vừa đặt trong phiên này mới xem được)
    public function booked(): void
    {
        $id = (int)($_SESSION['last_booking'] ?? 0);
        $r = $id ? Reservation::findForTrack($id) : false;
        if (!$r) redirect('site/book');
        $this->publicView('site/booked', ['title' => 'Đã gửi yêu cầu đặt bàn', 'r' => $r]);
    }

    // Tra cứu và hủy đặt bàn bằng mã đặt bàn + số điện thoại
    public function track(): void
    {
        $r = null; $error = null;
        $code = trim($_POST['code'] ?? ($_GET['code'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');

        if ($this->isPost()) {
            csrf_check();
            [$r, $error] = $this->lookup($code, $phone);
        }
        $this->publicView('site/track', ['title' => 'Tra cứu đặt bàn', 'r' => $r, 'error' => $error, 'code' => $code, 'phone' => $phone,
                                         'canCancel' => $r ? $this->canCancel($r) : false]);
    }

    public function cancel(): void
    {
        if (!$this->isPost()) redirect('site/track');
        csrf_check();
        $code = trim($_POST['code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        [$r, $error] = $this->lookup($code, $phone);

        if ($r && $this->canCancel($r)) {
            Reservation::setStatus((int)$r['id'], 'cancelled');
            flash('success', 'Đã hủy lượt đặt bàn ' . $this->code((int)$r['id']) . '.');
        } elseif ($r) {
            flash('error', 'Lượt đặt này không thể tự hủy. Vui lòng gọi điện cho nhà hàng.');
        } else {
            flash('error', $error ?? 'Không tìm thấy lượt đặt.');
        }
        redirect('site/track');
    }

    // ---------- nội bộ ----------
    private function code(int $id): string { return 'DB' . str_pad((string)$id, 6, '0', STR_PAD_LEFT); }

    private function canCancel(array $r): bool
    {
        return in_array($r['status'], ['pending', 'confirmed'], true)
            && strtotime($r['reserved_at']) - time() >= BOOK_CANCEL_BEFORE_MIN * 60;
    }

    // Tìm đặt bàn theo mã và số điện thoại. Chống dò: sai quá nhiều lần từ một IP thì tạm khóa
    private function lookup(string $code, string $phone): array
    {
        $ip = client_ip();
        if (LoginAttempt::isBlocked('track', $ip)) return [null, 'Bạn đã tra cứu sai quá nhiều lần. Vui lòng thử lại sau ' . LoginAttempt::WINDOW_MIN . ' phút.'];

        $id = (int)preg_replace('/\D/', '', $code);
        $r = $id > 0 ? Reservation::findForTrack($id) : false;
        $p = normalize_phone($phone);

        if ($r && $p !== '' && normalize_phone((string)$r['customer_phone']) === $p) return [$r, null];

        LoginAttempt::record('track', $ip);
        return [null, 'Không tìm thấy lượt đặt khớp với mã và số điện thoại này.'];
    }

    // Kiểm tra form đặt bàn. Trả về [dữ liệu sạch, lỗi]
    private function readBooking(array $f, array $branches, array $slots): array
    {
        $branch = null;
        foreach ($branches as $b) if ((int)$b['id'] === $f['branch_id']) $branch = $b;
        if (!$branch) return [null, 'Vui lòng chọn chi nhánh.'];

        if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) return [null, 'Vui lòng nhập họ tên (2 đến 100 ký tự).'];
        $phone = normalize_phone($f['phone']);
        if (!valid_phone($phone)) return [null, 'Số điện thoại không hợp lệ. Ví dụ: 0901234567.'];
        if ($f['guests'] < 1 || $f['guests'] > BOOK_MAX_GUESTS) {
            return [null, 'Số khách từ 1 đến ' . BOOK_MAX_GUESTS . '. Nhóm đông hơn vui lòng gọi ' . ($branch['phone'] ?: 'nhà hàng') . ' để được hỗ trợ.'];
        }
        if (!in_array($f['time'], $slots, true)) return [null, 'Vui lòng chọn giờ trong danh sách.'];

        $dt = DateTime::createFromFormat('Y-m-d H:i', $f['date'] . ' ' . $f['time']);
        if (!$dt || $dt->format('Y-m-d H:i') !== $f['date'] . ' ' . $f['time']) return [null, 'Ngày đặt bàn không hợp lệ.'];
        $now = new DateTime();
        if ($dt < (clone $now)->modify('+' . BOOK_MIN_LEAD_MIN . ' minutes')) return [null, 'Vui lòng đặt trước ít nhất ' . BOOK_MIN_LEAD_MIN . ' phút so với giờ đến.'];
        if ($dt > (clone $now)->modify('+' . BOOK_MAX_DAYS . ' days')) return [null, 'Chỉ nhận đặt bàn trong vòng ' . BOOK_MAX_DAYS . ' ngày tới.'];

        $note = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $f['note']));
        if (mb_strlen($note) > 255) return [null, 'Ghi chú tối đa 255 ký tự.'];
        $at = $dt->format('Y-m-d H:i:00');

        $ip = client_ip();
        if (Reservation::ipCount($ip) >= BOOK_MAX_PER_IP_HOUR) return [null, 'Bạn đã gửi quá nhiều yêu cầu trong một giờ. Vui lòng thử lại sau hoặc gọi điện cho nhà hàng.'];
        if (Reservation::activeByPhone($phone) >= BOOK_MAX_ACTIVE_PER_PHONE) return [null, 'Số điện thoại này đã có ' . BOOK_MAX_ACTIVE_PER_PHONE . ' lượt đặt còn hiệu lực. Vui lòng hủy bớt hoặc gọi điện cho nhà hàng.'];
        if (Reservation::sameSlot($phone, $at)) return [null, 'Số điện thoại này đã có một lượt đặt quanh giờ đó.'];

        [$fit, $busy] = Reservation::capacity((int)$branch['id'], $f['guests'], $at);
        if ($fit === 0) return [null, 'Chi nhánh chưa nhận đặt online cho ' . $f['guests'] . ' khách. Vui lòng gọi ' . ($branch['phone'] ?: 'nhà hàng') . '.'];
        if ($busy >= $fit) return [null, 'Khung giờ ' . $f['time'] . ' ngày ' . $dt->format('d/m/Y') . ' đã kín. Vui lòng chọn giờ khác.'];

        return [[
            'branch_id' => (int)$branch['id'], 'customer_name' => $f['name'], 'customer_phone' => $phone, 'guests' => $f['guests'],
            'reserved_at' => $at, 'note' => $note !== '' ? $note : null, 'ip' => $ip,
        ], null];
    }
}
