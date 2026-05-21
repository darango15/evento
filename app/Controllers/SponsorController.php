<?php
/**
 * SponsorController — Gestión de patrocinadores de eventos.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\Sponsor;

class SponsorController extends Controller
{
    /** GET /admin/events/{eventId}/sponsors */
    public function index(string $eventId): void
    {
        $event    = $this->findEventOrAbort((int)$eventId);
        $sponsors = Sponsor::byEventGrouped((int)$eventId);

        $this->render('sponsors/index', [
            'title'     => 'Patrocinadores — ' . $event['name'],
            'event'     => $event,
            'sponsors'  => $sponsors,
            'pageTitle' => 'Patrocinadores',
        ]);
    }

    /** POST /admin/events/{eventId}/sponsors */
    public function store(string $eventId): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);

        $data = $this->input(['name', 'website', 'description', 'tier', 'contact_name', 'contact_email', 'sort_order']);

        if (empty($data['name'])) {
            $this->flash('error', 'El nombre del patrocinador es requerido.');
            $this->redirect("/admin/events/{$eventId}/sponsors");
            return;
        }

        $data['event_id']   = (int)$eventId;
        $data['sort_order'] = (int)($data['sort_order'] ?? 0);

        Sponsor::create($data);

        $this->flash('success', 'Patrocinador añadido correctamente.');
        $this->redirect("/admin/events/{$eventId}/sponsors");
    }

    /** POST /admin/events/{eventId}/sponsors/{id} */
    public function update(string $eventId, string $id): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);

        $data = $this->input(['name', 'website', 'description', 'tier', 'contact_name', 'contact_email', 'sort_order']);

        if (empty($data['name'])) {
            $this->flash('error', 'El nombre del patrocinador es requerido.');
            $this->redirect("/admin/events/{$eventId}/sponsors");
            return;
        }

        $data['sort_order'] = (int)($data['sort_order'] ?? 0);
        Sponsor::update((int)$id, $data);

        $this->flash('success', 'Patrocinador actualizado correctamente.');
        $this->redirect("/admin/events/{$eventId}/sponsors");
    }

    /** DELETE /admin/events/{eventId}/sponsors/{id} */
    public function destroy(string $eventId, string $id): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);
        Sponsor::delete((int)$id);

        $this->flash('success', 'Patrocinador eliminado.');
        $this->redirect("/admin/events/{$eventId}/sponsors");
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
