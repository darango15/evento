<?php
/**
 * ReportController — Reportes y exportaciones del evento.
 *
 * Genera reportes de asistencia, exportaciones CSV y datos para gráficos.
 * Solo accesible para roles: owner, admin, staff.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Event;
use App\Services\ReportService;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vistas de reportes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/events/{eventId}/reports
     * Dashboard de reportes del evento.
     */
    public function index(string $eventId): void
    {
        $event = $this->findEventOrAbort((int)$eventId);

        $attendance    = $this->reportService->getAttendanceReport((int)$eventId);
        $sessionReport = $this->reportService->getSessionAttendanceReport((int)$eventId);
        $checkinChart  = $this->reportService->getCheckinsByHourChart((int)$eventId);
        $companyChart  = $this->reportService->getAttendeesByCompanyChart((int)$eventId);
        $sponsors      = $this->reportService->getSponsorsReport((int)$eventId);

        $this->render('reports/index', [
            'title'         => 'Reportes — ' . $event['name'],
            'pageTitle'     => 'Reportes del Evento',
            'event'         => $event,
            'attendance'    => $attendance,
            'sessionReport' => $sessionReport,
            'checkinChart'  => $checkinChart,
            'companyChart'  => $companyChart,
            'sponsors'      => $sponsors,
        ]);
    }

    /**
     * GET /admin/events/{eventId}/reports/attendees
     * Vista de reporte de asistentes.
     */
    public function attendees(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $report  = $this->reportService->getAttendanceReport((int)$eventId);

        $this->render('reports/attendees', [
            'title'     => 'Reporte de Asistentes — ' . $event['name'],
            'pageTitle' => 'Reporte de Asistentes',
            'event'     => $event,
            'report'    => $report,
        ]);
    }

    /**
     * GET /admin/events/{eventId}/reports/sessions
     * Vista de reporte de sesiones.
     */
    public function sessions(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $sessions = $this->reportService->getSessionAttendanceReport((int)$eventId);

        $this->render('reports/sessions', [
            'title'     => 'Reporte de Sesiones — ' . $event['name'],
            'pageTitle' => 'Asistencia por Sesión',
            'event'     => $event,
            'sessions'  => $sessions,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exportaciones CSV
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/events/{eventId}/reports/export/attendees
     * Descarga CSV con todos los asistentes.
     */
    public function exportAttendeesCsv(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $status  = $_GET['status'] ?? '';
        $csv     = $this->reportService->exportAttendeesToCsv((int)$eventId, $status);

        $filename = 'asistentes-' . slugify($event['name']) . '-' . date('Ymd') . '.csv';
        Response::downloadContent($csv, $filename, 'text/csv; charset=UTF-8');
    }

    /**
     * GET /admin/events/{eventId}/reports/export/sessions
     * Descarga CSV con asistencia por sesión.
     */
    public function exportSessionsCsv(string $eventId): void
    {
        $event    = $this->findEventOrAbort((int)$eventId);
        $csv      = $this->reportService->exportSessionAttendanceToCsv((int)$eventId);
        $filename = 'sesiones-' . slugify($event['name']) . '-' . date('Ymd') . '.csv';
        Response::downloadContent($csv, $filename, 'text/csv; charset=UTF-8');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Endpoints AJAX para gráficos en tiempo real
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/events/{eventId}/reports/chart/checkins
     * JSON con datos de check-in por hora para Chart.js.
     */
    public function chartCheckins(string $eventId): void
    {
        $this->findEventOrAbort((int)$eventId);
        $data = $this->reportService->getCheckinsByHourChart((int)$eventId);
        $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /admin/events/{eventId}/reports/chart/companies
     * JSON con distribución de asistentes por empresa.
     */
    public function chartCompanies(string $eventId): void
    {
        $this->findEventOrAbort((int)$eventId);
        $data = $this->reportService->getAttendeesByCompanyChart((int)$eventId);
        $this->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function findEventOrAbort(int $id): array
    {
        $event = Event::find($id);

        if (!$event || (int)$event['tenant_id'] !== (int)tenantId()) {
            $this->abort(404);
        }

        return $event;
    }
}
