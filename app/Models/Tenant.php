<?php
/**
 * Tenant — Modelo de tenants (organizadores).
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Buscar por subdominio
 * $tenant = Tenant::findBySubdomain('demo');
 *
 * // Verificar plan
 * if (Tenant::canCreateEvent($tenantId)) { ... }
 *
 * // Todos los tenants activos
 * $active = Tenant::getActive();
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Tenant extends Model
{
    protected string $table = 'tenants';

    protected array $fillable = [
        'subdomain', 'name', 'email', 'phone', 'logo',
        'plan', 'status', 'trial_ends_at', 'settings',
    ];

    /**
     * Encuentra un tenant por su subdominio.
     *
     * @param  string $subdomain
     * @return array|null
     */
    public static function findBySubdomain(string $subdomain): ?array
    {
        return self::firstWhere('subdomain', $subdomain);
    }

    /**
     * Devuelve todos los tenants activos.
     *
     * @return array
     */
    public static function getActive(): array
    {
        return self::rawQuery(
            "SELECT * FROM tenants WHERE status IN ('active', 'trial') ORDER BY name ASC"
        );
    }

    /**
     * Estadísticas del tenant: eventos, sesiones, asistentes.
     *
     * @param  int $tenantId
     * @return array
     */
    public static function getStats(int $tenantId): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM events WHERE tenant_id = :tid1) AS total_events,
                (SELECT COUNT(*) FROM events WHERE tenant_id = :tid2 AND status = 'published') AS published_events,
                (SELECT COUNT(*) FROM attendees WHERE tenant_id = :tid3) AS total_attendees,
                (SELECT COUNT(*) FROM attendees WHERE tenant_id = :tid4 AND status = 'checked_in') AS checked_in,
                (SELECT COUNT(*) FROM event_sessions es
                    INNER JOIN events e ON es.event_id = e.id
                    WHERE e.tenant_id = :tid5) AS total_sessions
        ";

        return self::rawQueryFirst($sql, [
            ':tid1' => $tenantId,
            ':tid2' => $tenantId,
            ':tid3' => $tenantId,
            ':tid4' => $tenantId,
            ':tid5' => $tenantId
        ]) ?? [];
    }

    /**
     * Verifica si el tenant puede crear más eventos según su plan.
     *
     * @param  int $tenantId
     * @return bool
     */
    public static function canCreateEvent(int $tenantId): bool
    {
        $tenant = self::find($tenantId);
        if (!$tenant) return false;

        $limits = [
            'basic'      => 3,
            'pro'        => 50,
            'enterprise' => PHP_INT_MAX,
        ];

        $limit = $limits[$tenant['plan']] ?? 3;
        $count = self::rawQueryFirst(
            "SELECT COUNT(*) AS cnt FROM events WHERE tenant_id = :tid",
            [':tid' => $tenantId]
        );

        return ($count['cnt'] ?? 0) < $limit;
    }

    /**
     * Decodifica el campo JSON settings.
     *
     * @param  array $tenant
     * @return array
     */
    public static function decodeSettings(array $tenant): array
    {
        if (!empty($tenant['settings']) && is_string($tenant['settings'])) {
            $tenant['settings'] = json_decode($tenant['settings'], true) ?? [];
        }
        return $tenant;
    }
}
