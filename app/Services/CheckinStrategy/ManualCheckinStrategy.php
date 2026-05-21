<?php
/**
 * ManualCheckinStrategy — Check-in manual por email o nombre del asistente.
 *
 * Permite al staff buscar y hacer check-in sin escanear QR.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 *
 * @example
 * ```php
 * $strategy = new ManualCheckinStrategy();
 * $result   = $strategy->process('juan@ejemplo.com', null, [
 *     'event_id' => 3,
 *     'user_id'  => 2
 * ]);
 * ```
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

use App\Models\Attendee;
use App\Models\Checkin;
use App\Core\Database;

class ManualCheckinStrategy implements CheckinStrategyInterface
{
    public function getName(): string
    {
        return 'manual';
    }

    /**
     * Check-in manual por email del asistente.
     *
     * @param  string   $identifier  Email del asistente
     * @param  int|null $sessionId
     * @param  array    $context     Requiere 'event_id'
     * @return array
     */
    public function process(string $identifier, ?int $sessionId, array $context = []): array
    {
        $identifier = trim($identifier);
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $eventId    = (int)($context['event_id'] ?? 0);

        if (empty($identifier)) {
            return $this->fail('El identificador está vacío.');
        }

        if (!$eventId) {
            return $this->fail('ID de evento no proporcionado en el contexto.');
        }

        // Buscar por email o por código
        if ($isEmail) {
            $attendee = Attendee::rawQueryFirst(
                "SELECT a.*, e.name AS event_name, e.start_date, e.venue_name
                 FROM attendees a
                 INNER JOIN events e ON a.event_id = e.id
                 WHERE a.email = :email AND a.event_id = :eid LIMIT 1",
                [':email' => strtolower($identifier), ':eid' => $eventId]
            );
        } else {
            $attendee = Attendee::findByCode(strtoupper($identifier));
        }

        if (!$attendee) {
            return $this->fail("No se encontró un asistente con '{$identifier}' en este evento.");
        }

        if ($attendee['status'] === 'cancelled') {
            return $this->fail('Este registro fue cancelado.', $attendee);
        }

        if ($attendee['status'] === 'checked_in') {
            return [
                'success'    => false,
                'already_in' => true,
                'message'    => '⚠️ Este asistente ya realizó check-in anteriormente.',
                'attendee'   => $attendee,
            ];
        }

        // Hacer check-in
        Attendee::doCheckin((int)$attendee['id']);

        // Si hay sesión, actualizar esa sesión.
        if ($sessionId) {
            $db  = Database::getInstance();
            $sql = "UPDATE attendee_sessions SET status = 'attended', checkin_at = NOW()
                    WHERE attendee_id = :aid AND session_id = :sid";
            $db->query($sql, [':aid' => (int)$attendee['id'], ':sid' => $sessionId]);
        }

        // Auditoría
        Checkin::create([
            'attendee_id'        => $attendee['id'],
            'session_id'         => $sessionId,
            'checked_by_user_id' => $context['user_id'] ?? null,
            'checkin_method'     => $this->getName(),
            'checked_in_at'      => date('Y-m-d H:i:s'),
            'ip_address'         => $context['ip'] ?? null,
            'notes'              => $isEmail ? 'Check-in manual por email' : 'Check-in manual por código',
        ]);

        return [
            'success'  => true,
            'message'  => '✅ Check-in manual exitoso: ' . $attendee['full_name'],
            'attendee' => array_merge($attendee, ['status' => 'checked_in']),
        ];
    }

    private function fail(string $message, ?array $attendee = null): array
    {
        return ['success' => false, 'message' => '❌ ' . $message, 'attendee' => $attendee];
    }
}
