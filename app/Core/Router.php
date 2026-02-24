<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private static array $routes          = [];
    private static array $groupMiddleware = [];

    // ─── Route registration ───────────────────────────────────────────────────

    public static function get(string $path, array $handler, array $middleware = []): void
    {
        self::register('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, array $handler, array $middleware = []): void
    {
        self::register('POST', $path, $handler, $middleware);
    }

    private static function register(string $method, string $path, array $handler, array $extra): void
    {
        self::$routes[] = [
            'method'     => $method,
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => array_merge(self::$groupMiddleware, $extra),
            'pattern'    => self::buildPattern($path),
        ];
    }

    /** Convert route path with {param} placeholders into a regex. */
    private static function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#u';
    }

    // ─── Middleware groups ────────────────────────────────────────────────────

    /** Apply middleware to a group of routes, then reset. */
    public static function middleware(array $middleware): self
    {
        self::$groupMiddleware = $middleware;
        return new self();
    }

    public function group(callable $callback): void
    {
        $callback();
        self::$groupMiddleware = [];
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────────

    public static function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named route params only
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);

                // Run middleware pipeline → controller
                self::runMiddleware(
                    $route['middleware'],
                    $request,
                    fn () => self::callHandler($route['handler'], $request)
                );
                return;
            }
        }

        Response::notFound();
    }

    // ─── Middleware pipeline ──────────────────────────────────────────────────

    private static function runMiddleware(array $stack, Request $request, callable $final): void
    {
        if (empty($stack)) {
            $final();
            return;
        }

        $name  = array_shift($stack);
        $class = self::resolveMiddleware($name);

        if ($class && class_exists($class)) {
            (new $class())->handle(
                $request,
                fn () => self::runMiddleware($stack, $request, $final)
            );
        } else {
            self::runMiddleware($stack, $request, $final);
        }
    }

    /** Map middleware alias → class. */
    private static function resolveMiddleware(string $name): ?string
    {
        return match ($name) {
            'auth'  => \App\Middleware\AuthMiddleware::class,
            default => null,
        };
    }

    // ─── Controller invocation ────────────────────────────────────────────────

    private static function callHandler(array $handler, Request $request): void
    {
        [$class, $method] = $handler;

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller class not found: {$class}");
        }

        $controller = new $class($request);

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method '{$method}' not found in {$class}");
        }

        $controller->{$method}();
    }
}
