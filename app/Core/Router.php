<?php
/**
 * Router — Enrutador HTTP simple y expresivo.
 *
 * Soporta rutas GET, POST, PUT, DELETE y grupos con prefijos.
 * Permite middlewares por ruta o grupo.
 * Resuelve parámetros dinámicos en la URL (ej: /events/{id}).
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * $router = new Router();
 * $router->get('/events', [EventController::class, 'index']);
 * $router->post('/events', [EventController::class, 'store'], ['auth']);
 * $router->get('/events/{id}', [EventController::class, 'show']);
 * $router->group('/admin', ['auth', 'tenant'], function($r) {
 *     $r->get('/dashboard', [DashboardController::class, 'index']);
 * });
 * $router->dispatch();
 * ```
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\TenantMiddleware;
use App\Core\Middleware\RoleMiddleware;

final class Router
{
    /** @var array<string, array> Rutas registradas agrupadas por método HTTP */
    private array $routes = [];

    /** @var array Prefijo activo durante un grupo */
    private string $groupPrefix = '';

    /** @var array Middlewares del grupo activo */
    private array $groupMiddlewares = [];

    /** @var array Mapa de alias de middleware a clase */
    private array $middlewareMap = [
        'auth'   => AuthMiddleware::class,
        'tenant' => TenantMiddleware::class,
        'role'   => RoleMiddleware::class,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Registro de rutas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registra una ruta GET.
     *
     * @param string         $path
     * @param array|callable $handler  [Controller::class, 'method'] o callable
     * @param array          $middlewares Nombres de middleware a aplicar
     */
    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /** Registra una ruta POST. */
    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /** Registra una ruta PUT (simulada con _method en formularios). */
    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /** Registra una ruta DELETE (simulada con _method en formularios). */
    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Agrupa rutas bajo un prefijo y middlewares comunes.
     *
     * @param string   $prefix
     * @param array    $middlewares
     * @param callable $callback
     *
     * @example
     * ```php
     * $router->group('/admin', ['auth'], function($r) {
     *     $r->get('/users', [UserController::class, 'index']);
     * });
     * ```
     */
    public function group(string $prefix, array $middlewares, callable $callback): void
    {
        $previousPrefix      = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->groupPrefix      = $previousPrefix . $prefix;
        $this->groupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->groupPrefix      = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Despacho de la petición
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resuelve la petición actual y ejecuta el handler correspondiente.
     *
     * Soporta _method override en formularios HTML para PUT/DELETE.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $this->parseUri();

        // Soporte para method override (_method en POST)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->matchRoute($route['pattern'], $uri);

            if ($params !== false) {
                // Ejecutar middlewares
                foreach ($route['middlewares'] as $alias) {
                    // Soporte para alias con parámetro: 'role:owner,admin'
                    if (str_starts_with($alias, 'role:')) {
                        RoleMiddleware::fromString(substr($alias, 5))->handle();
                        continue;
                    }
                    $middlewareClass = $this->middlewareMap[$alias] ?? null;
                    if ($middlewareClass && class_exists($middlewareClass)) {
                        (new $middlewareClass())->handle();
                    }
                }

                // Ejecutar handler
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // 404
        $this->handleNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /** Añade una ruta al registro interno. */
    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $fullPath   = $this->groupPrefix . $path;
        $pattern    = $this->compilePattern($fullPath);
        $allMiddles = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[$method][] = [
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => array_unique($allMiddles),
        ];
    }

    /**
     * Convierte un path con {param} en una expresión regular.
     *
     * Ejemplo: /events/{id}/sessions → /^\/events\/(\d+)\/sessions$/
     */
    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '@^' . $pattern . '$@';
    }

    /**
     * Intenta hacer match entre el patrón y la URI actual.
     *
     * @return array|false Parámetros capturados o false si no coincide.
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Quitar el match completo
            return $matches;
        }

        return false;
    }

    /** Limpia la URI actual quitando query string y basepath. */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = strtok($uri, '?'); // Quitar query string
        $uri = rtrim($uri, '/') ?: '/';
        return rawurldecode($uri);
    }

    /**
     * Instancia el controller e invoca el método con los parámetros.
     *
     * @param array|callable $handler
     * @param array          $params
     */
    private function callHandler(array|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        [$class, $method] = $handler;

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller not found: {$class}");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} not found in {$class}");
        }

        call_user_func_array([$controller, $method], $params);
    }

    /** Muestra la página 404. */
    private function handleNotFound(): void
    {
        http_response_code(404);
        $viewPath = dirname(__DIR__, 2) . '/views/errors/404.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>404 — Página no encontrada</h1>';
        }
    }
}
