<?php
/**
 * AgendaController — CRUD de sesiones de la agenda del evento.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\EventSession;

class AgendaController extends Controller
{
    /**
     * GET /admin/events/{eventId}/agenda
     */
    public function index(string $eventId): void
    {
        $event    = $this->findEventOrAbort((int)$eventId);
        $sessions = EventSession::byEvent((int)$eventId);

        $this->render('agenda/index', [
            'title'     => 'Agenda — ' . $event['name'],
            'event'     => $event,
            'sessions'  => $sessions,
            'pageTitle' => 'Agenda del Evento',
        ]);
    }

    /**
     * GET /admin/events/{eventId}/agenda/{id}
     */
    public function show(string $eventId, string $id): void
    {
        $event     = $this->findEventOrAbort((int)$eventId);
        $session   = $this->findSessionOrAbort((int)$id, (int)$eventId);
        $attendees = EventSession::getAttendees((int)$id);

        $this->render('agenda/show', [
            'title'     => 'Sesión: ' . $session['title'],
            'event'     => $event,
            'session'   => $session,
            'attendees' => $attendees,
            'pageTitle' => 'Detalle de Sesión',
        ]);
    }

    /**
     * GET /admin/events/{eventId}/agenda/create
     */
    public function create(string $eventId): void
    {
        $event = $this->findEventOrAbort((int)$eventId);

        $this->render('agenda/session_form', [
            'title'     => 'Nueva Sesión',
            'event'     => $event,
            'session'   => null,
            'pageTitle' => 'Crear Sesión',
        ]);
    }

    /**
     * POST /admin/events/{eventId}/agenda
     */
    public function store(string $eventId): void
    {
        $this->validateCsrf();
        $event  = $this->findEventOrAbort((int)$eventId);
        $data   = $this->input([
            'title', 'description', 'type', 'speaker_name', 'speaker_bio',
            'start_time', 'end_time', 'room', 'max_attendees', 'is_virtual',
            'virtual_link', 'status', 'sort_order',
        ]);

        $errors = $this->validateSession($data);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;
            $this->redirect("/admin/events/{$eventId}/agenda/create");
            return;
        }

        $data['event_id']      = (int)$eventId;
        $data['is_virtual']    = (int)($data['is_virtual'] === '1');
        $data['max_attendees'] = $data['max_attendees'] !== '' ? (int)$data['max_attendees'] : null;
        $data['sort_order']    = (int)$data['sort_order'];

        EventSession::create($data);

        $this->flash('success', 'Sesión "' . e($data['title']) . '" creada correctamente.');
        $this->redirect("/admin/events/{$eventId}/agenda");
    }

    /**
     * GET /admin/events/{eventId}/agenda/{id}/edit
     */
    public function edit(string $eventId, string $id): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $session = $this->findSessionOrAbort((int)$id, (int)$eventId);

        $this->render('agenda/session_form', [
            'title'     => 'Editar: ' . $session['title'],
            'event'     => $event,
            'session'   => $session,
            'pageTitle' => 'Editar Sesión',
        ]);
    }

    /**
     * POST /admin/events/{eventId}/agenda/{id}  (PUT override)
     */
    public function update(string $eventId, string $id): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);
        $this->findSessionOrAbort((int)$id, (int)$eventId);

        $data = $this->input([
            'title', 'description', 'type', 'speaker_name', 'speaker_bio',
            'start_time', 'end_time', 'room', 'max_attendees', 'is_virtual',
            'virtual_link', 'status', 'sort_order',
        ]);

        $errors = $this->validateSession($data);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;
            $this->redirect("/admin/events/{$eventId}/agenda/{$id}/edit");
            return;
        }

        $data['is_virtual']    = (int)($data['is_virtual'] === '1');
        $data['max_attendees'] = $data['max_attendees'] !== '' ? (int)$data['max_attendees'] : null;
        $data['sort_order']    = (int)$data['sort_order'];

        EventSession::update((int)$id, $data);

        $this->flash('success', 'Sesión actualizada.');
        $this->redirect("/admin/events/{$eventId}/agenda");
    }

    /**
     * DELETE /admin/events/{eventId}/agenda/{id}
     */
    public function destroy(string $eventId, string $id): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);
        $this->findSessionOrAbort((int)$id, (int)$eventId);

        EventSession::delete((int)$id);

        $this->flash('success', 'Sesión eliminada.');
        $this->redirect("/admin/events/{$eventId}/agenda");
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function findEventOrAbort(int $id): array
    {
        $event = Event::find($id);
        if (!$event || (int)$event['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }
        return $event;
    }

    private function findSessionOrAbort(int $id, int $eventId): array
    {
        $session = EventSession::find($id);
        if (!$session || (int)$session['event_id'] !== $eventId) {
            $this->abort(404);
        }
        return $session;
    }

    private function validateSession(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'El título de la sesión es requerido.';
        }

        if (empty($data['start_time'])) {
            $errors['start_time'] = 'La hora de inicio es requerida.';
        }

        if (empty($data['end_time'])) {
            $errors['end_time'] = 'La hora de fin es requerida.';
        }

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['end_time'] <= $data['start_time']) {
                $errors['end_time'] = 'La hora de fin debe ser posterior a la hora de inicio.';
            }
        }

        return $errors;
    }
}
