<?php

class Router {
    private $routes = [];

    public function add(string $method, string $path, string $controller) {
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

    public function dispatch(string $path) {
        $path = $this->normalizePath($path);
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

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
$router->add('GET', '/', 'UserController@index');
$router->add('GET', '/about', 'AboutController@index');
$router->add('POST', '/users', 'UserController@create');

?>