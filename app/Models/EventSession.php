<?php
/**
 * EventSession — Modelo de sesiones/charlas de la agenda.
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * $sessions = EventSession::byEvent(5);
 * $session  = EventSession::find(12);
 * $count    = EventSession::countAttendees(12);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class EventSession extends Model
{
    protected string $table = 'event_sessions';

    protected array $fillable = [
        'event_id', 'title', 'description', 'type',
        'speaker_name', 'speaker_bio', 'speaker_photo',
        'start_time', 'end_time', 'room', 'max_attendees',
        'is_virtual', 'virtual_link', 'materials', 'status', 'sort_order',
    ];

    /**
     * Sesiones de un evento ordenadas por hora de inicio.
     *
     * @param  int         $eventId
     * @param  string|null $status  Filtro opcional por estado
     * @return array
     */
    public static function byEvent(int $eventId, ?string $status = null): array
    {
        if ($status) {
            return self::rawQuery(
                "SELECT * FROM event_sessions WHERE event_id = :eid AND status = :status ORDER BY start_time ASC, sort_order ASC",
                [':eid' => $eventId, ':status' => $status]
            );
        }

        return self::rawQuery(
            "SELECT * FROM event_sessions WHERE event_id = :eid ORDER BY start_time ASC, sort_order ASC",
            [':eid' => $eventId]
        );
    }

    /**
     * Cuenta los asistentes registrados a una sesión.
     *
     * @param  int $sessionId
     * @return int
     */
    public static function countAttendees(int $sessionId): int
    {
        $row = self::rawQueryFirst(
            "SELECT COUNT(*) AS cnt FROM attendee_sessions
             WHERE session_id = :sid AND status != 'cancelled'",
            [':sid' => $sessionId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Obtiene la lista de asistentes inscritos en una sesión.
     *
     * @param  int $sessionId
     * @return array
     */
    public static function getAttendees(int $sessionId): array
    {
        return self::rawQuery(
            "SELECT a.id, a.full_name, a.email, a.company, att_s.status, att_s.checkin_at
             FROM attendee_sessions att_s
             INNER JOIN attendees a ON att_s.attendee_id = a.id
             WHERE att_s.session_id = :sid AND att_s.status != 'cancelled'
             ORDER BY a.full_name ASC",
            [':sid' => $sessionId]
        );
    }

    /**
     * Verifica si hay cupo disponible en la sesión.
     *
     * @param  int $sessionId
     * @return bool
     */
    public static function hasCapacity(int $sessionId): bool
    {
        $session = self::find($sessionId);
        if (!$session || $session['max_attendees'] === null) return true;

        return self::countAttendees($sessionId) < (int)$session['max_attendees'];
    }

    /**
     * Sesiones agrupadas por día (para mostrar agenda).
     *
     * @param  int $eventId
     * @return array Indexado por fecha 'Y-m-d'
     */
    public static function groupedByDay(int $eventId): array
    {
        $sessions = self::byEvent($eventId, 'scheduled');
        $grouped  = [];

        foreach ($sessions as $session) {
            $day = substr($session['start_time'], 0, 10); // Y-m-d
            $grouped[$day][] = $session;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Decodifica el JSON de materiales.
     *
     * @param  array $session
     * @return array
     */
    public static function decodeMaterials(array $session): array
    {
        if (!empty($session['materials']) && is_string($session['materials'])) {
            $session['materials'] = json_decode($session['materials'], true) ?? [];
        }
        return $session;
    }
}
