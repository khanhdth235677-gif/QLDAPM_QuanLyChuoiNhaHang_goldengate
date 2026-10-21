<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Chat.php';

// Hộp thư chat với khách hàng cho nhân viên
class ChatController extends Controller
{
    const ROLES = ['admin', 'manager', 'cashier', 'waiter'];

    public function index(): void
    {
        $this->requireRole(...self::ROLES);
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $status = ($_GET['st'] ?? 'open') === 'closed' ? 'closed' : 'open';
        $branches = $isAdmin ? Branch::activeList() : [];
        $branchId = $isAdmin ? (int)($_GET['branch'] ?? 0) : (int)$u['branch_id'];
        if ($isAdmin && $branchId) {
            $ok = false;
            foreach ($branches as $b) if ((int)$b['id'] === $branchId) $ok = true;
            if (!$ok) $branchId = 0;
        }

        $this->view('chat/index', ['title' => 'Chat khách hàng', 'rows' => Chat::inbox($branchId ?: null, $status), 'status' => $status,
                                   'isAdmin' => $isAdmin, 'branches' => $branches, 'branchId' => $branchId, 'me' => (int)$u['id']]);
    }

    public function show(string $id = ''): void
    {
        $conv = $this->load($id);
        Chat::markRead((int)$conv['id'], 'staff');
        $msgs = Chat::present(Chat::messages((int)$conv['id'], 0), 'staff');
        $last = $msgs ? end($msgs)['id'] : 0;

        $this->view('chat/show', ['title' => 'Chat - ' . ($conv['guest_name'] ?: 'Khách'), 'conv' => $conv, 'msgs' => $msgs, 'last' => $last, 'me' => (int)auth_user()['id']]);
    }

    // Nhân viên trả lời. Gọi bằng fetch thì trả JSON, gọi bằng form thường thì chuyển lại trang chat
    public function send(string $id = ''): void
    {
        $conv = $this->load($id);
        $ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
        if (!$this->isPost()) redirect('chat/show/' . (int)$id);
        if ($ajax) { if (!csrf_valid()) $this->json(['ok' => false, 'error' => 'Phiên đã hết hạn, hãy tải lại trang.'], 419); }
        else csrf_check();

        $text = $this->clean((string)($_POST['message'] ?? ''));
        $err = null;
        if ($conv['status'] !== 'open')          $err = 'Cuộc trò chuyện đã đóng. Hãy mở lại trước khi trả lời.';
        elseif ($text === '')                     $err = 'Vui lòng nhập nội dung.';
        elseif (mb_strlen($text) > Chat::MAX_LEN) $err = 'Tin nhắn tối đa ' . Chat::MAX_LEN . ' ký tự.';

        if ($err) {
            if ($ajax) $this->json(['ok' => false, 'error' => $err], 422);
            flash('error', $err);
            redirect('chat/show/' . (int)$id);
        }

        $u = auth_user();
        $mid = Chat::addMessage((int)$conv['id'], 'staff', (int)$u['id'], $text);
        if (!$conv['assigned_user_id']) Chat::assign((int)$conv['id'], (int)$u['id']);   // người trả lời đầu tiên tự nhận phụ trách

        if ($ajax) $this->json(['ok' => true, 'messages' => Chat::present(Chat::messages((int)$conv['id'], $mid - 1), 'staff')]);
        redirect('chat/show/' . (int)$id);
    }

    // Tin mới cho trang đang mở (JSON)
    public function poll(string $id = ''): void
    {
        $conv = $this->loadJson($id);
        $msgs = Chat::messages((int)$conv['id'], max(0, (int)($_GET['after'] ?? 0)));
        Chat::markRead((int)$conv['id'], 'staff');
        $this->json(['ok' => true, 'status' => $conv['status'], 'assigned' => $conv['assigned_name'], 'messages' => Chat::present($msgs, 'staff')]);
    }

    // Số tin chưa đọc cho chấm đỏ ở menu (JSON)
    public function unread(): void
    {
        if (!is_logged_in() || !has_role(...self::ROLES)) $this->json(['unread' => 0], 401);
        $u = auth_user();
        session_write_close();
        $n = ($u['role'] === 'admin' || $u['branch_id']) ? Chat::unreadCount($u['role'] === 'admin' ? null : (int)$u['branch_id']) : 0;
        $this->json(['unread' => $n]);
    }

    public function assign(string $id = ''): void
    {
        $conv = $this->load($id);
        $this->postOnly('chat/show/' . (int)$id);
        Chat::assign((int)$conv['id'], (int)auth_user()['id']);
        flash('success', 'Bạn đã nhận phụ trách cuộc trò chuyện này.');
        redirect('chat/show/' . (int)$id);
    }

    public function close(string $id = ''): void
    {
        $conv = $this->load($id);
        $this->postOnly('chat/show/' . (int)$id);
        Chat::setStatus((int)$conv['id'], 'closed');
        flash('success', 'Đã đóng cuộc trò chuyện.');
        redirect('chat');
    }

    public function reopen(string $id = ''): void
    {
        $conv = $this->load($id);
        $this->postOnly('chat/show/' . (int)$id);
        Chat::setStatus((int)$conv['id'], 'open');
        flash('success', 'Đã mở lại cuộc trò chuyện.');
        redirect('chat/show/' . (int)$id);
    }

    // ---------- nội bộ ----------
    private function load(string $id): array
    {
        $this->requireRole(...self::ROLES);
        $conv = Chat::find((int)$id);
        if (!$conv) $this->forbidden();
        $u = auth_user();
        if ($u['role'] !== 'admin' && (int)$conv['branch_id'] !== (int)$u['branch_id']) $this->forbidden();
        return $conv;
    }

    // Như load() nhưng trả lỗi JSON thay vì chuyển trang
    private function loadJson(string $id): array
    {
        if (!is_logged_in() || !has_role(...self::ROLES)) $this->json(['ok' => false, 'error' => 'Phiên đăng nhập đã hết hạn.'], 401);
        $conv = Chat::find((int)$id);
        $u = auth_user();
        if (!$conv || ($u['role'] !== 'admin' && (int)$conv['branch_id'] !== (int)$u['branch_id'])) $this->json(['ok' => false, 'error' => 'Không có quyền.'], 403);
        session_write_close();
        return $conv;
    }

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function clean(string $s): string
    {
        $s = str_replace("\r\n", "\n", $s);
        $s = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', ' ', $s);
        return trim(preg_replace("/\n{3,}/", "\n\n", $s));
    }
}
