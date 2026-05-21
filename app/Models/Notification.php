<?php
/**
 * Notification — Modelo de notificaciones persistidas en base de datos.
 *
 * Almacena todas las notificaciones enviadas o pendientes para
 * el historial, reenvíos y el panel de notificaciones en el dashboard.
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Crear una notificación
 * Notification::create([
 *     'tenant_id'   => 1,
 *     'event_id'    => 3,
 *     'type'        => 'registration_confirmation',
 *     'channel'     => 'email',
 *     'recipient'   => 'usuario@ejemplo.com',
 *     'subject'     => 'Confirmación de registro',
 *     'body'        => '...',
 *     'status'      => 'pending',
 * ]);
 *
 * // Marcar como enviada
 * Notification::markSent($id);
 *
 * // Obtener pendientes
 * $pending = Notification::getPending(50);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected string $table = 'notifications';

    protected array $fillable = [
        'tenant_id', 'event_id', 'attendee_id',
        'type', 'channel', 'recipient',
        'subject', 'body', 'metadata',
        'status', 'sent_at', 'error_message',
        'retry_count',
    ];

    /**
     * Tipos de notificación válidos.
     */
    public const TYPES = [
        'registration_confirmation',
        'agenda_saved',
        'agenda_reminder',
        'checkin_confirmed',
        'event_cancelled',
        'event_updated',
        'custom_broadcast',
    ];

    /**
     * Canales disponibles.
     */
    public const CHANNELS = ['email', 'sms', 'database', 'push'];

    // ─────────────────────────────────────────────────────────────────────────
    // Consultas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene notificaciones pendientes de envío.
     *
     * @param  int $limit
     * @return array
     */
    public static function getPending(int $limit = 50): array
    {
        return self::rawQuery(
            "SELECT * FROM notifications
             WHERE status = 'pending' AND retry_count < 3
             ORDER BY created_at ASC
             LIMIT :lim",
            [':lim' => $limit]
        );
    }

    /**
     * Obtiene el historial de notificaciones de un evento.
     *
     * @param  int    $eventId
     * @param  string $channel Filtrar por canal ('email'|'sms'|'database'|'' para todos)
     * @param  int    $limit
     * @return array
     */
    public static function byEvent(int $eventId, string $channel = '', int $limit = 100): array
    {
        if ($channel) {
            return self::rawQuery(
                "SELECT * FROM notifications
                 WHERE event_id = :eid AND channel = :ch
                 ORDER BY created_at DESC LIMIT :lim",
                [':eid' => $eventId, ':ch' => $channel, ':lim' => $limit]
            );
        }

        return self::rawQuery(
            "SELECT * FROM notifications WHERE event_id = :eid
             ORDER BY created_at DESC LIMIT :lim",
            [':eid' => $eventId, ':lim' => $limit]
        );
    }

    /**
     * Obtiene las notificaciones de un asistente específico.
     *
     * @param  int $attendeeId
     * @return array
     */
    public static function byAttendee(int $attendeeId): array
    {
        return self::rawQuery(
            "SELECT * FROM notifications
             WHERE attendee_id = :aid
             ORDER BY created_at DESC",
            [':aid' => $attendeeId]
        );
    }

    /**
     * Obtiene notificaciones del tipo 'database' no leídas para el dashboard.
     *
     * @param  int $tenantId
     * @param  int $limit
     * @return array
     */
    public static function getUnreadForDashboard(int $tenantId, int $limit = 20): array
    {
        return self::rawQuery(
            "SELECT * FROM notifications
             WHERE tenant_id = :tid AND channel = 'database' AND status = 'sent'
               AND (metadata NOT LIKE '%\"read\":true%' OR metadata IS NULL)
             ORDER BY created_at DESC LIMIT :lim",
            [':tid' => $tenantId, ':lim' => $limit]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cambios de estado
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Marca una notificación como enviada.
     *
     * @param  int $id
     * @return bool
     */
    public static function markSent(int $id): bool
    {
        return self::update($id, [
            'status'  => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marca una notificación como fallida e incrementa el contador de reintentos.
     *
     * @param  int    $id
     * @param  string $errorMessage
     * @return bool
     */
    public static function markFailed(int $id, string $errorMessage = ''): bool
    {
        $notification = self::find($id);
        if (!$notification) return false;

        $retries = (int)($notification['retry_count'] ?? 0) + 1;
        $status  = $retries >= 3 ? 'failed' : 'pending';

        return self::update($id, [
            'status'        => $status,
            'error_message' => $errorMessage,
            'retry_count'   => $retries,
        ]);
    }

    /**
     * Estadísticas de notificaciones de un evento.
     *
     * @param  int $eventId
     * @return array
     */
    public static function getStats(int $eventId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'sent')    AS sent,
                    SUM(status = 'pending') AS pending,
                    SUM(status = 'failed')  AS failed,
                    channel
                FROM notifications
                WHERE event_id = :eid
                GROUP BY channel";

        return self::rawQuery($sql, [':eid' => $eventId]);
    }

    /**
     * Verifica si ya se envió una notificación de cierto tipo a un asistente.
     * Útil para evitar notificaciones duplicadas.
     *
     * @param  int    $attendeeId
     * @param  string $type
     * @return bool
     */
    public static function alreadySent(int $attendeeId, string $type): bool
    {
        $row = self::rawQueryFirst(
            "SELECT id FROM notifications
             WHERE attendee_id = :aid AND type = :type AND status = 'sent' LIMIT 1",
            [':aid' => $attendeeId, ':type' => $type]
        );
        return $row !== null;
    }
}
