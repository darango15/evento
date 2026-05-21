<?php
/**
 * ObserverInterface — Contrato para los observadores de eventos del sistema.
 *
 * Define el contrato del patrón Observer. Los observadores reaccionan
 * a eventos del dominio (registro, check-in, cancelación, etc.)
 * enviando notificaciones por diferentes canales.
 *
 * @package App\Observers
 * @version 1.0.0
 *
 * @example
 * ```php
 * class EmailNotifier implements ObserverInterface
 * {
 *     public function update(string $event, array $data): void
 *     {
 *         match($event) {
 *             'attendee.registered' => $this->sendConfirmation($data),
 *             'event.cancelled'     => $this->sendCancellation($data),
 *             default               => null,
 *         };
 *     }
 *
 *     public function getChannel(): string { return 'email'; }
 *     public function getSubscribedEvents(): array { return ['attendee.registered', 'event.cancelled']; }
 * }
 * ```
 */

declare(strict_types=1);

namespace App\Observers;

interface ObserverInterface
{
    /**
     * Notifica al observador de un evento del dominio.
     *
     * @param string $event Nombre del evento, ej: 'attendee.registered'
     * @param array  $data  Datos del evento (asistente, sesión, etc.)
     */
    public function update(string $event, array $data): void;

    /**
     * Nombre del canal de notificación.
     *
     * @return string  'email' | 'sms' | 'database' | 'push'
     */
    public function getChannel(): string;

    /**
     * Lista de eventos del dominio a los que este observador está suscrito.
     *
     * @return string[]
     */
    public function getSubscribedEvents(): array;
}
