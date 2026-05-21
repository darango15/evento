<?php
/**
 * Event — Modelo de eventos.
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Eventos publicados de un tenant
 * $events = Event::byTenant(1, 'published');
 *
 * // Buscar por slug
 * $event = Event::findBySlug(1, 'tech-summit-2025');
 *
 * // Estadísticas del evento
 * $stats = Event::getStats(5);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    protected string $table = 'events';

    protected array $fillable = [
        'tenant_id', 'slug', 'name', 'description', 'cover_image',
        'start_date', 'end_date', 'timezone', 'venue_name', 'venue_address',
        'venue_lat', 'venue_lng', 'max_capacity', 'is_virtual', 'virtual_link',
        'status', 'settings',
    ];

    /**
     * Eventos de un tenant con filtro opcional de status.
     *
     * @param  int         $tenantId
     * @param  string|null $status   'draft'|'published'|'cancelled'|'completed'|null
     * @return array
     */
    public static function byTenant(int $tenantId, ?string $status = null): array
    {
        if ($status) {
            return self::rawQuery(
                "SELECT * FROM events WHERE tenant_id = :tid AND status = :status ORDER BY start_date DESC",
                [':tid' => $tenantId, ':status' => $status]
            );
        }

        return self::rawQuery(
            "SELECT * FROM events WHERE tenant_id = :tid ORDER BY start_date DESC",
            [':tid' => $tenantId]
        );
    }

    /**
     * Busca un evento por su slug dentro de un tenant.
     *
     * @param  int    $tenantId
     * @param  string $slug
     * @return array|null
     */
    public static function findBySlug(int $tenantId, string $slug): ?array
    {
        return self::rawQueryFirst(
            "SELECT * FROM events WHERE tenant_id = :tid AND slug = :slug LIMIT 1",
            [':tid' => $tenantId, ':slug' => $slug]
        );
    }

    /**
     * Verifica que el slug sea único dentro del tenant (excluyendo un ID).
     *
     * @param  int    $tenantId
     * @param  string $slug
     * @param  int    $excludeId
     * @return bool
     */
    public static function slugExists(int $tenantId, string $slug, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM events WHERE tenant_id = :tid AND slug = :slug AND id != :eid LIMIT 1";
        $row = self::rawQueryFirst($sql, [':tid' => $tenantId, ':slug' => $slug, ':eid' => $excludeId]);
        return $row !== null;
    }

    /**
     * Estadísticas del evento: sesiones, asistentes registrados, check-ins.
     *
     * @param  int $eventId
     * @return array
     */
    public static function getStats(int $eventId): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM event_sessions WHERE event_id = :eid1) AS total_sessions,
                (SELECT COUNT(*) FROM attendees WHERE event_id = :eid2 AND status != 'cancelled') AS total_attendees,
                (SELECT COUNT(*) FROM attendees WHERE event_id = :eid3 AND status = 'checked_in') AS checked_in,
                (SELECT COUNT(*) FROM attendees WHERE event_id = :eid4 AND status = 'registered') AS pending,
                (SELECT COUNT(*) FROM sponsors WHERE event_id = :eid5) AS total_sponsors
        ";

        return self::rawQueryFirst($sql, [
            ':eid1' => $eventId,
            ':eid2' => $eventId,
            ':eid3' => $eventId,
            ':eid4' => $eventId,
            ':eid5' => $eventId
        ]) ?? [];
    }

    /**
     * Devuelve los próximos eventos publicados (para la página pública).
     *
     * @param  int $tenantId
     * @param  int $limit
     * @return array
     */
    public static function upcoming(int $tenantId, int $limit = 10): array
    {
        return self::rawQuery(
            "SELECT * FROM events
             WHERE tenant_id = :tid AND status = 'published' AND end_date >= CURDATE()
             ORDER BY start_date ASC LIMIT :lim",
            [':tid' => $tenantId, ':lim' => $limit]
        );
    }

    /**
     * Verifica si el evento tiene capacidad disponible.
     *
     * @param  int $eventId
     * @return bool
     */
    public static function hasCapacity(int $eventId): bool
    {
        $event = self::find($eventId);
        if (!$event || $event['max_capacity'] === null) return true;

        $registered = self::rawQueryFirst(
            "SELECT COUNT(*) AS cnt FROM attendees WHERE event_id = :eid AND status IN ('registered','checked_in')",
            [':eid' => $eventId]
        );

        return ((int)($registered['cnt'] ?? 0)) < (int)$event['max_capacity'];
    }

    /**
     * Decodifica el JSON de settings del evento.
     *
     * @param  array $event
     * @return array
     */
    public static function decodeSettings(array $event): array
    {
        if (!empty($event['settings']) && is_string($event['settings'])) {
            $event['settings'] = json_decode($event['settings'], true) ?? [];
        }
        return $event;
    }
}
