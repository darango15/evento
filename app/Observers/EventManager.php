<?php
/**
 * EventManager — Sujeto del patrón Observer (Event Bus del dominio).
 *
 * Gestiona la lista de observadores y despacha eventos del dominio
 * a los observadores suscritos.
 *
 * Eventos del dominio disponibles:
 * - attendee.registered    → Participante se registra
 * - attendee.checkedin     → Participante hace check-in
 * - agenda.saved           → Participante guarda/modifica su agenda
 * - session.reminder       → Recordatorio 24h antes de una sesión
 * - event.cancelled        → Evento cancelado
 * - event.updated          → Evento actualizado
 *
 * @package App\Observers
 * @version 1.0.0
 *
 * @example
 * ```php
 * // Registrar observadores
 * $manager = EventManager::getInstance();
 * $manager->subscribe(new EmailNotifier());
 * $manager->subscribe(new DatabaseNotifier());
 *
 * // Disparar un evento del dominio
 * $manager->dispatch('attendee.registered', [
 *     'attendee' => $attendee,
 *     'event'    => $event,
 * ]);
 * ```
 */

declare(strict_types=1);

namespace App\Observers;

use App\Core\Traits\SingletonTrait;

class EventManager
{
    use SingletonTrait;

    /** @var array<string, ObserverInterface[]> Observadores por evento */
    private array $listeners = [];

    /** @var bool Si false, los errores de notificación no propagan excepciones */
    private bool $throwOnError = false;

    private function __construct() {}

    /**
     * Suscribe un observador. Se registra automáticamente para sus eventos.
     *
     * @param  ObserverInterface $observer
     * @return self
     */
    public function subscribe(ObserverInterface $observer): self
    {
        foreach ($observer->getSubscribedEvents() as $event) {
            $this->listeners[$event][] = $observer;
        }
        return $this;
    }

    /**
     * Elimina un observador de todos sus eventos.
     *
     * @param  ObserverInterface $observer
     */
    public function unsubscribe(ObserverInterface $observer): void
    {
        foreach ($observer->getSubscribedEvents() as $event) {
            $this->listeners[$event] = array_filter(
                $this->listeners[$event] ?? [],
                fn($o) => $o !== $observer
            );
        }
    }

    /**
     * Despacha un evento del dominio a todos los observadores suscritos.
     *
     * @param  string $event  Nombre del evento, ej: 'attendee.registered'
     * @param  array  $data   Datos del contexto del evento
     * @return int    Número de observadores notificados con éxito
     */
    public function dispatch(string $event, array $data = []): int
    {
        $notified = 0;
        $observers = $this->listeners[$event] ?? [];

        foreach ($observers as $observer) {
            try {
                $observer->update($event, $data);
                $notified++;
            } catch (\Throwable $e) {
                appLog('error', "Observer error [{$observer->getChannel()}] on event [{$event}]: " . $e->getMessage());

                if ($this->throwOnError) {
                    throw $e;
                }
            }
        }

        if (!empty($observers)) {
            appLog('debug', "Dispatched [{$event}] to {$notified}/" . count($observers) . " observers.");
        }

        return $notified;
    }

    /**
     * Registra un observador para un evento específico (forma alternativa).
     *
     * @param  string            $event
     * @param  ObserverInterface $observer
     */
    public function on(string $event, ObserverInterface $observer): void
    {
        $this->listeners[$event][] = $observer;
    }

    /**
     * Lista todos los eventos registrados.
     *
     * @return string[]
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Cuenta observadores por evento.
     *
     * @param  string $event
     * @return int
     */
    public function countObservers(string $event): int
    {
        return count($this->listeners[$event] ?? []);
    }

    /**
     * Activa la propagación de excepciones (útil para tests).
     */
    public function throwOnError(bool $throw = true): self
    {
        $this->throwOnError = $throw;
        return $this;
    }
}
