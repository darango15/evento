<?php
/**
 * Attendee — Modelo de participantes de eventos.
 *
 * @package App\Models
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Registrar asistente
 * $id = Attendee::create([
 *     'tenant_id'      => 1,
 *     'event_id'       => 3,
 *     'email'          => 'usuario@ejemplo.com',
 *     'full_name'      => 'Juan Pérez',
 *     'check_in_code'  => generateCheckInCode(),
 * ]);
 *
 * // Buscar por código QR
 * $attendee = Attendee::findByCode('ABC123XYZ');
 *
 * // Hacer check-in
 * Attendee::doCheckin($attendeeId);
 * ```
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Attendee extends Model
{
    protected string $table = 'attendees';

    protected array $fillable = [
        'tenant_id', 'event_id', 'user_id', 'email', 'full_name',
        'phone', 'company', 'position', 'check_in_code', 'qr_code_path',
        'status', 'dietary_restrictions', 'special_needs', 'custom_fields',
        'registration_date', 'checked_in_at',
    ];

    /**
     * Busca un asistente por su código de check-in.
     *
     * @param  string $code
     * @return array|null
     */
    public static function findByCode(string $code): ?array
    {
        return self::rawQueryFirst(
            "SELECT a.*, e.name AS event_name, e.start_date, e.venue_name
             FROM attendees a
             INNER JOIN events e ON a.event_id = e.id
             WHERE a.check_in_code = :code LIMIT 1",
            [':code' => $code]
        );
    }

    /**
     * Lista de asistentes de un evento con paginación opcional.
     *
     * @param  int         $eventId
     * @param  string|null $status
     * @param  string      $search  Texto de búsqueda por nombre/email
     * @param  int         $page
     * @param  int         $perPage
     * @return array ['data' => [], 'total' => int]
     */
    public static function paginate(
        int $eventId,
        ?string $status = null,
        string $search = '',
        int $page = 1,
        int $perPage = 25
    ): array {
        $offset = ($page - 1) * $perPage;
        $params = [':eid' => $eventId];
        $where  = ['a.event_id = :eid'];

        if ($status) {
            $where[]          = 'a.status = :status';
            $params[':status'] = $status;
        }

        if ($search) {
            $where[]          = '(a.full_name LIKE :search OR a.email LIKE :search OR a.company LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS cnt FROM attendees a {$whereClause}";
        $total    = (int)(self::rawQueryFirst($countSql, $params)['cnt'] ?? 0);

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $sql = "SELECT a.* FROM attendees a {$whereClause}
                ORDER BY a.registration_date DESC
                LIMIT :limit OFFSET :offset";

        $data = self::rawQuery($sql, $params);

        return ['data' => $data, 'total' => $total, 'pages' => ceil($total / $perPage)];
    }

    /**
     * Marca a un asistente como 'checked_in' y registra la hora.
     *
     * @param  int $attendeeId
     * @return bool
     */
    public static function doCheckin(int $attendeeId): bool
    {
        return self::update($attendeeId, [
            'status'        => 'checked_in',
            'checked_in_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verifica si ya está registrado el email en el evento.
     *
     * @param  int    $eventId
     * @param  string $email
     * @return bool
     */
    public static function isRegistered(int $eventId, string $email): bool
    {
        $row = self::rawQueryFirst(
            "SELECT id FROM attendees WHERE event_id = :eid AND email = :email LIMIT 1",
            [':eid' => $eventId, ':email' => $email]
        );
        return $row !== null;
    }

    /**
     * Resumen de asistencia por evento para el dashboard.
     *
     * @param  int $eventId
     * @return array
     */
    public static function attendanceSummary(int $eventId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'registered') AS registered,
                    SUM(status = 'checked_in') AS checked_in,
                    SUM(status = 'cancelled')  AS cancelled,
                    SUM(status = 'no_show')    AS no_show
                FROM attendees WHERE event_id = :eid";

        return self::rawQueryFirst($sql, [':eid' => $eventId]) ?? [];
    }
}
