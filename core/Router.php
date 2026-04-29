<?php
 
namespace Core;
 
/**
 * Router
 * Enruta peticiones HTTP a controladores y métodos.
 * Compatible con la convención de rutas de Laravel.
 */
class Router
{
    private array $routes = [];
 
    /** Registra una ruta GET */
    public function get(string $uri, string|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }
 
    /** Registra una ruta POST */
    public function post(string $uri, string|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }
 
    /** Registra rutas GET y POST */
    public function match(array $methods, string $uri, string|array $action): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $uri, $action);
        }
    }
 
    private function addRoute(string $method, string $uri, string|array $action): void
    {
        // Convierte parámetros {id} en regex capturadores
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $uri);
        $pattern = '@^' . $pattern . '$@';
 
        $this->routes[] = [
            'method'  => $method,
            'uri'     => $uri,
            'pattern' => $pattern,
            'action'  => $action,
        ];
    }
 
    /** Despacha la petición actual al controlador correspondiente */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
 
        // Quitar el base path si se sirve desde subdirectorio
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . ltrim($uri, '/');
 
        // Soporte para _method spoofing (PUT, DELETE desde formularios HTML)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
 
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // quita el match completo
                $this->callAction($route['action'], $matches);
                return;
            }
        }
 
        // Ruta no encontrada
        http_response_code(404);
        require VIEW_PATH . '/shared/404.php';
    }
 
    private function callAction(string|array $action, array $params): void
    {
        if (is_string($action)) {
            // Formato 'NombreController@metodo'
            [$class, $method] = explode('@', $action);
        } else {
            [$class, $method] = $action;
        }
 
        // Agrega namespace si no viene incluido el namespace completo
        if (!str_starts_with($class, 'App\\') && !str_starts_with($class, 'Core\\')) {
            $class = 'App\\Controllers\\' . $class;
        }
 
        if (!class_exists($class)) {
            throw new \RuntimeException("Controlador [{$class}] no encontrado.");
        }
 
        $controller = new $class();
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Método [{$method}] no existe en [{$class}].");
        }
 
        call_user_func_array([$controller, $method], $params);
    }
}
