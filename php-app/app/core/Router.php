<?php
class Router {
    private $routes = [];
    private $routeParams = [];
    private $middlewareStack = [];
    
    public function add($method, $path, $handler, $middleware = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/') ?: '/';
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $requestMethod, $requestUri)) {
                return $this->executeRoute($route);
            }
        }
        
        http_response_code(404);
        $this->renderError('Page not found', 404);
    }
    
    private function matchRoute($route, $method, $uri) {
        if ($route['method'] !== $method) return false;
        
        $pattern = $this->convertToRegex($route['path']);
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $this->routeParams = $matches;
            return true;
        }
        return false;
    }
    
    private function convertToRegex($path) {
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#i';
    }
    
    private function executeRoute($route) {
        try {
            // Execute middleware
            foreach ($route['middleware'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                if (!$middleware->handle()) {
                    return false;
                }
            }
            
            $handler = $route['handler'];
            
            if (is_callable($handler)) {
                call_user_func_array($handler, $this->routeParams);
            } else if (is_string($handler)) {
                $this->callControllerMethod($handler);
            }
        } catch (Exception $e) {
            error_log("Route execution error: " . $e->getMessage());
            $this->renderError('Server error', 500);
        }
    }
    
    private function callControllerMethod($handler) {
        list($controller, $method) = explode('@', $handler);
        $controllerFile = __DIR__ . '/../controllers/' . $controller . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller file not found: $controllerFile");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controller)) {
            throw new Exception("Controller class not found: $controller");
        }
        
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $method)) {
            throw new Exception("Method not found: $method in $controller");
        }
        
        call_user_func_array([$controllerInstance, $method], $this->routeParams);
    }
    
    private function renderError($message, $code) {
        http_response_code($code);
        
        if (file_exists(__DIR__ . "/../views/errors/{$code}.php")) {
            require_once __DIR__ . "/../views/errors/{$code}.php";
        } else {
            echo "<h1>$code Error</h1><p>$message</p>";
        }
        exit;
    }
}