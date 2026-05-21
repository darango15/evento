<?php
/**
 * Request — Abstracción de la petición HTTP actual.
 *
 * Encapsula $_GET, $_POST, $_FILES, $_SERVER y $_COOKIE
 * con sanitización básica y helpers de conveniencia.
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * $request = new Request();
 * $name    = $request->post('name', 'default');
 * $page    = $request->get('page', 1);
 * $method  = $request->method();
 * if ($request->isPost()) { ... }
 * if ($request->isAjax()) { ... }
 * ```
 */

declare(strict_types=1);

namespace App\Core;

class Request
{
    /** @var array Datos POST ya procesados */
    private array $post;

    /** @var array Datos GET */
    private array $query;

    /** @var array Archivos subidos */
    private array $files;

    /** @var array Headers */
    private array $headers;

    /** @var string Método HTTP efectivo (considera _method override) */
    private string $method;

    /** @var string URI limpia sin query string */
    private string $uri;

    public function __construct()
    {
        $this->post    = $_POST    ?? [];
        $this->query   = $_GET     ?? [];
        $this->files   = $_FILES   ?? [];
        $this->headers = $this->parseHeaders();
        $this->method  = $this->resolveMethod();
        $this->uri     = $this->resolveUri();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acceso a datos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene un valor del POST con valor por defecto.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Obtiene un valor del query string (GET).
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Obtiene un valor de POST o GET (primero POST).
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Devuelve solo los campos indicados del POST.
     *
     * @param  array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = trim((string)($this->post[$key] ?? ''));
        }
        return $result;
    }

    /**
     * Devuelve todos los datos POST.
     */
    public function all(): array
    {
        return $this->post;
    }

    /**
     * Obtiene un archivo subido.
     *
     * @param  string $field
     * @return array|null
     */
    public function file(string $field): ?array
    {
        $file = $this->files[$field] ?? null;
        if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            return $file;
        }
        return null;
    }

    /**
     * Obtiene un header HTTP.
     *
     * @param  string $name  Nombre del header (insensible a mayúsculas)
     * @param  string $default
     * @return string
     */
    public function header(string $name, string $default = ''): string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Información de la petición
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Método HTTP efectivo (GET, POST, PUT, DELETE…).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * URI actual sin query string.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * URL completa incluyendo query string.
     */
    public function fullUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * IP del cliente (con soporte para proxies).
     */
    public function ip(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * User-Agent del cliente.
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Verifica si el método es POST.
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Verifica si el método es GET.
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Verifica si la petición es AJAX (XMLHttpRequest).
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * Verifica si espera respuesta JSON (Accept: application/json).
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Verifica si el CSRF token del POST es válido.
     */
    public function hasCsrf(): bool
    {
        $token = $this->post('_token', '');
        return isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($this->post['_method'])) {
            $override = strtoupper($this->post['_method']);
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                return $override;
            }
        }

        return $method;
    }

    private function resolveUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = strtok($uri, '?');
        return rtrim($uri, '/') ?: '/';
    }

    /** @return array<string, string> */
    private function parseHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
            return $headers;
        }

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
