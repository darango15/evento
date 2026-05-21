<?php
/**
 * Front Controller — Punto de entrada único de la aplicación.
 *
 * Todo el tráfico HTTP es redirigido aquí por .htaccess.
 * Se encarga de:
 *   1. Cargar variables de entorno
 *   2. Iniciar sesión PHP
 *   3. Registrar el autoloader de Composer
 *   4. Configurar manejo global de errores
 *   5. Despachar la petición a través del Router
 *
 * @version 1.0.0
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// Constantes de rutas
// ─────────────────────────────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');
define('VIEW_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', __DIR__);

// ─────────────────────────────────────────────────────────────────────────────
// Autoloader de Composer (PSR-4 + helpers)
// ─────────────────────────────────────────────────────────────────────────────
$autoloader = ROOT_PATH . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    http_response_code(503);
    die('<h1>503 — Dependencias no instaladas</h1><p>Ejecuta: <code>composer install</code></p>');
}

require_once $autoloader;

// ─────────────────────────────────────────────────────────────────────────────
// Variables de entorno
// ─────────────────────────────────────────────────────────────────────────────
$envFile = ROOT_PATH . '/.env';

if (!file_exists($envFile)) {
    http_response_code(503);
    die('<h1>503 — Configuración incompleta</h1><p>Crea el archivo <code>.env</code> a partir de <code>.env.example</code> o ejecuta el <a href="/install.php">instalador</a>.</p>');
}

loadEnv($envFile);

// ─────────────────────────────────────────────────────────────────────────────
// CORS Headers (Permitir peticiones de la PWA)
// ─────────────────────────────────────────────────────────────────────────────
$allowedOrigins = array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', ''))));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true))) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (!$origin && !empty($allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Vary: Origin');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// Configuración de PHP
// ─────────────────────────────────────────────────────────────────────────────
$timezone = env('APP_TIMEZONE', 'America/Mexico_City');
date_default_timezone_set($timezone);

ini_set('display_errors', env('APP_DEBUG', 'false') === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/' . env('LOG_FILE', 'logs/app.log'));
error_reporting(E_ALL);

// ─────────────────────────────────────────────────────────────────────────────
// Sesión
// ─────────────────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME', 'evento_session'));
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// Manejo global de excepciones
// ─────────────────────────────────────────────────────────────────────────────
set_exception_handler(function (\Throwable $e) {
    appLog('error', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);

    // Rutas API siempre responden JSON
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_starts_with(strtok($uri, '?'), '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => isDebug() ? $e->getMessage() : 'Error interno del servidor.',
        ]);
        exit(1);
    }

    if (isDebug()) {
        echo '<pre style="background:#1a1a2e;color:#e94560;padding:2rem;font-family:monospace;">';
        echo '<strong>Error 500:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
        echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        echo '</pre>';
    } else {
        $viewFile = VIEW_PATH . '/errors/500.php';
        file_exists($viewFile) ? require $viewFile : require_once VIEW_PATH . '/errors/500.php';
    }

    exit(1);
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) return false;

    appLog('warning', "[PHP Error {$errno}] {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
    ]);

    return false; // Manejo estándar de PHP también
});

// ─────────────────────────────────────────────────────────────────────────────
// Bootstrap de servicios globales
// ─────────────────────────────────────────────────────────────────────────────
use App\Core\Router;
use App\Services\TenantContext;

TenantContext::getInstance()->resolve();

$router = new Router();

// Redirigir /install al instalador web
$router->get('/install', fn() => header('Location: /install.php'));

// ─────────────────────────────────────────────────────────────────────────────
// Cargar definición de rutas
// ─────────────────────────────────────────────────────────────────────────────
require_once APP_PATH . '/Config/routes.php';

// ─────────────────────────────────────────────────────────────────────────────
// Despachar petición
// ─────────────────────────────────────────────────────────────────────────────
$router->dispatch();
