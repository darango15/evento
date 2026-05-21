<?php
/**
 * QRCheckinStrategy — Check-in mediante escaneo de código QR.
 *
 * Recibe el código único impreso en el QR del asistente,
 * lo busca en la base de datos y registra el check-in.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 *
 * @example
 * ```php
 * $strategy = new QRCheckinStrategy();
 * $result   = $strategy->process('A1B2C3D4E5F6...', null, ['user_id' => 2]);
 * if ($result['success']) {
 *     echo 'Check-in OK: ' . $result['attendee']['full_name'];
 * }
 * ```
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

use App\Models\Attendee;
use App\Models\Checkin;
use App\Core\Database;

class QRCheckinStrategy implements CheckinStrategyInterface
{
    public function getName(): string
    {
        return 'qr_code';
    }

    /**
     * Procesa el check-in por código QR.
     *
     * @param  string   $identifier  El check_in_code del asistente
     * @param  int|null $sessionId
     * @param  array    $context
     * @return array
     */
    public function process(string $identifier, ?int $sessionId, array $context = []): array
    {
        $code = strtoupper(trim($identifier));

        if (empty($code)) {
            return $this->fail('El código QR está vacío o es inválido.');
        }

        // Buscar asistente por código
        $attendee = Attendee::findByCode($code);

        if (!$attendee) {
            return $this->fail('Código QR no encontrado. El asistente no está registrado.');
        }

        // Verificar que no esté cancelado
        if ($attendee['status'] === 'cancelled') {
            return $this->fail('Este registro fue cancelado y no puede hacer check-in.', $attendee);
        }

        // Verificar si ya hizo check-in (evitar duplicados)
        if ($attendee['status'] === 'checked_in') {
            return [
                'success'    => false,
                'already_in' => true,
                'message'    => '⚠️ Este asistente ya realizó check-in' .
                    (!empty($attendee['checked_in_at'])
                        ? ' el ' . formatDate($attendee['checked_in_at'], true)
                        : '.'),
                'attendee'   => $attendee,
            ];
        }

        // Hacer check-in en la tabla attendees
        Attendee::doCheckin((int)$attendee['id']);

        // Registrar en la tabla checkins (auditoría)
        $this->logCheckin((int)$attendee['id'], $sessionId, $this->getName(), $context);

        // Si hay sesión, actualizar attendee_sessions
        if ($sessionId) {
            $this->updateSessionAttendance((int)$attendee['id'], $sessionId);
        }

        appLog('info', 'Check-in QR exitoso', [
            'attendee_id' => $attendee['id'],
            'code'        => $code,
            'session_id'  => $sessionId,
        ]);

        return [
            'success'  => true,
            'message'  => '✅ Check-in exitoso. ¡Bienvenido, ' . $attendee['full_name'] . '!',
            'attendee' => array_merge($attendee, ['status' => 'checked_in']),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function fail(string $message, ?array $attendee = null): array
    {
        return ['success' => false, 'message' => '❌ ' . $message, 'attendee' => $attendee];
    }

    private function logCheckin(int $attendeeId, ?int $sessionId, string $method, array $context): void
    {
        Checkin::create([
            'attendee_id'        => $attendeeId,
            'session_id'         => $sessionId,
            'checked_by_user_id' => $context['user_id'] ?? null,
            'checkin_method'     => $method,
            'checked_in_at'      => date('Y-m-d H:i:s'),
            'ip_address'         => $context['ip'] ?? null,
            'device_info'        => $context['device'] ?? null,
        ]);
    }

    private function updateSessionAttendance(int $attendeeId, int $sessionId): void
    {
        $db  = Database::getInstance();
        $sql = "UPDATE attendee_sessions
                SET status = 'attended', checkin_at = NOW()
                WHERE attendee_id = :aid AND session_id = :sid";
        $db->query($sql, [':aid' => $attendeeId, ':sid' => $sessionId]);
    }
}
