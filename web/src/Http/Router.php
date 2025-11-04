<?php
namespace App\Http;

use App\View\View;
use App\Core\Container;

class Router
{
    /** @var array<string, array<string, callable|array{0: class-string, 1: string}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    public function __construct(
        private View $view,
        private ?Container $container = null
    ) {}

    /**
     * Register a GET route.
     *
     * @param string $path
     * @param array|\Closure $handler [Controller::class, 'method'] or closure
     */
    public function get(string $path, array|\Closure $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    /**
     * Register a POST route.
     *
     * @param string $path
     * @param array|\Closure $handler
     */
    public function post(string $path, array|\Closure $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    /**
     * Match current request and run handler.
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?? '/');

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo "Route not found: {$method} {$path}";
            return;
        }

        // If it's a closure, run it
        if ($handler instanceof \Closure) {
            $out = $handler();
            if ($out !== null) {
                echo $out;
            }
            return;
        }

        // If it's a controller array
        [$controllerClass, $action] = $handler;

        // Instantiate controller with View (+ Container if available)
        $controller = $this->container
            ? new $controllerClass($this->view, $this->container)
            : new $controllerClass($this->view);

        $result = $controller->$action();

        // Most of your controllers return string (Twig HTML)
        if (is_string($result)) {
            echo $result;
        }
    }

    private function normalize(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
}


