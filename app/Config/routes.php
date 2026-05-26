<?php
/**
 * Definición de rutas de la aplicación.
 *
 * Todas las rutas se registran aquí usando el Router.
 * El archivo es incluido desde public/index.php después del bootstrap.
 *
 * Convenciones:
 * - Rutas públicas: sin middleware
 * - Rutas admin: ['auth', 'tenant']
 * - Rutas por rol: ['auth', 'tenant', 'role:owner,admin']
 * - Rutas API: ['api_auth'] (validado en ApiController)
 *
 * @var \App\Core\Router $router
 */

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\AgendaController;
use App\Controllers\AttendeeController;
use App\Controllers\CheckinController;
use App\Controllers\SponsorController;
use App\Controllers\ReportController;
use App\Controllers\ApiController;

// ─────────────────────────────────────────────────────────────────────────────
// Rutas públicas
// ─────────────────────────────────────────────────────────────────────────────

$router->get('/',                            [EventController::class,  'publicIndex']);
$router->get('/eventos/{slug}',              [EventController::class,  'publicShow']);
$router->get('/eventos/{slug}/registro',                    [AttendeeController::class, 'registrationForm']);
$router->post('/eventos/{slug}/registro',                   [AttendeeController::class, 'register']);
$router->get('/eventos/{slug}/confirmacion/{code}',         [AttendeeController::class, 'confirmation']);
$router->get('/registro/ticket/{code}',                     [AttendeeController::class, 'ticket']);
$router->get('/registro/qr/{code}',                         [AttendeeController::class, 'qrImage']);

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación
// ─────────────────────────────────────────────────────────────────────────────

$router->get('/login',  [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// ─────────────────────────────────────────────────────────────────────────────
// Panel Admin — requieren auth + tenant
// ─────────────────────────────────────────────────────────────────────────────

$router->group('/admin', ['auth', 'tenant'], function ($r) {

    // Dashboard
    $r->get('/dashboard', [DashboardController::class, 'index']);
    $r->get('/dashboard/live/{eventId}', [DashboardController::class, 'liveStats']);

    // Eventos
    $r->get('/events',              [EventController::class, 'index']);
    $r->get('/events/create',       [EventController::class, 'create']);
    $r->post('/events',             [EventController::class, 'store']);
    $r->get('/events/{id}',         [EventController::class, 'show']);
    $r->get('/events/{id}/edit',    [EventController::class, 'edit']);
    $r->put('/events/{id}',         [EventController::class, 'update']);
    $r->delete('/events/{id}',      [EventController::class, 'destroy']);

    // Agenda/Sesiones
    $r->get('/events/{eventId}/agenda',              [AgendaController::class, 'index']);
    $r->get('/events/{eventId}/agenda/create',       [AgendaController::class, 'create']);
    $r->post('/events/{eventId}/agenda',             [AgendaController::class, 'store']);
    $r->get('/events/{eventId}/agenda/{id}',         [AgendaController::class, 'show']);
    $r->get('/events/{eventId}/agenda/{id}/edit',    [AgendaController::class, 'edit']);
    $r->put('/events/{eventId}/agenda/{id}',         [AgendaController::class, 'update']);
    $r->delete('/events/{eventId}/agenda/{id}',      [AgendaController::class, 'destroy']);

    // Asistentes
    $r->get('/events/{eventId}/attendees',           [AttendeeController::class, 'index']);
    $r->get('/events/{eventId}/tickets/print',       [AttendeeController::class, 'printTickets']);
    $r->get('/attendees/{id}',                       [AttendeeController::class, 'show']);
    $r->delete('/attendees/{id}',                    [AttendeeController::class, 'destroy']);
    $r->post('/attendees/{id}/restore',              [AttendeeController::class, 'restore']);

    // Check-in
    $r->get('/events/{eventId}/checkin',             [CheckinController::class, 'scanner']);
    $r->post('/events/{eventId}/checkin',            [CheckinController::class, 'process']);
    $r->get('/events/{eventId}/checkins',            [CheckinController::class, 'list']);

    // Patrocinadores
    $r->get('/events/{eventId}/sponsors',            [SponsorController::class, 'index']);
    $r->post('/events/{eventId}/sponsors',           [SponsorController::class, 'store']);
    $r->post('/events/{eventId}/sponsors/{id}',      [SponsorController::class, 'update']);
    $r->delete('/events/{eventId}/sponsors/{id}',    [SponsorController::class, 'destroy']);

    // Reportes
    $r->get('/events/{eventId}/reports',                    [ReportController::class, 'index']);
    $r->get('/events/{eventId}/reports/attendees',          [ReportController::class, 'attendees']);
    $r->get('/events/{eventId}/reports/sessions',           [ReportController::class, 'sessions']);
    $r->get('/events/{eventId}/reports/export/attendees',   [ReportController::class, 'exportAttendeesCsv']);
    $r->get('/events/{eventId}/reports/export/sessions',    [ReportController::class, 'exportSessionsCsv']);
    $r->get('/events/{eventId}/reports/chart/checkins',     [ReportController::class, 'chartCheckins']);
    $r->get('/events/{eventId}/reports/chart/companies',    [ReportController::class, 'chartCompanies']);
});

// ─────────────────────────────────────────────────────────────────────────────
// API RESTful para apps móviles (autenticación via Bearer token en controller)
// ─────────────────────────────────────────────────────────────────────────────

$router->get('/api/ping',                        [ApiController::class, 'ping']);
$router->post('/api/login',                      [ApiController::class, 'login']);
$router->get('/api/events',                      [ApiController::class, 'events']);
$router->get('/api/event/{slug}/sessions',       [ApiController::class, 'sessions']);
$router->get('/api/event/{slug}/stats',          [ApiController::class, 'eventStats']);
$router->post('/api/checkin',                    [ApiController::class, 'checkin']);
$router->get('/api/attendee/{code}/agenda',      [ApiController::class, 'attendeeAgenda']);
$router->get('/api/attendee/{code}',             [ApiController::class, 'attendeeByCode']);
