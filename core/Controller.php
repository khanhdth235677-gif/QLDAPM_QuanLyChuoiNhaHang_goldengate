<?php
class Controller
{
    protected function view(string $name, array $data = [], bool $layout = true): void
    {
        $file = APP_PATH . '/views/' . $name . '.php';
        if (!is_file($file)) { http_response_code(500); exit('Không tìm thấy view: ' . e($name)); }

        // Mỗi phần (header, nội dung, footer) chạy trong phạm vi biến riêng,
        // để biến của header (menu, roles...) không ghi đè biến của view
        if ($layout) $this->render(APP_PATH . '/views/layouts/header.php', ['title' => $data['title'] ?? '']);
        $this->render($file, $data);
        if ($layout) $this->render(APP_PATH . '/views/layouts/footer.php', []);
    }

    private function render(string $__file, array $__vars): void
    {
        extract($__vars, EXTR_SKIP);
        require $__file;
    }

    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

    // Chỉ cho phép gửi bằng POST kèm CSRF token (dùng cho các nút khóa, đặt lại mật khẩu...)
    protected function postOnly(string $backTo = 'dashboard'): void
    {
        if (!$this->isPost()) redirect($backTo);
        csrf_check();
    }

    protected function forbidden(): void
    {
        http_response_code(403);
        require APP_PATH . '/views/errors/403.php';
        exit;
    }

    protected function requireLogin(): void
    {
        if (!is_logged_in()) redirect('auth/login');

        // Tài khoản bị buộc đổi mật khẩu thì chỉ được vào trang đổi mật khẩu
        $u = auth_user();
        if (!empty($u['must_change']) && !in_array(current_segment(), ['account', 'auth'], true)) {
            flash('error', 'Bạn cần đổi mật khẩu trước khi sử dụng hệ thống.');
            redirect('account/password');
        }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireLogin();
        if (!has_role(...$roles)) $this->forbidden();
    }
}
