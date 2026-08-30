<?php
/**
 * Expressive Router Engine
 */

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static string $groupPrefix = '';
    private static array $groupMiddleware = [];

    public static function get(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    public static function put(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    public static function options(string $path, $handler, array $middleware = []): void
    {
        self::addRoute('OPTIONS', $path, $handler, $middleware);
    }

    public static function group(array $attributes, callable $callback): void
    {
        $prevPrefix = self::$groupPrefix;
        $prevMiddleware = self::$groupMiddleware;

        if (isset($attributes['prefix'])) {
            self::$groupPrefix = $prevPrefix . '/' . trim($attributes['prefix'], '/');
        }
        if (isset($attributes['middleware'])) {
            $middlewares = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
            self::$groupMiddleware = array_merge(self::$groupMiddleware, $middlewares);
        }

        $callback();

        self::$groupPrefix = $prevPrefix;
        self::$groupMiddleware = $prevMiddleware;
    }

    private static function addRoute(string $method, string $path, $handler, array $middleware = []): void
    {
        $fullPath = self::$groupPrefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        if ($fullPath !== '/') {
            $fullPath = rtrim($fullPath, '/');
        }

        $allMiddleware = array_merge(self::$groupMiddleware, $middleware);

        self::$routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $allMiddleware
        ];
    }

    public static function dispatch(string $uri, string $method)
    {
        // Strip query string
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        
        // Remove base path if subfolder installation (e.g. /CicalengkaGO/public or /CicalengkaGO)
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        
        if ($scriptDir !== '/' && $scriptDir !== '.' && !empty($scriptDir)) {
            if (strpos($uri, $scriptDir) === 0) {
                $uri = substr($uri, strlen($scriptDir));
            } else {
                $parentDir = str_replace('\\', '/', dirname($scriptDir));
                if ($parentDir !== '/' && $parentDir !== '.' && !empty($parentDir) && strpos($uri, $parentDir) === 0) {
                    $uri = substr($uri, strlen($parentDir));
                }
            }
        }
        
        if (str_starts_with($uri, '/public/')) {
            $uri = substr($uri, 7);
        } elseif ($uri === '/public') {
            $uri = '/';
        }
        
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route path pattern to regex (e.g. /stores/{id} -> #^/stores/(?P<id>[^/]+)$#)
            $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                // Extract named route parameters
                $params = array_filter($matches, function ($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                // Run middlewares
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw)) {
                        $mwClass = "\\App\\Middleware\\" . $mw;
                        if (class_exists($mwClass)) {
                            $instance = new $mwClass();
                            $pass = $instance->handle();
                            if ($pass === false) {
                                return; // Handled by middleware (e.g. redirect or 403)
                            }
                        }
                    } elseif (is_callable($mw)) {
                        if ($mw() === false) {
                            return;
                        }
                    }
                }

                // Execute handler with indexed positional arguments to prevent PHP 8 named parameter conflicts
                $indexedParams = array_values($params);
                if (is_array($route['handler'])) {
                    [$ctrlClass, $action] = $route['handler'];
                    $controller = new $ctrlClass();
                    return call_user_func_array([$controller, $action], $indexedParams);
                } elseif (is_callable($route['handler'])) {
                    return call_user_func_array($route['handler'], $indexedParams);
                }
            }
        }

        // 404 handler
        if (str_starts_with($uri, '/api/')) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API Endpoint Not Found: ' . $uri]);
            exit;
        }

        http_response_code(404);
        View::render('errors.404', ['uri' => $uri], null);
    }
}
