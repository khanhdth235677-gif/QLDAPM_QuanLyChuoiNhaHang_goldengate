<?php
require_once APP_PATH . '/models/Chat.php';
require_once APP_PATH . '/models/Site.php';
require_once APP_PATH . '/models/LoginAttempt.php';

// API JSON cho khung chat của khách (không cần đăng nhập). Khách được nhận ra qua cookie ngẫu nhiên
class LivechatController extends Controller
{
    const COOKIE = 'gg_chat';
    const RATE_MSGS = 8;      // tối đa 8 tin trong 60 giây mỗi cuộc chat

    // Hỏi tin mới. ?after=<id tin cuối đã có>, ?open=1 khi khung chat đang mở (đánh dấu đã đọc)
    public function poll(): void
    {
        session_write_close();               // không giữ khóa phiên khi chỉ đọc
        $token = $this->token();
        $conv = $token ? Chat::latestByToken($token) : false;
        if (!$conv) $this->json(['active' => false]);

        $cid = (int)$conv['id'];
        $msgs = Chat::messages($cid, max(0, (int)($_GET['after'] ?? 0)));
        $open = !empty($_GET['open']);
        if ($open) Chat::markRead($cid, 'customer');

        $this->json(['active' => true, 'status' => $conv['status'], 'messages' => Chat::present($msgs, 'customer'),
                     'unread' => $open ? 0 : Chat::unreadForCustomer($cid)]);
    }

    public function start(): void
    {
        $this->needPost();
        $token = $this->token() ?? bin2hex(random_bytes(20));
        $conv = Chat::latestByToken($token);
        if ($conv && $conv['status'] === 'open') $this->json(['ok' => false, 'error' => 'Bạn đang có một cuộc trò chuyện chưa kết thúc.'], 409);

        $name = trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)($_POST['name'] ?? '')));
        $text = $this->clean((string)($_POST['message'] ?? ''));
        $branch = Site::branch((int)($_POST['branch_id'] ?? 0));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) $this->json(['ok' => false, 'error' => 'Vui lòng nhập tên (2 đến 50 ký tự).'], 422);
        if (!$branch) $this->json(['ok' => false, 'error' => 'Vui lòng chọn chi nhánh.'], 422);
        if ($text === '') $this->json(['ok' => false, 'error' => 'Vui lòng nhập nội dung tin nhắn.'], 422);
        if (mb_strlen($text) > Chat::MAX_LEN) $this->json(['ok' => false, 'error' => 'Tin nhắn tối đa ' . Chat::MAX_LEN . ' ký tự.'], 422);

        $ip = client_ip();
        if (LoginAttempt::isBlocked('chat', $ip)) $this->json(['ok' => false, 'error' => 'Bạn đã mở quá nhiều cuộc trò chuyện. Vui lòng thử lại sau ' . LoginAttempt::WINDOW_MIN . ' phút.'], 429);
        LoginAttempt::record('chat', $ip);

        $id = Chat::create($token, $name, (int)$branch['id']);
        Chat::addMessage($id, 'customer', null, $text);

        setcookie(self::COOKIE, $token, [
            'expires' => time() + 30 * 86400, 'path' => rtrim(BASE_URL, '/') . '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        $this->json(['ok' => true, 'status' => 'open', 'messages' => Chat::present(Chat::messages($id, 0), 'customer')]);
    }

    public function send(): void
    {
        $this->needPost();
        $conv = $this->current();
        $text = $this->clean((string)($_POST['message'] ?? ''));
        if ($text === '') $this->json(['ok' => false, 'error' => 'Vui lòng nhập nội dung tin nhắn.'], 422);
        if (mb_strlen($text) > Chat::MAX_LEN) $this->json(['ok' => false, 'error' => 'Tin nhắn tối đa ' . Chat::MAX_LEN . ' ký tự.'], 422);
        if (Chat::customerMsgCount((int)$conv['id'], 60) >= self::RATE_MSGS) $this->json(['ok' => false, 'error' => 'Bạn gửi quá nhanh, vui lòng chờ một chút.'], 429);

        $id = Chat::addMessage((int)$conv['id'], 'customer', null, $text);
        $this->json(['ok' => true, 'messages' => Chat::present(Chat::messages((int)$conv['id'], $id - 1), 'customer')]);
    }

    public function close(): void
    {
        $this->needPost();
        $conv = $this->current(false);
        Chat::setStatus((int)$conv['id'], 'closed');
        $this->json(['ok' => true]);
    }

    // ---------- nội bộ ----------
    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function needPost(): void
    {
        if (!$this->isPost()) $this->json(['ok' => false, 'error' => 'Phương thức không hợp lệ.'], 405);
        if (!csrf_valid()) $this->json(['ok' => false, 'error' => 'Phiên đã hết hạn, vui lòng tải lại trang.'], 419);
    }

    private function token(): ?string
    {
        $t = (string)($_COOKIE[self::COOKIE] ?? '');
        return preg_match('/^[a-f0-9]{40}$/', $t) ? $t : null;
    }

    // Cuộc chat hiện tại của khách (mới nhất theo cookie). $mustBeOpen = true: phải đang mở
    private function current(bool $mustBeOpen = true): array
    {
        $token = $this->token();
        $conv = $token ? Chat::latestByToken($token) : false;
        if (!$conv) $this->json(['ok' => false, 'error' => 'Chưa có cuộc trò chuyện nào.'], 404);
        if ($mustBeOpen && $conv['status'] !== 'open') $this->json(['ok' => false, 'error' => 'Cuộc trò chuyện đã kết thúc.', 'closed' => true], 409);
        return $conv;
    }

    // Làm sạch tin nhắn: bỏ ký tự điều khiển (giữ xuống dòng), gọn khoảng trắng đầu cuối
    private function clean(string $s): string
    {
        $s = str_replace("\r\n", "\n", $s);
        $s = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', ' ', $s);
        return trim(preg_replace("/\n{3,}/", "\n\n", $s));
    }
}
