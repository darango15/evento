<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Checkin — Modelo de auditoría de check-ins.
 *
 * @package App\Models
 */
class Checkin extends Model
{
    protected string $table = 'checkins';

    protected array $fillable = [
        'attendee_id', 'session_id', 'checked_by_user_id',
        'checkin_method', 'checked_in_at', 'ip_address', 'device_info', 'notes',
    ];

    /**
     * Lista de check-ins de un evento con datos del asistente.
     *
     * @param  int $eventId
     * @return array
     */
    public static function byEvent(int $eventId): array
    {
        return self::rawQuery(
            "SELECT c.*, a.full_name, a.email, a.company,
                    u.name AS checked_by_name,
                    es.title AS session_title
             FROM checkins c
             INNER JOIN attendees a ON c.attendee_id = a.id
             LEFT JOIN users u ON c.checked_by_user_id = u.id
             LEFT JOIN event_sessions es ON c.session_id = es.id
             WHERE a.event_id = :eid
             ORDER BY c.checked_in_at DESC",
            [':eid' => $eventId]
        );
    }
}
