<?php
/**
 * DashboardController — Panel principal con métricas y estadísticas.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Tenant;
use App\Models\Event;
use App\Models\Attendee;

class DashboardController extends Controller
{
    /**
     * GET /admin/dashboard
     * Dashboard principal del tenant.
     */
    public function index(): void
    {
        $tid    = (int)tenantId();
        $tenant = currentTenant();

        // Estadísticas globales del tenant
        $stats = Tenant::getStats($tid);

        // Próximos 5 eventos
        $upcomingEvents = Event::rawQuery(
            "SELECT e.*,
                (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status != 'cancelled') AS attendee_count
             FROM events e
             WHERE e.tenant_id = :tid AND e.start_date >= CURDATE()
             ORDER BY e.start_date ASC LIMIT 5",
            [':tid' => $tid]
        );

        // Últimos 10 check-ins
        $recentCheckins = Attendee::rawQuery(
            "SELECT a.full_name, a.email, a.company, a.checked_in_at, e.name AS event_name
             FROM attendees a
             INNER JOIN events e ON a.event_id = e.id
             WHERE a.tenant_id = :tid AND a.status = 'checked_in'
             ORDER BY a.checked_in_at DESC LIMIT 10",
            [':tid' => $tid]
        );

        // Eventos recientes (últimos 5)
        $recentEvents = Event::rawQuery(
            "SELECT * FROM events WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 5",
            [':tid' => $tid]
        );

        $this->render('dashboard/index', [
            'title'          => 'Dashboard — ' . $tenant['name'],
            'pageTitle'      => 'Panel de Control',
            'stats'          => $stats,
            'upcomingEvents' => $upcomingEvents,
            'recentCheckins' => $recentCheckins,
            'recentEvents'   => $recentEvents,
            'tenant'         => $tenant,
        ]);
    }

    /**
     * GET /admin/dashboard/live/{eventId}
     * Estadísticas en tiempo real para polling AJAX del dashboard.
     */
    public function liveStats(string $eventId): void
    {
        $event = Event::find((int)$eventId);
        if (!$event || (int)$event['tenant_id'] !== (int)tenantId()) {
            Response::jsonError('Evento no encontrado', 404);
            return;
        }

        $summary = Attendee::attendanceSummary((int)$eventId);

        $recentCheckins = Attendee::rawQuery(
            "SELECT full_name, company, checked_in_at
             FROM attendees
             WHERE event_id = :eid AND status = 'checked_in'
             ORDER BY checked_in_at DESC LIMIT 10",
            [':eid' => (int)$eventId]
        );

        Response::json([
            'summary'        => $summary,
            'recentCheckins' => $recentCheckins,
            'timestamp'      => date('Y-m-d H:i:s'),
        ]);
    }
}
