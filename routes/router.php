<?php

declare(strict_types=1);

class Router
{
    //firma delle funzioni
    /** @var array<string, array<string, array{controller: string, action: string, auth: bool}>> */

    private array $routes = [];

    public function get(string $path, string $controller, string $action, bool $auth = false): void
    {
        $this->addRoute('GET', $path, $controller, $action, $auth);
    }

    public function post(string $path, string $controller, string $action, bool $auth = false): void
    {
        $this->addRoute('POST', $path, $controller, $action, $auth);
    }

    public function put(string $path, string $controller, string $action, bool $auth = false): void
    {
        $this->addRoute('PUT', $path, $controller, $action, $auth);
    }

    public function delete(string $path, string $controller, string $action, bool $auth = false): void
    {
        $this->addRoute('DELETE', $path, $controller, $action, $auth);
    }

    private function addRoute(string $method, string $path, string $controller, string $action, bool $auth): void
    {
        $this->routes[$method][$path] = [
            'controller' => $controller,
            'action'     => $action,
            'auth'       => $auth,
        ];
    }

    // Smaltimento richiesta HTTP
    public function dispatch(string $method, string $uri): void
    {
        // normalizzo uri
        $uri = '/' . trim($uri, '/'); 

        // controllo corrispondenza metodo 
        foreach ($this->routes[$method] ?? [] as $path => $route) {
            $params = $this->match($path, $uri);

            if ($params !== null) {
                // Chiamo AuthMiddleware in caso di route protetta
                if ($route['auth']) {
                    require_once __DIR__ . '/../middleware/AuthMiddleware.php';
                    AuthMiddleware::handle();
                }

                $controllerClass = $route['controller'];
                $action          = $route['action'];

                $controller = new $controllerClass();
                $controller->$action($params);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route non trovato']);
    }

    private function match(string $routePath, string $uri): ?array
    {
    
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );
        }

        return null;
    }
}