<?php
/**
 * DatabaseNotifier — Observador que persiste notificaciones en base de datos.
 *
 * Guarda todas las notificaciones en la tabla 'notifications' para:
 * - Mostrar un feed de actividad en el dashboard en tiempo real
 * - Historial completo de todas las notificaciones enviadas
 * - Soporte para notificaciones push futuras
 *
 * @package App\Observers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Observers;

use App\Models\Notification;

class DatabaseNotifier implements ObserverInterface
{
    /** {@inheritdoc} */
    public function getChannel(): string
    {
        return 'database';
    }

    /** {@inheritdoc} */
    public function getSubscribedEvents(): array
    {
        return [
            'attendee.registered',
            'attendee.checkedin',
            'agenda.saved',
            'event.cancelled',
            'event.updated',
        ];
    }

    /** {@inheritdoc} */
    public function update(string $event, array $data): void
    {
        match ($event) {
            'attendee.registered' => $this->persistRegistration($data),
            'attendee.checkedin'  => $this->persistCheckin($data),
            'agenda.saved'        => $this->persistAgenda($data),
            'event.cancelled'     => $this->persistCancellation($data),
            'event.updated'       => $this->persistUpdate($data),
            default               => null,
        };
    }

    private function persistRegistration(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $event    = $data['event']    ?? null;
        if (!$attendee || !$event) return;

        Notification::create([
            'tenant_id'   => $attendee['tenant_id'] ?? null,
            'event_id'    => $event['id'],
            'attendee_id' => $attendee['id'],
            'type'        => 'registration_confirmation',
            'channel'     => 'database',
            'recipient'   => $attendee['email'],
            'subject'     => 'Nuevo registro',
            'body'        => json_encode([
                'message'  => "{$attendee['full_name']} se ha registrado al evento {$event['name']}.",
                'icon'     => 'user-plus',
                'color'    => 'green',
                'read'     => false,
            ]),
            'status'      => 'sent',
            'sent_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function persistCheckin(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $event    = $data['event']    ?? null;
        $session  = $data['session']  ?? null;
        if (!$attendee || !$event) return;

        $sessionInfo = $session ? " en sesión: {$session['title']}" : '';

        Notification::create([
            'tenant_id'   => $attendee['tenant_id'] ?? null,
            'event_id'    => $event['id'],
            'attendee_id' => $attendee['id'],
            'type'        => 'checkin_confirmed',
            'channel'     => 'database',
            'recipient'   => $attendee['email'],
            'subject'     => 'Check-in realizado',
            'body'        => json_encode([
                'message' => "{$attendee['full_name']} hizo check-in{$sessionInfo}.",
                'icon'    => 'check-circle',
                'color'   => 'blue',
                'read'    => false,
            ]),
            'status'      => 'sent',
            'sent_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function persistAgenda(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $event    = $data['event']    ?? null;
        if (!$attendee || !$event) return;

        Notification::create([
            'tenant_id'   => $attendee['tenant_id'] ?? null,
            'event_id'    => $event['id'],
            'attendee_id' => $attendee['id'],
            'type'        => 'agenda_saved',
            'channel'     => 'database',
            'recipient'   => $attendee['email'],
            'subject'     => 'Agenda guardada',
            'body'        => json_encode([
                'message' => "{$attendee['full_name']} guardó su agenda personal.",
                'icon'    => 'calendar',
                'color'   => 'purple',
                'read'    => false,
            ]),
            'status'      => 'sent',
            'sent_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function persistCancellation(array $data): void
    {
        $event = $data['event'] ?? null;
        if (!$event) return;

        Notification::create([
            'tenant_id' => $event['tenant_id'] ?? null,
            'event_id'  => $event['id'],
            'type'      => 'event_cancelled',
            'channel'   => 'database',
            'recipient' => 'admin',
            'subject'   => 'Evento cancelado',
            'body'      => json_encode([
                'message' => "El evento {$event['name']} ha sido cancelado.",
                'icon'    => 'x-circle',
                'color'   => 'red',
                'read'    => false,
            ]),
            'status'    => 'sent',
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    private function persistUpdate(array $data): void
    {
        $event = $data['event'] ?? null;
        if (!$event) return;

        Notification::create([
            'tenant_id' => $event['tenant_id'] ?? null,
            'event_id'  => $event['id'],
            'type'      => 'event_updated',
            'channel'   => 'database',
            'recipient' => 'admin',
            'subject'   => 'Evento actualizado',
            'body'      => json_encode([
                'message' => "El evento {$event['name']} ha sido actualizado.",
                'icon'    => 'refresh',
                'color'   => 'yellow',
                'read'    => false,
            ]),
            'status'    => 'sent',
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
