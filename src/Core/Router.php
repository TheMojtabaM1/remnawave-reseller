<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal regex router. Routes are registered with method + path pattern.
 * Path params use {name} syntax and are passed to the handler as an assoc array.
 */
final class Router
{
    /** @var array<int, array{method:string,regex:string,params:array,handler:mixed,middleware:array}> */
    private array $routes = [];

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_]+)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = compact('method', 'regex', 'params', 'handler', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path;
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $args = array_combine($route['params'], $matches) ?: [];

                foreach ($route['middleware'] as $mw) {
                    // Middleware are callables that may halt via Response::* / Auth::guard
                    $mw($request);
                }

                $this->call($route['handler'], $request, $args);
                return;
            }
        }
        Response::abort(404, 'صفحه مورد نظر یافت نشد');
    }

    private function call(mixed $handler, Request $request, array $args): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $instance = new $class();
            $instance->$action($request, $args);
            return;
        }
        if (is_callable($handler)) {
            $handler($request, $args);
            return;
        }
        Response::abort(500, 'مسیر نامعتبر است');
    }
}
