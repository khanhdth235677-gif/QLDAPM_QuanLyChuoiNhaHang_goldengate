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
