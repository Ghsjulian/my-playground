<?php

class Router {
    private $routes = [];
    private $middlewares = [];

    public function get(string $path, string $controller) {
        $this->addRoute('GET', $path, $controller);
    }

    public function post(string $path, string $controller) {
        $this->addRoute('POST', $path, $controller);
    }

    public function put(string $path, string $controller) {
        $this->addRoute('PUT', $path, $controller);
    }

    public function delete(string $path, string $controller) {
        $this->addRoute('DELETE', $path, $controller);
    }

    private function addRoute(string $method, string $path, string $controller) {
        $path = $this->normalizePath($path);
        $this->routes[] = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $this->parseController($controller),
            'middlewares' => []
        ];
    }

    private function normalizePath(string $path): string {
        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }

    private function parseController(string $controller): array {
        $parts = explode('@', $controller);
        if (count($parts) !== 2) {
            throw new Exception("Invalid controller format. Use 'Classname@method'");
        }
        return $parts;
    }

    public function use(string $path, callable $middleware) {
        $this->middlewares[] = [
            'path' => $this->normalizePath($path),
            'middleware' => $middleware
        ];
    }

    public function dispatch(string $path) {
        $path = $this->normalizePath($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        // Run middlewares
        foreach ($this->middlewares as $middleware) {
            if (preg_match("#^{$middleware['path']}$#", $path)) {
                $middleware['middleware']();
            }
        }

        // Run routes
        foreach ($this->routes as $route) {
            if (!preg_match("#^{$route['path']}$#", $path) || $route['method'] !== $method) {
                continue;
            }

            [$class, $function] = $route['controller'];

            $controllerInstance = new $class;

            $controllerInstance->{$function}();
        }
    }
}

/* How to use this class */ 
$router = new Router();

$router->get('/', 'UserController@index');
$router->get('/about', 'AboutController@index');
$router->post('/users', 'UserController@create');

$router->use('/admin', function () {
    echo "Admin middleware";
});

$router->get('/admin/dashboard', 'AdminController@dashboard');

$router->dispatch($_SERVER['REQUEST_URI']);


?>