<?php
/**
 * EmailNotifier — Observador que envía notificaciones por email.
 *
 * Reacciona a eventos del dominio (registro, agenda, check-in, etc.)
 * y envía emails personalizados a los asistentes usando EmailService.
 *
 * @package App\Observers
 * @version 1.0.0
 *
 * @example
 * ```php
 * $manager = EventManager::getInstance();
 * $manager->subscribe(new EmailNotifier());
 *
 * // Cuando un participante se registra, dispara email automáticamente:
 * $manager->dispatch('attendee.registered', [
 *     'attendee' => $attendeeData,
 *     'event'    => $eventData,
 * ]);
 * ```
 */

declare(strict_types=1);

namespace App\Observers;

use App\Services\EmailService;
use App\Models\Notification;

class EmailNotifier implements ObserverInterface
{
    private EmailService $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    /** {@inheritdoc} */
    public function getChannel(): string
    {
        return 'email';
    }

    /** {@inheritdoc} */
    public function getSubscribedEvents(): array
    {
        return [
            'attendee.registered',
            'attendee.checkedin',
            'agenda.saved',
            'session.reminder',
            'event.cancelled',
            'event.updated',
        ];
    }

    /** {@inheritdoc} */
    public function update(string $event, array $data): void
    {
        match ($event) {
            'attendee.registered' => $this->handleRegistration($data),
            'attendee.checkedin'  => $this->handleCheckin($data),
            'agenda.saved'        => $this->handleAgendaSaved($data),
            'session.reminder'    => $this->handleReminder($data),
            'event.cancelled'     => $this->handleEventCancelled($data),
            'event.updated'       => $this->handleEventUpdated($data),
            default               => null,
        };
    }

    /**
     * Envía email de confirmación de registro con código QR adjunto.
     */
    private function handleRegistration(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $event    = $data['event']    ?? null;

        if (!$attendee || !$event) return;

        // Evitar envío duplicado
        if (Notification::alreadySent((int)$attendee['id'], 'registration_confirmation')) return;

        $subject = "Confirmación de registro — {$event['name']}";
        $body    = $this->renderTemplate('registration_confirmation', [
            'attendee' => $attendee,
            'event'    => $event,
        ]);

        $sent = $this->emailService->send(
            to:      $attendee['email'],
            name:    $attendee['full_name'],
            subject: $subject,
            body:    $body,
            attachQr: $attendee['qr_code_path'] ?? null,
        );

        $this->logNotification($attendee, $event, 'registration_confirmation', $subject, $sent);
    }

    /**
     * Notifica al staff cuando un asistente hace check-in (opcional).
     */
    private function handleCheckin(array $data): void
    {
        // Notificación de check-in: solo persistir en DB, no enviar email
        // (se delega al DatabaseNotifier para el dashboard)
    }

    /**
     * Envía resumen de agenda personal guardada.
     */
    private function handleAgendaSaved(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $sessions = $data['sessions'] ?? [];
        $event    = $data['event']    ?? null;

        if (!$attendee || !$event || empty($sessions)) return;

        $subject = "Tu agenda para {$event['name']}";
        $body    = $this->renderTemplate('agenda_saved', [
            'attendee' => $attendee,
            'event'    => $event,
            'sessions' => $sessions,
        ]);

        $sent = $this->emailService->send(
            to:      $attendee['email'],
            name:    $attendee['full_name'],
            subject: $subject,
            body:    $body,
        );

        $this->logNotification($attendee, $event, 'agenda_saved', $subject, $sent);
    }

    /**
     * Envía recordatorio 24h antes de la sesión.
     */
    private function handleReminder(array $data): void
    {
        $attendee = $data['attendee'] ?? null;
        $session  = $data['session']  ?? null;
        $event    = $data['event']    ?? null;

        if (!$attendee || !$session || !$event) return;

        $subject = "Recordatorio: {$session['title']} mañana";
        $body    = $this->renderTemplate('session_reminder', [
            'attendee' => $attendee,
            'session'  => $session,
            'event'    => $event,
        ]);

        $sent = $this->emailService->send(
            to:      $attendee['email'],
            name:    $attendee['full_name'],
            subject: $subject,
            body:    $body,
        );

        $this->logNotification($attendee, $event, 'agenda_reminder', $subject, $sent);
    }

    /**
     * Notifica cancelación del evento a todos los asistentes.
     */
    private function handleEventCancelled(array $data): void
    {
        $attendees = $data['attendees'] ?? [];
        $event     = $data['event']     ?? null;

        if (!$event || empty($attendees)) return;

        $subject = "Aviso importante: {$event['name']} ha sido cancelado";

        foreach ($attendees as $attendee) {
            $body = $this->renderTemplate('event_cancelled', [
                'attendee' => $attendee,
                'event'    => $event,
            ]);

            $sent = $this->emailService->send(
                to:      $attendee['email'],
                name:    $attendee['full_name'],
                subject: $subject,
                body:    $body,
            );

            $this->logNotification($attendee, $event, 'event_cancelled', $subject, $sent);
        }
    }

    /**
     * Notifica actualización del evento.
     */
    private function handleEventUpdated(array $data): void
    {
        $attendees = $data['attendees'] ?? [];
        $event     = $data['event']     ?? null;

        if (!$event || empty($attendees)) return;

        $subject = "Actualización: {$event['name']}";

        foreach ($attendees as $attendee) {
            $body = $this->renderTemplate('event_updated', [
                'attendee' => $attendee,
                'event'    => $event,
                'changes'  => $data['changes'] ?? [],
            ]);

            $sent = $this->emailService->send(
                to:      $attendee['email'],
                name:    $attendee['full_name'],
                subject: $subject,
                body:    $body,
            );

            $this->logNotification($attendee, $event, 'event_updated', $subject, $sent);
        }
    }

    /**
     * Renderiza una plantilla de email básica en HTML.
     *
     * @param  string $template Nombre del template
     * @param  array  $vars     Variables disponibles
     * @return string HTML del cuerpo del email
     */
    private function renderTemplate(string $template, array $vars): string
    {
        extract($vars, EXTR_SKIP);

        $templatePath = defined('ROOT_PATH')
            ? ROOT_PATH . "/views/emails/{$template}.php"
            : '';

        if ($templatePath && file_exists($templatePath)) {
            ob_start();
            require $templatePath;
            return ob_get_clean();
        }

        // Fallback: template genérico inline
        return $this->genericTemplate($template, $vars);
    }

    /**
     * Template genérico de fallback cuando no hay archivo de vista.
     */
    private function genericTemplate(string $type, array $vars): string
    {
        $attendeeName = htmlspecialchars($vars['attendee']['full_name'] ?? 'Participante');
        $eventName    = htmlspecialchars($vars['event']['name'] ?? 'Evento');
        $appName      = htmlspecialchars(env('APP_NAME', 'EventoSaaS'));

        $messages = [
            'registration_confirmation' => "Hola {$attendeeName}, tu registro en <strong>{$eventName}</strong> ha sido confirmado. Encontrarás tu código QR adjunto.",
            'agenda_saved'   => "Hola {$attendeeName}, tu agenda personal para <strong>{$eventName}</strong> ha sido guardada.",
            'session_reminder' => "Hola {$attendeeName}, recuerda que mañana tienes una sesión en <strong>{$eventName}</strong>.",
            'event_cancelled' => "Hola {$attendeeName}, lamentamos informarte que <strong>{$eventName}</strong> ha sido cancelado.",
            'event_updated'   => "Hola {$attendeeName}, hay actualizaciones importantes sobre <strong>{$eventName}</strong>.",
        ];

        $msg = $messages[$type] ?? "Tienes una notificación sobre <strong>{$eventName}</strong>.";

        return <<<HTML
        <html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background:#1e40af; color:white; padding:20px; text-align:center;">
                <h1>{$appName}</h1>
            </div>
            <div style="padding:30px;">
                <p>{$msg}</p>
            </div>
            <div style="background:#f3f4f6; padding:15px; text-align:center; font-size:12px; color:#6b7280;">
                Este email fue enviado por {$appName}.
            </div>
        </body></html>
        HTML;
    }

    /**
     * Persiste el log de la notificación enviada.
     */
    private function logNotification(
        array $attendee,
        array $event,
        string $type,
        string $subject,
        bool $sent
    ): void {
        Notification::create([
            'tenant_id'   => $attendee['tenant_id'] ?? $event['tenant_id'] ?? null,
            'event_id'    => $event['id'] ?? null,
            'attendee_id' => $attendee['id'] ?? null,
            'type'        => $type,
            'channel'     => 'email',
            'recipient'   => $attendee['email'],
            'subject'     => $subject,
            'status'      => $sent ? 'sent' : 'failed',
            'sent_at'     => $sent ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
