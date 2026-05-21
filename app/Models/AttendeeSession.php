<?php
/**
 * AttendeeSession — Modelo de agenda personal del participante.
 *
 * Gestiona la relación entre asistentes y las sesiones a las que
 * se han inscrito dentro de un evento (agenda personal).
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Inscribir a una sesión
 * AttendeeSession::register($attendeeId, $sessionId);
 *
 * // Ver agenda del participante
 * $agenda = AttendeeSession::getAgenda($attendeeId, $eventId);
 *
 * // Cancelar inscripción
 * AttendeeSession::cancel($attendeeId, $sessionId);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AttendeeSession extends Model
{
    protected string $table = 'attendee_sessions';

    protected array $fillable = [
        'attendee_id', 'session_id', 'event_id', 'tenant_id',
        'status', 'registered_at', 'checkin_at', 'checkin_method',
    ];

    /**
     * Registra a un asistente en una sesión.
     * Verifica cupo antes de registrar.
     *
     * @param  int $attendeeId
     * @param  int $sessionId
     * @return array ['success' => bool, 'message' => string]
     */
    public static function register(int $attendeeId, int $sessionId): array
    {
        // Verificar que no esté ya registrado
        if (self::isRegistered($attendeeId, $sessionId)) {
            return ['success' => false, 'message' => 'Ya estás inscrito en esta sesión.'];
        }

        // Verificar cupo disponible
        $session = EventSession::find($sessionId);
        if (!$session) {
            return ['success' => false, 'message' => 'Sesión no encontrada.'];
        }

        if ($session['max_capacity'] !== null) {
            $enrolled = self::countEnrolled($sessionId);
            if ($enrolled >= (int)$session['max_capacity']) {
                return ['success' => false, 'message' => 'Esta sesión ya no tiene cupo disponible.'];
            }
        }

        // Verificar que el asistente pertenezca al mismo evento
        $attendee = Attendee::find($attendeeId);
        if (!$attendee || (int)$attendee['event_id'] !== (int)$session['event_id']) {
            return ['success' => false, 'message' => 'No puedes inscribirte a sesiones de otro evento.'];
        }

        // Verificar conflicto de horario
        if (self::hasTimeConflict($attendeeId, $session)) {
            return ['success' => false, 'message' => 'Ya tienes una sesión agendada en ese horario.'];
        }

        self::create([
            'attendee_id'   => $attendeeId,
            'session_id'    => $sessionId,
            'event_id'      => $session['event_id'],
            'tenant_id'     => $attendee['tenant_id'],
            'status'        => 'registered',
            'registered_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'message' => 'Inscrito correctamente.'];
    }

    /**
     * Cancela la inscripción de un asistente a una sesión.
     *
     * @param  int $attendeeId
     * @param  int $sessionId
     * @return bool
     */
    public static function cancel(int $attendeeId, int $sessionId): bool
    {
        $sql = "UPDATE attendee_sessions SET status = 'cancelled'
                WHERE attendee_id = :aid AND session_id = :sid";
        self::db()->query($sql, [':aid' => $attendeeId, ':sid' => $sessionId]);
        return true;
    }

    /**
     * Verifica si un asistente ya está inscrito en una sesión.
     *
     * @param  int $attendeeId
     * @param  int $sessionId
     * @return bool
     */
    public static function isRegistered(int $attendeeId, int $sessionId): bool
    {
        $row = self::rawQueryFirst(
            "SELECT id FROM attendee_sessions
             WHERE attendee_id = :aid AND session_id = :sid AND status != 'cancelled' LIMIT 1",
            [':aid' => $attendeeId, ':sid' => $sessionId]
        );
        return $row !== null;
    }

    /**
     * Cuenta los asistentes inscritos en una sesión.
     *
     * @param  int $sessionId
     * @return int
     */
    public static function countEnrolled(int $sessionId): int
    {
        $row = self::rawQueryFirst(
            "SELECT COUNT(*) AS cnt FROM attendee_sessions
             WHERE session_id = :sid AND status != 'cancelled'",
            [':sid' => $sessionId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Obtiene la agenda personal completa de un asistente para un evento.
     *
     * @param  int $attendeeId
     * @param  int $eventId
     * @return array Sesiones agendadas ordenadas por hora de inicio
     */
    public static function getAgenda(int $attendeeId, int $eventId): array
    {
        return self::rawQuery(
            "SELECT es.*, att_s.status AS enrollment_status, att_s.id AS enrollment_id,
                    att_s.checkin_at, att_s.checkin_method
             FROM attendee_sessions att_s
             INNER JOIN event_sessions es ON att_s.session_id = es.id
             WHERE att_s.attendee_id = :aid AND att_s.event_id = :eid
               AND att_s.status != 'cancelled'
             ORDER BY es.start_time ASC",
            [':aid' => $attendeeId, ':eid' => $eventId]
        );
    }

    /**
     * Registra el check-in de un asistente en una sesión específica.
     *
     * @param  int    $attendeeId
     * @param  int    $sessionId
     * @param  string $method  'qr_code'|'manual'|'mobile'|'kiosk'
     * @return bool
     */
    public static function doCheckin(int $attendeeId, int $sessionId, string $method = 'manual'): bool
    {
        // Verificar que ya esté inscrito
        if (!self::isRegistered($attendeeId, $sessionId)) {
            return false;
        }

        // Verificar que no haya hecho check-in ya
        $existing = self::rawQueryFirst(
            "SELECT id FROM attendee_sessions
             WHERE attendee_id = :aid AND session_id = :sid AND checkin_at IS NOT NULL LIMIT 1",
            [':aid' => $attendeeId, ':sid' => $sessionId]
        );
        if ($existing) {
            return false;
        }

        $sql = "UPDATE attendee_sessions
                SET status = 'attended', checkin_at = :now, checkin_method = :method
                WHERE attendee_id = :aid AND session_id = :sid";

        self::db()->query($sql, [
            ':now'    => date('Y-m-d H:i:s'),
            ':method' => $method,
            ':aid'    => $attendeeId,
            ':sid'    => $sessionId,
        ]);

        return true;
    }

    /**
     * Obtiene los IDs de sesiones agendadas por un asistente.
     *
     * @param  int $attendeeId
     * @return int[]
     */
    public static function getSessionIds(int $attendeeId): array
    {
        $rows = self::rawQuery(
            "SELECT session_id FROM attendee_sessions
             WHERE attendee_id = :aid AND status != 'cancelled'",
            [':aid' => $attendeeId]
        );
        return array_column($rows, 'session_id');
    }

    /**
     * Verifica si agregar una sesión causaría un conflicto de horario.
     *
     * @param  int   $attendeeId
     * @param  array $newSession  Array con start_time y end_time
     * @return bool
     */
    private static function hasTimeConflict(int $attendeeId, array $newSession): bool
    {
        if (empty($newSession['start_time']) || empty($newSession['end_time'])) {
            return false;
        }

        $conflicts = self::rawQuery(
            "SELECT es.id FROM attendee_sessions att_s
             INNER JOIN event_sessions es ON att_s.session_id = es.id
             WHERE att_s.attendee_id = :aid
               AND att_s.status != 'cancelled'
               AND es.event_id = :eid
               AND es.start_time < :end_time
               AND es.end_time > :start_time",
            [
                ':aid'        => $attendeeId,
                ':eid'        => $newSession['event_id'],
                ':start_time' => $newSession['start_time'],
                ':end_time'   => $newSession['end_time'],
            ]
        );

        return !empty($conflicts);
    }
}
