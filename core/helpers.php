<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
// Thêm ?v=<thời gian sửa file> để trình duyệt tự tải lại CSS/JS khi file thay đổi (tránh bị kẹt bản cũ trong cache)
function asset(string $path): string {
    $rel  = ltrim($path, '/');
    $url  = BASE_URL . '/assets/' . $rel;
    $file = ROOT_PATH . '/assets/' . $rel;
    return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
}
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
function money($n): string { return number_format((float)$n, 0, ',', '.') . ' ₫'; }
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

// Phần đầu của URL hiện tại, ví dụ "users" trong /goldengate/users/edit/3
function current_segment(): string {
    $seg = explode('/', trim($_GET['url'] ?? '', '/'))[0];
    return strtolower($seg !== '' ? $seg : 'dashboard');
}

// Mật khẩu ngẫu nhiên (bỏ các ký tự dễ nhầm như 0/O, 1/l/I)
function random_password(int $len = 10): string {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

// ----- CSRF -----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) { http_response_code(419); exit('Phiên làm việc không hợp lệ, hãy tải lại trang.'); }
}

// ----- Flash message: flash('error','...') để ghi, flash('error') để đọc 1 lần -----
function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return null; }
    $m = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $m;
}

// ----- Xác thực, phân quyền -----
function auth_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return isset($_SESSION['user']); }
function has_role(string ...$roles): bool {
    $u = auth_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

// Hiển thị số lượng kho gọn: 12,5 thay vì 12,50; 20 thay vì 20,00
function qty_fmt($v): string {
    $s = number_format((float)$v, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

// Chuẩn hóa số lượng người nhập: nhận 12,5 hoặc 12.5, trả về chuỗi '12.5' hoặc null nếu sai
function parse_qty(string $raw, bool $allowZero = false): ?string {
    $raw = str_replace(',', '.', trim($raw));
    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw)) return null;
    if (!$allowZero && (float)$raw <= 0) return null;
    return $raw;
}

// JSON an toàn để nhúng thẳng vào thẻ <script> (chặn </script> và ký tự đặc biệt trong dữ liệu)
function json_js($v): string {
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// Chuẩn hóa số điện thoại về dạng 0xxxxxxxxx (bỏ khoảng trắng, dấu chấm, gạch; đổi +84 thành 0)
function normalize_phone(string $raw): string {
    $s = preg_replace('/[\s().\-]/', '', trim($raw));
    if (strpos($s, '+84') === 0) $s = '0' . substr($s, 3);
    elseif (strpos($s, '84') === 0 && strlen($s) >= 11) $s = '0' . substr($s, 2);
    return $s;
}
function valid_phone(string $normalized): bool { return (bool)preg_match('/^0\d{9,10}$/', $normalized); }

// Giống csrf_check() nhưng trả về true/false để API JSON tự trả lỗi
function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}
