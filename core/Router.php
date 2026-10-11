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
            if (!preg_match('/^[A-Za-z0-9_]+$/', $p)) $this->notFound();
        }

        $class  = ucfirst(strtolower($parts[0] ?? 'dashboard')) . 'Controller';
        $method = strtolower($parts[1] ?? 'index');
        $params = array_slice($parts, 2);

        $file = APP_PATH . '/controllers/' . $class . '.php';
        if (!is_file($file)) $this->notFound();
        require_once $file;

        $controller = new $class();
        if (!method_exists($controller, $method)) $this->notFound();

        $rm = new ReflectionMethod($controller, $method);
        if (!$rm->isPublic()
            || count($params) > $rm->getNumberOfParameters()
            || count($params) < $rm->getNumberOfRequiredParameters()) {
            $this->notFound();
        }

        $rm->invokeArgs($controller, $params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        require APP_PATH . '/views/errors/404.php';
        exit;
    }
}
