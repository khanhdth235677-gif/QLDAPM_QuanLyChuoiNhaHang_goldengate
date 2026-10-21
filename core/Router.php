<?php
// URL dạng /goldengate/<controller>/<method>/<tham số...>
// Ví dụ: /goldengate/auth/login  ->  AuthController::login()
class Router
{
    public function dispatch(): void
    {
        $url   = trim($_GET['url'] ?? '', '/');
        $parts = $url === '' ? [] : explode('/', $url);

        foreach ($parts as $p) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $p)) $this->notFound('Địa chỉ "' . $url . '" chứa ký tự không hợp lệ.');
        }

        $default = is_logged_in() ? 'dashboard' : 'site';   // địa chỉ gốc: khách thấy trang công khai
        $class  = ucfirst(strtolower($parts[0] ?? $default)) . 'Controller';
        $method = strtolower($parts[1] ?? 'index');
        $params = array_slice($parts, 2);

        $file = APP_PATH . '/controllers/' . $class . '.php';
        if (!is_file($file)) $this->notFound('Không tìm thấy file controller: ' . $file);
        require_once $file;

        if (!class_exists($class, false)) $this->notFound('File ' . $file . ' có nhưng không chứa class ' . $class . '.');
        $controller = new $class();
        if (!method_exists($controller, $method)) $this->notFound('Class ' . $class . ' không có hàm "' . $method . '".');

        $rm = new ReflectionMethod($controller, $method);
        if (!$rm->isPublic()) $this->notFound('Hàm ' . $class . '::' . $method . ' không phải public.');
        if (count($params) > $rm->getNumberOfParameters()) $this->notFound('Địa chỉ có thừa tham số cho ' . $class . '::' . $method . '.');
        if (count($params) < $rm->getNumberOfRequiredParameters()) $this->notFound('Địa chỉ thiếu tham số cho ' . $class . '::' . $method . '.');

        $rm->invokeArgs($controller, $params);
    }

    // Ở chế độ DEBUG, trang 404 sẽ hiện lý do cụ thể để dễ sửa
    private function notFound(string $reason = ''): void
    {
        http_response_code(404);
        if (!(defined('DEBUG') && DEBUG)) $reason = '';
        require APP_PATH . '/views/errors/404.php';
        exit;
    }
}
