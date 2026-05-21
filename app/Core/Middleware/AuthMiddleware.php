<?php
/**
 * AuthMiddleware — Verifica que el usuario esté autenticado.
 *
 * @package App\Core\Middleware
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Core\Middleware;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Verifica si el usuario tiene sesión activa.
     * Si no, lo redirige al login guardando la URL original.
     */
    public function handle(): void
    {
        if (empty($_SESSION['user'])) {
            // Guardar la URL intentada para redirigir después del login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/admin';
            header('Location: /login');
            exit;
        }

        // Verificar que la sesión no haya expirado
        $lifetime = (int) env('SESSION_LIFETIME', 7200);

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['flash']['warning'][] = 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.';
            header('Location: /login');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }
}
