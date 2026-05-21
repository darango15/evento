<?php
/**
 * AuthController — Manejo de login/logout.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\TenantContext;

class AuthController extends Controller
{
    /**
     * GET /login
     * Muestra el formulario de inicio de sesión.
     */
    public function showLogin(): void
    {
        // Si ya está logueado, redirigir al panel
        if (!empty($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
            $this->redirect('/admin/dashboard');
        }

        $this->render('auth/login', [
            'title'       => 'Iniciar Sesión — EventoSaaS',
            'pageTitle'   => 'Acceso al Panel',
        ], 'auth');
    }

    /**
     * POST /login
     * Procesa las credenciales de inicio de sesión.
     */
    public function login(): void
    {
        $this->validateCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validación básica de campos
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Ingresa tu correo y contraseña.');
            $this->redirect('/login');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'El correo electrónico no es válido.');
            $this->redirect('/login');
            return;
        }

        // Resolver tenant del contexto actual
        $context  = TenantContext::getInstance();
        $tenantId = $context->hasTenant() ? $context->getId() : null;

        // Intentar autenticar
        $user = User::authenticate($email, $password, $tenantId);

        if (!$user) {
            // Pequeño delay para dificultar ataques de fuerza bruta
            sleep(1);
            $this->flash('error', 'Credenciales incorrectas. Verifica tu email y contraseña.');
            $this->redirect('/login');
            return;
        }

        // Regenerar ID de sesión por seguridad (previene session fixation)
        session_regenerate_id(true);

        // Almacenar usuario en sesión
        $_SESSION['user']          = $user;
        $_SESSION['last_activity'] = time();

        // Redirigir a la URL intentada o al dashboard (solo rutas internas)
        $intended = $_SESSION['intended_url'] ?? '/admin/dashboard';
        unset($_SESSION['intended_url']);
        // Bloquear redirecciones externas (open redirect)
        if (!preg_match('#^/[^/]#', $intended) && $intended !== '/') {
            $intended = '/admin/dashboard';
        }

        $this->flash('success', '¡Bienvenido, ' . e($user['name']) . '!');
        $this->redirect($intended);
    }

    /**
     * GET /logout
     * Cierra la sesión y redirige al login.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();

        session_start();
        $this->flash('info', 'Has cerrado sesión correctamente.');
        $this->redirect('/login');
    }
}
