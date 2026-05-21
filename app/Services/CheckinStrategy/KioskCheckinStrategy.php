<?php
/**
 * KioskCheckinStrategy — Check-in desde pantalla táctil kiosk en el evento.
 *
 * Permite que el asistente se registre directamente en una pantalla
 * táctil instalada en la entrada del evento. Busca por código O por email.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 *
 * @example
 * ```php
 * $strategy = new KioskCheckinStrategy();
 *
 * // Por código
 * $result = $strategy->process('A1B2C3', null, ['kiosk_id' => 'kiosk-01']);
 *
 * // Por email (pasar email como identifier)
 * $result = $strategy->process('juan@empresa.com', null, ['kiosk_id' => 'kiosk-01']);
 * ```
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

use App\Models\Attendee;
use App\Models\Checkin;
use App\Core\Database;

class KioskCheckinStrategy implements CheckinStrategyInterface
{
    public function getName(): string
    {
        return 'kiosk';
    }

    /**
     * Procesa el check-in desde kiosk.
     * Acepta tanto código QR como email del asistente.
     *
     * @param  string   $identifier  Código QR o email del asistente
     * @param  int|null $sessionId
     * @param  array    $context     Debe incluir 'event_id' para búsqueda por email
     * @return array
     */
    public function process(string $identifier, ?int $sessionId, array $context = []): array
    {
        $identifier = trim($identifier);

        if (empty($identifier)) {
            return $this->fail('Ingresa tu código o email para hacer check-in.');
        }

        // Determinar tipo de búsqueda
        $attendee = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $this->findByEmail($identifier, (int)($context['event_id'] ?? 0))
            : Attendee::findByCode(strtoupper($identifier));

        if (!$attendee) {
            return $this->fail('No encontramos tu registro. Verifica tu código o email.');
        }

        if ($attendee['status'] === 'cancelled') {
            return $this->fail('Tu registro fue cancelado. Consulta con el staff del evento.', $attendee);
        }

        if ($attendee['status'] === 'checked_in') {
            return [
                'success'    => false,
                'already_in' => true,
                'message'    => '¡Ya realizaste check-in! Bienvenido de nuevo, ' . $attendee['full_name'] . '.',
                'attendee'   => $attendee,
            ];
        }

        Attendee::doCheckin((int)$attendee['id']);

        Checkin::create([
            'attendee_id'    => (int)$attendee['id'],
            'session_id'     => $sessionId,
            'checkin_method' => $this->getName(),
            'checked_in_at'  => date('Y-m-d H:i:s'),
            'ip_address'     => $context['ip'] ?? null,
            'device_info'    => json_encode([
                'kiosk_id' => $context['kiosk_id'] ?? 'kiosk-unknown',
                'type'     => 'kiosk_touchscreen',
            ]),
        ]);

        if ($sessionId) {
            $db  = Database::getInstance();
            $sql = "UPDATE attendee_sessions
                    SET status = 'attended', checkin_at = NOW()
                    WHERE attendee_id = :aid AND session_id = :sid";
            $db->query($sql, [':aid' => (int)$attendee['id'], ':sid' => $sessionId]);
        }

        appLog('info', 'Check-in Kiosk exitoso', ['attendee_id' => $attendee['id']]);

        return [
            'success'  => true,
            'message'  => '¡Bienvenido, ' . $attendee['full_name'] . '! Tu acceso ha sido registrado.',
            'attendee' => array_merge($attendee, ['status' => 'checked_in']),
        ];
    }

    /**
     * Busca asistente por email dentro de un evento específico.
     *
     * @param  string $email
     * @param  int    $eventId
     * @return array|null
     */
    private function findByEmail(string $email, int $eventId): ?array
    {
        if (!$eventId) return null;

        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT a.*, e.name AS event_name, e.start_date, e.venue_name
             FROM attendees a
             INNER JOIN events e ON a.event_id = e.id
             WHERE a.email = :email AND a.event_id = :eid
               AND a.status != 'cancelled'
             LIMIT 1",
            [':email' => strtolower($email), ':eid' => $eventId]
        );

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function fail(string $message, ?array $attendee = null): array
    {
        return ['success' => false, 'message' => $message, 'attendee' => $attendee];
    }
}
