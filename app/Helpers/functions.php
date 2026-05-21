<?php
/**
 * Funciones helper globales de la aplicación.
 *
 * Disponibles en todo el proyecto gracias al autoload "files" en composer.json.
 *
 * @package App\Helpers
 * @version 1.0.0
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// Entorno
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Obtiene una variable de entorno con valor por defecto.
 *
 * @param  string $key
 * @param  mixed  $default
 * @return mixed
 *
 * @example env('DB_HOST', '127.0.0.1')
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Carga un archivo .env desde la ruta dada.
 * Usa el parser simple (key=value) sin dependencias externas.
 *
 * @param string $filePath Ruta absoluta al .env
 */
function loadEnv(string $filePath): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorar comentarios
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'"); // quitar comillas

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Seguridad
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Escapa output HTML para prevenir XSS.
 *
 * @param  mixed $value
 * @return string
 *
 * @example echo e($user['name']);
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Genera o retorna el token CSRF de la sesión actual.
 *
 * @return string
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Devuelve el campo oculto CSRF para formularios.
 *
 * @return string HTML del input
 */
function csrfField(): string
{
    return '<input type="hidden" name="_token" value="' . csrfToken() . '">';
}

/**
 * Devuelve el campo oculto para method override (PUT, DELETE).
 *
 * @param  string $method PUT|DELETE
 * @return string
 */
function methodField(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}

// ─────────────────────────────────────────────────────────────────────────────
// Strings y URLs
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Convierte un string a slug URL-friendly.
 *
 * @param  string $text
 * @return string
 *
 * @example slugify('Tech Summit México 2025') → 'tech-summit-mexico-2025'
 */
function slugify(string $text): string
{
    // Transliterar caracteres especiales
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Genera una URL completa con el base_url de la aplicación.
 *
 * @param  string $path
 * @return string
 *
 * @example url('/admin/events') → 'http://evento.test/admin/events'
 */
function url(string $path = ''): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Genera una URL a un asset estático.
 *
 * @param  string $path Ruta relativa a /public/assets/
 * @return string
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

// ─────────────────────────────────────────────────────────────────────────────
// Sesión y Flash
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Obtiene y elimina los mensajes flash de la sesión.
 *
 * @param  string|null $type Si null, devuelve todos los tipos
 * @return array
 */
function flashMessages(?string $type = null): array
{
    $all = $_SESSION['flash'] ?? [];

    if ($type !== null) {
        $messages = $all[$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }

    unset($_SESSION['flash']);
    return $all;
}

/**
 * Verifica si hay mensajes flash pendientes.
 *
 * @param  string|null $type
 * @return bool
 */
function hasFlash(?string $type = null): bool
{
    if ($type !== null) {
        return !empty($_SESSION['flash'][$type]);
    }
    return !empty($_SESSION['flash']);
}

// ─────────────────────────────────────────────────────────────────────────────
// Fechas
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Formatea una fecha/datetime de MySQL a formato legible en español.
 *
 * @param  string $date   Fecha en formato Y-m-d o Y-m-d H:i:s
 * @param  bool   $time   Si incluir la hora
 * @return string
 *
 * @example formatDate('2025-09-15') → '15 sep 2025'
 */
function formatDate(string $date, bool $time = false): string
{
    if (empty($date)) return '—';

    $dt = new \DateTime($date);

    if ($time) {
        return $dt->format('d/m/Y H:i');
    }

    $months = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
        5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
        9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    return $dt->format('d') . ' ' . $months[(int)$dt->format('n')] . ' ' . $dt->format('Y');
}

// ─────────────────────────────────────────────────────────────────────────────
// Paginación y arrays
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Genera un código único aleatorio para check-in (32 chars hex).
 *
 * @return string
 */
function generateCheckInCode(): string
{
    return strtoupper(bin2hex(random_bytes(16)));
}

/**
 * Trunca un texto a N caracteres con sufijo.
 *
 * @param  string $text
 * @param  int    $length
 * @param  string $suffix
 * @return string
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Devuelve el usuario autenticado de la sesión.
 *
 * @return array|null
 */
function authUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica si el usuario tiene un rol específico.
 *
 * @param  string|array $roles
 * @return bool
 */
function userHasRole(string|array $roles): bool
{
    $user = authUser();
    if (!$user) return false;

    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'] ?? '', $roles, true);
}

/**
 * Obtiene el tenant activo del contexto de la petición.
 *
 * @return array|null
 */
function currentTenant(): ?array
{
    return $_SESSION['current_tenant'] ?? null;
}

/**
 * Obtiene el ID del tenant activo.
 *
 * @return int|null
 */
function tenantId(): ?int
{
    $tenant = currentTenant();
    return $tenant ? (int) $tenant['id'] : null;
}

/**
 * Verifica si la aplicación está en modo debug.
 */
function isDebug(): bool
{
    return env('APP_DEBUG', 'false') === 'true';
}

/**
 * Registra un mensaje en el log de la aplicación.
 *
 * @param string $level   debug|info|warning|error
 * @param string $message
 * @param array  $context
 */
function appLog(string $level, string $message, array $context = []): void
{
    $logFile = ROOT_PATH . '/' . env('LOG_FILE', 'logs/app.log');
    $logDir  = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ctx       = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $line      = "[{$timestamp}] [{$level}] {$message}{$ctx}" . PHP_EOL;

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
