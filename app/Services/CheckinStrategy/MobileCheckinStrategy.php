<?php
/**
 * MobileCheckinStrategy — Check-in desde aplicación móvil via API token.
 *
 * Valida el token de API del dispositivo móvil antes de procesar
 * el check-in. Registra el dispositivo y versión de la app.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 *
 * @example
 * ```php
 * $strategy = new MobileCheckinStrategy();
 * $result   = $strategy->process('A1B2C3D4', null, [
 *     'api_token'    => 'staff_token_aqui',
 *     'device_id'    => 'iPhone15-ABC123',
 *     'app_version'  => '2.1.0',
 *     'user_id'      => 5,
 * ]);
 * ```
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

use App\Models\Attendee;
use App\Models\Checkin;
use App\Core\Database;

class MobileCheckinStrategy implements CheckinStrategyInterface
{
    public function getName(): string
    {
        return 'mobile';
    }

    /**
     * Procesa el check-in desde una app móvil.
     *
     * @param  string   $identifier  Código de check-in del asistente
     * @param  int|null $sessionId
     * @param  array    $context     Debe incluir 'api_token' para autenticación
     * @return array
     */
    public function process(string $identifier, ?int $sessionId, array $context = []): array
    {
        // Validar token de API
        if (!$this->validateApiToken($context['api_token'] ?? '')) {
            return $this->fail('Token de API inválido o expirado.');
        }

        $code = strtoupper(trim($identifier));

        if (empty($code)) {
            return $this->fail('El código de check-in está vacío.');
        }

        $attendee = Attendee::findByCode($code);

        if (!$attendee) {
            return $this->fail('Código no encontrado.');
        }

        if ($attendee['status'] === 'cancelled') {
            return $this->fail('Registro cancelado.', $attendee);
        }

        if ($attendee['status'] === 'checked_in') {
            return [
                'success'    => false,
                'already_in' => true,
                'message'    => 'El asistente ya realizó check-in.',
                'attendee'   => $attendee,
                'checked_at' => $attendee['checked_in_at'],
            ];
        }

        Attendee::doCheckin((int)$attendee['id']);

        // Log con información del dispositivo móvil
        Checkin::create([
            'attendee_id'        => (int)$attendee['id'],
            'session_id'         => $sessionId,
            'checked_by_user_id' => $context['user_id'] ?? null,
            'checkin_method'     => $this->getName(),
            'checked_in_at'      => date('Y-m-d H:i:s'),
            'ip_address'         => $context['ip'] ?? null,
            'device_info'        => json_encode([
                'device_id'   => $context['device_id']   ?? 'unknown',
                'app_version' => $context['app_version']  ?? 'unknown',
                'platform'    => $context['platform']     ?? 'mobile',
            ]),
        ]);

        if ($sessionId) {
            $db  = Database::getInstance();
            $sql = "UPDATE attendee_sessions
                    SET status = 'attended', checkin_at = NOW()
                    WHERE attendee_id = :aid AND session_id = :sid";
            $db->query($sql, [':aid' => (int)$attendee['id'], ':sid' => $sessionId]);
        }

        appLog('info', 'Check-in Mobile exitoso', [
            'attendee_id' => $attendee['id'],
            'device'      => $context['device_id'] ?? null,
        ]);

        return [
            'success'  => true,
            'message'  => 'Check-in exitoso.',
            'attendee' => array_merge($attendee, ['status' => 'checked_in']),
        ];
    }

    /**
     * Valida el token de API del dispositivo móvil.
     * El token se verifica contra los usuarios con rol staff/admin/owner.
     *
     * @param  string $token
     * @return bool
     */
    private function validateApiToken(string $token): bool
    {
        if (empty($token)) return false;

        $db  = Database::getInstance();
        $sql = "SELECT id FROM users
                WHERE api_token = :token
                  AND api_token IS NOT NULL
                  AND role IN ('owner', 'admin', 'staff')
                LIMIT 1";

        $stmt = $db->query($sql, [':token' => $token]);
        return $stmt->fetch() !== false;
    }

    private function fail(string $message, ?array $attendee = null): array
    {
        return ['success' => false, 'message' => $message, 'attendee' => $attendee];
    }
}
