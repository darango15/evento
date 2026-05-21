<?php
/**
 * CheckinStrategyInterface — Contrato para todas las estrategias de check-in.
 *
 * Implementa el patrón Strategy: cada método de check-in (QR, manual, kiosk)
 * es una implementación intercambiable de esta interfaz.
 *
 * @package App\Services\CheckinStrategy
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Services\CheckinStrategy;

interface CheckinStrategyInterface
{
    /**
     * Procesa el check-in dado un identificador.
     *
     * @param  string   $identifier  Código QR, email, nombre, etc.
     * @param  int|null $sessionId   ID de sesión específica (null = evento general)
     * @param  array    $context     Datos adicionales: ['user_id', 'ip', 'device']
     * @return array    Resultado: ['success' => bool, 'message' => string, 'attendee' => array|null]
     */
    public function process(string $identifier, ?int $sessionId, array $context = []): array;

    /**
     * Nombre legible de la estrategia.
     *
     * @return string
     */
    public function getName(): string;
}
