<?php
/**
 * RoleMiddleware — Controla acceso basado en roles (RBAC).
 *
 * Verifica que el usuario autenticado tenga uno de los roles permitidos.
 * Debe usarse después de AuthMiddleware.
 *
 * Jerarquía de roles: owner > admin > staff > attendee
 *
 * @package App\Core\Middleware
 * @version 1.0.0
 *
 * @example
 * ```php
 * // En el Router, para rutas que solo owner/admin pueden usar:
 * $router->delete('/admin/events/{id}', [EventController::class, 'destroy'], ['auth', 'role:owner,admin']);
 *
 * // Crear el middleware con los roles requeridos:
 * $mw = new RoleMiddleware(['owner', 'admin']);
 * $mw->handle();
 * ```
 */

declare(strict_types=1);

namespace App\Core\Middleware;

class RoleMiddleware implements MiddlewareInterface
{
    /** @var string[] Roles que tienen acceso a esta ruta */
    private array $allowedRoles;

    /**
     * @param string[] $allowedRoles Lista de roles permitidos, ej: ['owner', 'admin']
     */
    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Verifica que el usuario tenga el rol requerido.
     * Responde 403 si no tiene permisos.
     */
    public function handle(): void
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            // AuthMiddleware debería haber capturado esto antes
            header('Location: /login');
            exit;
        }

        $userRole = $user['role'] ?? 'attendee';

        // Si no hay roles específicos configurados, permitir acceso
        if (empty($this->allowedRoles)) {
            return;
        }

        // Verificar si el rol del usuario tiene nivel suficiente
        if (!$this->hasRequiredRole($userRole, $this->allowedRoles)) {
            http_response_code(403);
            $view = defined('ROOT_PATH') ? ROOT_PATH . '/views/errors/403.php' : '';
            if ($view && file_exists($view)) {
                require $view;
            } else {
                echo '<h1>403 — Acceso Denegado</h1><p>No tienes permisos para acceder a esta sección.</p>';
            }
            exit;
        }
    }

    /**
     * Crea una instancia a partir de una cadena de roles separados por coma.
     * Útil para el Router que parsea alias como 'role:owner,admin'.
     *
     * @param  string $rolesString Ej: 'owner,admin'
     * @return self
     */
    public static function fromString(string $rolesString): self
    {
        $roles = array_map('trim', explode(',', $rolesString));
        return new self($roles);
    }

    /**
     * Verifica si el rol del usuario satisface los roles requeridos
     * respetando la jerarquía: owner > admin > staff > attendee.
     *
     * @param  string   $userRole
     * @param  string[] $allowedRoles
     * @return bool
     */
    private function hasRequiredRole(string $userRole, array $allowedRoles): bool
    {
        // Comprobación directa: si el rol está en la lista
        if (in_array($userRole, $allowedRoles, true)) {
            return true;
        }

        // Jerarquía de roles (mayor nivel incluye los inferiores)
        $hierarchy = [
            'owner'    => 4,
            'admin'    => 3,
            'staff'    => 2,
            'attendee' => 1,
        ];

        $userLevel = $hierarchy[$userRole] ?? 0;

        // Si alguno de los roles permitidos requiere nivel menor o igual, dejar pasar
        // (owner puede hacer lo que admin puede, etc.)
        foreach ($allowedRoles as $role) {
            $requiredLevel = $hierarchy[$role] ?? 0;
            if ($userLevel >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper estático para verificar roles sin instanciar el middleware.
     *
     * @param  string|array $roles
     * @return bool
     */
    public static function check(string|array $roles): bool
    {
        $roles    = is_array($roles) ? $roles : [$roles];
        $user     = $_SESSION['user'] ?? null;
        $userRole = $user['role'] ?? 'attendee';

        $mw = new self($roles);
        return $mw->hasRequiredRole($userRole, $roles);
    }
}
