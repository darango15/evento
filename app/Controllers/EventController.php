<?php
/**
 * EventController — CRUD completo de eventos.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\Tenant;

class EventController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Panel Admin
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/events
     * Lista todos los eventos del tenant actual.
     */
    public function index(): void
    {
        $tid    = tenantId();
        $status = $_GET['status'] ?? null;
        $events = Event::byTenant($tid, $status ?: null);

        $this->render('events/index', [
            'title'      => 'Mis Eventos',
            'events'     => $events,
            'filter'     => $status,
            'pageTitle'  => 'Gestión de Eventos',
        ]);
    }

    /**
     * GET /admin/events/create
     * Formulario de creación de evento.
     */
    public function create(): void
    {
        // Verificar límite del plan
        if (!Tenant::canCreateEvent((int)tenantId())) {
            $this->flash('error', 'Has alcanzado el límite de eventos de tu plan. Actualiza para crear más eventos.');
            $this->redirect('/admin/events');
            return;
        }

        $this->render('events/create', [
            'title'     => 'Nuevo Evento',
            'pageTitle' => 'Crear Evento',
            'timezones' => $this->getTimezones(),
        ]);
    }

    /**
     * POST /admin/events
     * Almacena un nuevo evento.
     */
    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->input([
            'name', 'slug', 'description', 'start_date', 'end_date',
            'timezone', 'venue_name', 'venue_address', 'max_capacity',
            'is_virtual', 'virtual_link', 'status',
        ]);
        $tid    = (int)tenantId();
        $errors = $this->validateEvent($data);

        // Auto-generar slug si está vacío
        if (empty($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }

        // Verificar unicidad del slug
        if (Event::slugExists($tid, $data['slug'])) {
            $errors['slug'] = 'Ya existe un evento con este slug. Elige uno diferente.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;
            $this->redirect('/admin/events/create');
            return;
        }

        $data['tenant_id']  = $tid;
        $data['max_capacity'] = $data['max_capacity'] !== '' ? (int)$data['max_capacity'] : null;
        $data['is_virtual'] = (int)($data['is_virtual'] === '1');

        $eventId = Event::create($data);

        $this->flash('success', 'Evento "' . e($data['name']) . '" creado correctamente.');
        $this->redirect('/admin/events/' . $eventId);
    }

    /**
     * GET /admin/events/{id}
     * Vista detalle del evento con estadísticas.
     */
    public function show(string $id): void
    {
        $event = $this->findEventOrAbort((int)$id);
        $stats = Event::getStats((int)$id);

        $this->render('events/show', [
            'title'     => $event['name'],
            'event'     => $event,
            'stats'     => $stats,
            'pageTitle' => $event['name'],
        ]);
    }

    /**
     * GET /admin/events/{id}/edit
     * Formulario de edición.
     */
    public function edit(string $id): void
    {
        $event = $this->findEventOrAbort((int)$id);

        $this->render('events/edit', [
            'title'     => 'Editar: ' . $event['name'],
            'event'     => $event,
            'pageTitle' => 'Editar Evento',
            'timezones' => $this->getTimezones(),
        ]);
    }

    /**
     * POST /admin/events/{id}  (PUT override)
     * Actualiza el evento.
     */
    public function update(string $id): void
    {
        $this->validateCsrf();
        $event = $this->findEventOrAbort((int)$id);

        $data   = $this->input([
            'name', 'slug', 'description', 'start_date', 'end_date',
            'timezone', 'venue_name', 'venue_address', 'max_capacity',
            'is_virtual', 'virtual_link', 'status',
        ]);
        $tid    = (int)tenantId();
        $errors = $this->validateEvent($data);

        if (empty($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }

        if (Event::slugExists($tid, $data['slug'], (int)$id)) {
            $errors['slug'] = 'Ya existe un evento con este slug.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;
            $this->redirect('/admin/events/' . $id . '/edit');
            return;
        }

        $data['max_capacity'] = $data['max_capacity'] !== '' ? (int)$data['max_capacity'] : null;
        $data['is_virtual']   = (int)($data['is_virtual'] === '1');

        Event::update((int)$id, $data);

        $this->flash('success', 'Evento actualizado correctamente.');
        $this->redirect('/admin/events/' . $id);
    }

    /**
     * DELETE /admin/events/{id}
     * Elimina el evento (soft delete cambiando status a cancelled).
     */
    public function destroy(string $id): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$id);

        Event::update((int)$id, ['status' => 'cancelled']);

        $this->flash('success', 'Evento cancelado/eliminado.');
        $this->redirect('/admin/events');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Página pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /
     * Página pública: listado de eventos próximos del tenant.
     */
    public function publicIndex(): void
    {
        $tenant = currentTenant();

        if (!$tenant) {
            // Página de bienvenida cuando se accede al dominio raíz, redirigimos al login por solicitud del usuario
            $this->redirect('/login');
            return;
        }

        $events = Event::upcoming((int)$tenant['id']);
        $tenant = Tenant::decodeSettings($tenant);

        $this->render('public/event_list', [
            'title'  => $tenant['name'] . ' — Eventos',
            'events' => $events,
            'tenant' => $tenant,
        ], 'public');
    }

    /**
     * GET /eventos/{slug}
     * Página pública de detalle de evento.
     */
    public function publicShow(string $slug): void
    {
        $tenant = currentTenant();
        if (!$tenant) $this->abort(404);

        $event = Event::findBySlug((int)$tenant['id'], $slug);
        if (!$event || $event['status'] !== 'published') $this->abort(404);

        $event  = Event::decodeSettings($event);
        $stats  = Event::getStats((int)$event['id']);

        $this->render('public/event_detail', [
            'title'  => $event['name'] . ' — ' . $tenant['name'],
            'event'  => $event,
            'tenant' => $tenant,
            'stats'  => $stats,
        ], 'public');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Busca el evento verificando que pertenezca al tenant actual.
     * Llama abort(404) si no existe o no pertenece al tenant.
     */
    private function findEventOrAbort(int $id): array
    {
        $event = Event::find($id);

        if (!$event || (int)$event['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }

        return $event;
    }

    /** Valida campos requeridos del formulario de evento. */
    private function validateEvent(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'El nombre del evento es requerido.';
        }

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'La fecha de inicio es requerida.';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'La fecha de fin es requerida.';
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if ($data['end_date'] < $data['start_date']) {
                $errors['end_date'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        if (!empty($data['max_capacity']) && (!is_numeric($data['max_capacity']) || (int)$data['max_capacity'] < 1)) {
            $errors['max_capacity'] = 'La capacidad máxima debe ser un número positivo.';
        }

        return $errors;
    }

    /** Lista de zonas horarias disponibles. */
    private function getTimezones(): array
    {
        return [
            'America/Mexico_City'  => 'México Ciudad (CST)',
            'America/Monterrey'    => 'México Monterrey (CST)',
            'America/Cancun'       => 'México Cancún (EST)',
            'America/Panama'       => 'Panamá (EST)',
            'America/Bogota'       => 'Colombia (COT)',
            'America/Lima'         => 'Perú (PET)',
            'America/Caracas'      => 'Venezuela (VET)',
            'America/La_Paz'       => 'Bolivia (BOT)',
            'America/Santiago'     => 'Chile (CLT)',
            'America/Buenos_Aires' => 'Argentina (ART)',
            'America/Sao_Paulo'    => 'Brasil (BRT)',
            'America/New_York'     => 'USA Este (EST)',
            'America/Chicago'      => 'USA Central (CST)',
            'UTC'                  => 'UTC',
        ];
    }
}
