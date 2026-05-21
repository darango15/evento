<?php
/**
 * TenantContext — Contexto global del tenant activo.
 *
 * Singleton que resuelve y almacena el tenant actual
 * basándose en el subdominio de la petición HTTP.
 *
 * @package App\Services
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Resolver tenant al inicio de la petición
 * $context = TenantContext::getInstance();
 * $context->resolve();
 *
 * // En cualquier parte del código:
 * $tenant   = TenantContext::getInstance()->getTenant();
 * $tenantId = TenantContext::getInstance()->getId();
 * ```
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use RuntimeException;

final class TenantContext
{
    /** @var TenantContext|null */
    private static ?TenantContext $instance = null;

    /** @var array|null Tenant activo */
    private ?array $tenant = null;

    /** @var string|null Subdominio resuelto */
    private ?string $subdomain = null;

    private function __construct() {}

    /** @return self */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Resuelve el tenant a partir del subdominio de la petición.
     *
     * Extrae el subdominio comparando con TENANT_BASE_DOMAIN del .env.
     * Ejemplo: 'demo.evento.test' con base 'evento.test' → subdomain 'demo'
     *
     * @throws \RuntimeException Si el tenant no existe o está suspendido.
     */
    public function resolve(): void
    {
        $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDomain = env('TENANT_BASE_DOMAIN', 'evento.test');

        // Extraer subdominio
        if (str_ends_with($host, '.' . $baseDomain)) {
            $this->subdomain = str_replace('.' . $baseDomain, '', $host);
        } elseif (env('APP_DEBUG', 'false') === 'true' && isset($_GET['tenant'])) {
            // Fallback solo en modo debug: ?tenant=demo (nunca habilitar en producción)
            $this->subdomain = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['tenant']));
        }

        if ($this->subdomain === null && ($host === $baseDomain || $host === 'localhost' || $host === '127.0.0.1')) {
            // Host raíz real sin parámetro tenant — sin tenant (landing page o panel superadmin)
            $this->tenant    = null;
            $this->subdomain = null;
            return;
        }

        if ($this->subdomain === null) {
            return;
        }

        // Buscar tenant en BD
        $tenant = Tenant::firstWhere('subdomain', $this->subdomain);

        if (!$tenant) {
            http_response_code(404);
            $view = ROOT_PATH . '/views/errors/tenant_not_found.php';
            file_exists($view) ? require $view : die('<h1>Tenant no encontrado.</h1>');
            exit;
        }

        if ($tenant['status'] === 'suspended') {
            http_response_code(403);
            $view = ROOT_PATH . '/views/errors/tenant_suspended.php';
            file_exists($view) ? require $view : die('<h1>Esta cuenta está suspendida.</h1>');
            exit;
        }

        $this->tenant = $tenant;

        // Guardar en sesión para acceso rápido
        $_SESSION['current_tenant'] = $tenant;
    }

    /**
     * Devuelve el array del tenant activo.
     *
     * @return array|null
     */
    public function getTenant(): ?array
    {
        return $this->tenant;
    }

    /**
     * Devuelve el ID del tenant activo.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->tenant ? (int) $this->tenant['id'] : null;
    }

    /**
     * Devuelve el subdominio resuelto.
     *
     * @return string|null
     */
    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    /**
     * Verifica si hay un tenant activo.
     *
     * @return bool
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Establece un tenant manualmente (útil para tests/superadmin).
     *
     * @param array $tenant
     */
    public function setTenant(array $tenant): void
    {
        $this->tenant              = $tenant;
        $_SESSION['current_tenant'] = $tenant;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton.');
    }
}
