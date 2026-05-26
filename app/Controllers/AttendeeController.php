<?php
/**
 * AttendeeController — Registro público de asistentes y gestión admin.
 *
 * Consolida el flujo de registro público (formulario → QR → confirmación)
 * con la gestión administrativa de participantes (listado, detalle, eliminación).
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\AttendeeSession;
use App\Services\QRGenerator;
use App\Services\ValidationService;
use App\Observers\EventManager;
use App\Observers\EmailNotifier;
use App\Observers\DatabaseNotifier;

class AttendeeController extends Controller
{
    private ValidationService $validator;

    public function __construct()
    {
        $this->validator = new ValidationService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Registro público
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /eventos/{slug}/registro
     * Formulario público de registro de asistentes.
     */
    public function registrationForm(string $slug): void
    {
        $tenant = currentTenant();
        if (!$tenant) $this->abort(404);

        $event = Event::findBySlug((int)$tenant['id'], $slug);
        if (!$event || $event['status'] !== 'published') $this->abort(404);

        if (!Event::hasCapacity((int)$event['id'])) {
            $this->render('attendees/full', [
                'title' => 'Registro cerrado — ' . $event['name'],
                'event' => $event,
            ], 'public');
            return;
        }

        $event = Event::decodeSettings($event);

        $this->render('attendees/register', [
            'title'       => 'Registro — ' . $event['name'],
            'event'       => $event,
            'tenant'      => $tenant,
            'form_errors' => $_SESSION['form_errors'] ?? [],
            'form_data'   => $_SESSION['form_data']   ?? [],
        ], 'public');

        unset($_SESSION['form_errors'], $_SESSION['form_data']);
    }

    /**
     * POST /eventos/{slug}/registro
     * Procesa el formulario de registro, genera QR y dispara notificaciones.
     */
    public function register(string $slug): void
    {
        $this->validateCsrf();

        $tenant = currentTenant();
        if (!$tenant) $this->abort(404);

        $event = Event::findBySlug((int)$tenant['id'], $slug);
        if (!$event || $event['status'] !== 'published') $this->abort(404);

        $data   = $this->input(['full_name', 'email', 'phone', 'company', 'position', 'dietary_restrictions', 'special_needs']);
        $errors = $this->validator->validateAttendeeRegistration($data, (int)$event['id']);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;
            $this->redirect("/eventos/{$slug}/registro");
            return;
        }

        // Generar código único
        $checkInCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        // Crear asistente
        $attendeeId = Attendee::create([
            'tenant_id'            => (int)$tenant['id'],
            'event_id'             => (int)$event['id'],
            'email'                => strtolower(trim($data['email'])),
            'full_name'            => trim($data['full_name']),
            'phone'                => trim($data['phone']),
            'company'              => trim($data['company']),
            'position'             => trim($data['position']),
            'dietary_restrictions' => trim($data['dietary_restrictions']),
            'special_needs'        => trim($data['special_needs'] ?? ''),
            'check_in_code'        => $checkInCode,
            'status'               => 'registered',
            'registration_date'    => date('Y-m-d H:i:s'),
        ]);

        // Generar QR
        $qrPath = null;
        try {
            $qrGen  = new QRGenerator();
            $qrPath = $qrGen->generateForAttendee($checkInCode, (int)$tenant['id'], (int)$event['id']);
            Attendee::update($attendeeId, ['qr_code_path' => $qrPath]);
        } catch (\Throwable $e) {
            appLog('error', 'Error generando QR: ' . $e->getMessage());
        }

        // Disparar Observer: email de confirmación + notificación en dashboard
        $attendee = Attendee::find($attendeeId);
        if ($attendee) {
            $attendee['qr_code_path'] = $qrPath;
            $this->dispatchRegistrationEvent($attendee, $event);
        }

        appLog('info', 'Nuevo registro', ['attendee_id' => $attendeeId, 'event_id' => $event['id']]);

        $this->redirect("/eventos/{$slug}/confirmacion/{$checkInCode}");
    }

    /**
     * GET /eventos/{slug}/confirmacion/{code}
     * Página de confirmación tras el registro exitoso.
     */
    public function confirmation(string $slug, string $code): void
    {
        $attendee = Attendee::findByCode(strtoupper($code));
        if (!$attendee) $this->abort(404);

        $this->render('attendees/confirmation', [
            'title'    => '¡Registro Confirmado!',
            'attendee' => $attendee,
        ], 'public');
    }

    /**
     * GET /registro/qr/{code}
     * Sirve la imagen PNG del QR de un asistente (teal #02b6a5).
     * Usa el archivo guardado en disco si existe; si no, lo genera y lo guarda.
     */
    public function qrImage(string $code): void
    {
        $code     = strtoupper($code);
        $attendee = Attendee::findByCode($code);
        if (!$attendee) {
            http_response_code(404);
            exit;
        }

        // Servir archivo guardado si existe
        if (!empty($attendee['qr_code_path'])) {
            $abs = PUBLIC_PATH . $attendee['qr_code_path'];
            if (file_exists($abs)) {
                header('Content-Type: image/png');
                header('Cache-Control: public, max-age=604800');
                readfile($abs);
                exit;
            }
        }

        // Generar desde el API externo (teal), guardarlo y servir
        try {
            $qrGen     = new QRGenerator();
            $targetUrl = url("/registro/ticket/{$code}");
            $png       = $qrGen->fetchPng($targetUrl, '#02b6a5');

            // Guardar para futuras cargas
            try {
                $filename = "t{$attendee['tenant_id']}_e{$attendee['event_id']}_{$code}";
                $path     = $qrGen->generate($targetUrl, $filename, '#02b6a5');
                Attendee::update((int)$attendee['id'], ['qr_code_path' => $path]);
            } catch (\Throwable) {}

            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=604800');
            echo $png;
        } catch (\Throwable $e) {
            appLog('error', 'qrImage failed: ' . $e->getMessage());
            http_response_code(503);
        }
        exit;
    }

    /**
     * GET /registro/ticket/{code}
     * Ticket imprimible con código QR.
     */
    public function ticket(string $code): void
    {
        $attendee = Attendee::findByCode(strtoupper($code));
        if (!$attendee) $this->abort(404);

        $this->renderPartial('attendees/ticket', [
            'title'    => 'Ticket — ' . $attendee['event_name'],
            'attendee' => $attendee,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Panel Admin
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/events/{eventId}/attendees
     * Lista de participantes con búsqueda, filtros y resumen.
     */
    public function index(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $status  = $_GET['status'] ?? null;
        $search  = $_GET['q']      ?? '';
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $result  = Attendee::paginate((int)$eventId, $status ?: null, $search, $page);
        $summary = Attendee::attendanceSummary((int)$eventId);

        $this->render('attendees/index', [
            'title'     => 'Participantes — ' . $event['name'],
            'pageTitle' => 'Participantes',
            'event'     => $event,
            'attendees' => $result['data'],
            'total'     => $result['total'],
            'pages'     => $result['pages'],
            'page'      => $page,
            'summary'   => $summary,
            'search'    => $search,
            'filter'    => $status,
        ]);
    }

    /**
     * GET /admin/attendees/{id}
     * Detalle completo de un asistente con su agenda personal.
     */
    public function show(string $id): void
    {
        $attendee = Attendee::find((int)$id);

        if (!$attendee) $this->abort(404);

        // Verificar que pertenece al tenant actual
        if ((int)$attendee['tenant_id'] !== (int)tenantId()) {
            $this->abort(403);
        }

        $event  = Event::find((int)$attendee['event_id']);
        $agenda = AttendeeSession::getAgenda((int)$id, (int)$attendee['event_id']);

        $this->render('attendees/show', [
            'title'     => $attendee['full_name'],
            'pageTitle' => 'Detalle de Participante',
            'attendee'  => $attendee,
            'event'     => $event,
            'agenda'    => $agenda,
        ]);
    }

    /**
     * DELETE /admin/attendees/{id}
     * Cancela el registro de un asistente (soft delete → status cancelled).
     */
    public function destroy(string $id): void
    {
        $this->validateCsrf();

        $attendee = Attendee::find((int)$id);
        if (!$attendee || (int)$attendee['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }

        // Soft delete: cambiar estado a cancelled
        Attendee::update((int)$id, ['status' => 'cancelled']);

        appLog('warning', 'Asistente cancelado', ['attendee_id' => $id, 'by' => authUser()['id'] ?? null]);

        $this->flash('success', 'Registro de asistente cancelado correctamente.');
        $this->redirect("/admin/events/{$attendee['event_id']}/attendees");
    }

    /**
     * POST /admin/attendees/{id}/restore
     * Restaura un registro cancelado a 'registered'.
     */
    public function restore(string $id): void
    {
        $this->validateCsrf();

        $attendee = Attendee::find((int)$id);
        if (!$attendee || (int)$attendee['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }

        Attendee::update((int)$id, ['status' => 'registered']);

        $this->flash('success', 'Registro restaurado correctamente.');
        $this->redirect("/admin/events/{$attendee['event_id']}/attendees");
    }

    /**
     * GET /admin/events/{eventId}/tickets/print
     * Vista de impresión masiva de tickets (2 columnas, tamaño carta).
     */
    public function printTickets(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $perPage = min(5000, max(1, (int)($_GET['limit'] ?? 5000)));
        $result  = Attendee::paginate((int)$eventId, null, '', 1, $perPage);

        $this->renderPartial('attendees/tickets_print', [
            'title'     => 'Tickets — ' . $event['name'],
            'event'     => $event,
            'attendees' => $result['data'],
            'total'     => $result['total'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Despacha el evento de dominio 'attendee.registered' al EventManager.
     * Activa EmailNotifier y DatabaseNotifier automáticamente.
     */
    private function dispatchRegistrationEvent(array $attendee, array $event): void
    {
        try {
            $manager = EventManager::getInstance();

            if ($manager->countObservers('attendee.registered') === 0) {
                $manager->subscribe(new EmailNotifier());
                $manager->subscribe(new DatabaseNotifier());
            }

            $manager->dispatch('attendee.registered', [
                'attendee' => $attendee,
                'event'    => $event,
            ]);
        } catch (\Throwable $e) {
            appLog('error', 'EventManager dispatch error: ' . $e->getMessage());
        }
    }

    private function findEventOrAbort(int $id): array
    {
        $event = Event::find($id);
        if (!$event || (int)$event['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }
        return $event;
    }
}
