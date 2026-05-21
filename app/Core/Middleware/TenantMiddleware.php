<?php
/**
 * TenantMiddleware — Resuelve y valida el tenant en cada petición de rutas protegidas.
 *
 * @package App\Core\Middleware
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;

class TenantMiddleware implements MiddlewareInterface
{
    /**
     * Ejecuta el middleware.
     *
     * Llama a TenantContext::resolve() para identificar al tenant
     * por su subdominio. Si no hay tenant válido en rutas que lo requieren,
     * redirige al login o muestra error 403.
     */
    public function handle(): void
    {
        $context = TenantContext::getInstance();

        // Si aún no se ha resuelto en esta petición, resolver ahora
        if (!$context->hasTenant()) {
            $context->resolve();
        }

        $user = authUser();

        // Superadmin no necesita tenant
        if ($user && $user['role'] === 'superadmin') {
            return;
        }

        // Si no hay tenant por subdominio pero el usuario tiene tenant_id,
        // cargar su tenant directamente (acceso desde dominio raíz, útil en desarrollo)
        if (!$context->hasTenant() && $user && !empty($user['tenant_id'])) {
            $tenant = Tenant::find((int) $user['tenant_id']);
            if ($tenant && $tenant['status'] !== 'suspended') {
                $context->setTenant($tenant);
                return;
            }
        }

        // Rutas admin requieren tenant activo
        if (!$context->hasTenant()) {
            header('Location: /login');
            exit;
        }
    }
}
