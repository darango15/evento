<?php
/**
 * Response — Abstracción de la respuesta HTTP.
 *
 * Proporciona métodos fluentes para construir y enviar respuestas HTTP:
 * headers, códigos de estado, redirecciones y respuestas JSON.
 *
 * @package App\Core
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Respuesta JSON
 * Response::json(['status' => 'ok'], 200);
 *
 * // Redirección
 * Response::redirect('/admin/events');
 *
 * // Respuesta con header personalizado
 * (new Response())->withHeader('X-Custom', 'value')->withStatus(201)->send('Created');
 * ```
 */

declare(strict_types=1);

namespace App\Core;

class Response
{
    /** @var int Código de estado HTTP */
    private int $statusCode = 200;

    /** @var array<string, string> Headers adicionales */
    private array $headers = [];

    /** @var string Cuerpo de la respuesta */
    private string $body = '';

    // ─────────────────────────────────────────────────────────────────────────
    // Fluent API (instancia)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Establece el código de estado HTTP.
     *
     * @param  int $code
     * @return static
     */
    public function withStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Agrega un header HTTP.
     *
     * @param  string $name
     * @param  string $value
     * @return static
     */
    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Establece el cuerpo de la respuesta y la envía.
     *
     * @param string $body
     */
    public function send(string $body = ''): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $body ?: $this->body;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos estáticos de conveniencia
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía una respuesta JSON y termina la ejecución.
     *
     * @param mixed $data
     * @param int   $statusCode
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Envía una respuesta de error JSON estandarizada.
     *
     * @param string $message
     * @param int    $statusCode
     * @param array  $errors     Errores de validación opcionales
     */
    public static function jsonError(string $message, int $statusCode = 400, array $errors = []): void
    {
        $payload = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $statusCode);
    }

    /**
     * Envía una respuesta de éxito JSON estandarizada.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $statusCode
     */
    public static function jsonSuccess(mixed $data = null, string $message = 'OK', int $statusCode = 200): void
    {
        $payload = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        self::json($payload, $statusCode);
    }

    /**
     * Redirige a otra URL y termina la ejecución.
     *
     * @param string $url
     * @param int    $statusCode 301|302|307
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Envía un archivo para descarga.
     *
     * @param string $filePath   Ruta absoluta al archivo
     * @param string $filename   Nombre que verá el usuario
     * @param string $mimeType
     */
    public static function download(string $filePath, string $filename, string $mimeType = 'application/octet-stream'): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit;
        }

        header("Content-Type: {$mimeType}");
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    /**
     * Fuerza descarga de contenido en memoria (ej: CSV generado).
     *
     * @param string $content
     * @param string $filename
     * @param string $mimeType
     */
    public static function downloadContent(string $content, string $filename, string $mimeType = 'text/csv'): void
    {
        header("Content-Type: {$mimeType}; charset=utf-8");
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . strlen($content));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $content;
        exit;
    }

    /**
     * Muestra una página de error HTTP y termina la ejecución.
     *
     * @param int    $code
     * @param string $message
     */
    public static function abort(int $code, string $message = ''): void
    {
        http_response_code($code);

        $viewPath = defined('ROOT_PATH')
            ? ROOT_PATH . "/views/errors/{$code}.php"
            : __DIR__ . "/../../views/errors/{$code}.php";

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit;
    }

    /**
     * Devuelve los textos estándar para cada código HTTP.
     *
     * @param  int $code
     * @return string
     */
    public static function statusText(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'Unknown',
        };
    }
}
