<?php
/**
 * ApiController — API RESTful para aplicaciones móviles de check-in.
 *
 * Todos los endpoints requieren autenticación via Bearer token en el header:
 * Authorization: Bearer {api_token}
 *
 * Respuestas estandarizadas en JSON:
 * - 200: { "success": true, "data": {...} }
 * - 4xx: { "success": false, "message": "..." }
 *
 * @package App\Controllers
 * @version 1.0.0
 *
 * Endpoints:
 * GET  /api/event/{slug}/sessions      Lista sesiones del evento
 * POST /api/checkin                    Procesar check-in (QR o manual)
 * GET  /api/attendee/{code}/agenda     Agenda personal del participante
 * GET  /api/event/{slug}/stats         Estadísticas en tiempo real
 * GET  /api/ping                       Health check
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Attendee;
use App\Models\AttendeeSession;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Services\CheckinStrategy\CheckinContext;

class ApiController extends Controller
{
    /** @var array|null Usuario autenticado via API token */
    private ?array $apiUser = null;

    // ─────────────────────────────────────────────────────────────────────────
    // Health Check
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/ping
     * Health check del API.
     */
    public function ping(): void
    {
        $this->json([
            'success'   => true,
            'message'   => 'pong',
            'timestamp' => date('c'),
            'version'   => '1.0.0',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Autenticación
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/login
     * Autentica a un usuario del staff y devuelve su api_token.
     */
    public function login(): void
    {
        $body = $this->getJsonBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            Response::jsonError('Email y contraseña requeridos.', 400);
        }

        $user = User::rawQueryFirst(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            Response::jsonError('Credenciales inválidas.', 401);
        }

        if (!in_array($user['role'], ['owner', 'admin', 'staff'])) {
            Response::jsonError('No tienes permisos para usar esta app.', 403);
        }

        // Generar api_token si no existe
        if (empty($user['api_token'])) {
            $user['api_token'] = bin2hex(random_bytes(32));
            User::update((int)$user['id'], ['api_token' => $user['api_token']]);
        }

        Response::jsonSuccess([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'tenant_id' => $user['tenant_id'],
            ],
            'api_token' => $user['api_token']
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Eventos y Sesiones
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/events
     * Lista todos los eventos activos del tenant del usuario autenticado.
     */
    public function events(): void
    {
        $this->requireAuth();
        $tenantId = (int)$this->apiUser['tenant_id'];

        $events = Event::rawQuery(
            "SELECT id, name, slug, start_date, end_date, status, venue_name
             FROM events WHERE tenant_id = :tid AND status != 'cancelled'
             ORDER BY start_date DESC",
            [':tid' => $tenantId]
        );

        Response::jsonSuccess([
            'events' => $events
        ]);
    }

    /**
     * GET /api/event/{slug}/sessions
     * Lista todas las sesiones de un evento (requiere auth).
     *
     * Query params:
     * - type: filtrar por tipo (keynote, workshop, etc.)
     * - date: filtrar por fecha (Y-m-d)
     */
    public function sessions(string $slug): void
    {
        $this->requireAuth();

        $tenantId = (int)($this->apiUser['tenant_id'] ?? 0);
        $event    = Event::findBySlug($tenantId, $slug);

        if (!$event) {
            Response::jsonError('Evento no encontrado.', 404);
        }

        $type   = $_GET['type'] ?? '';
        $params = [':eid' => $event['id']];
        $where  = 'WHERE es.event_id = :eid AND es.status != "cancelled"';

        if ($type) {
            $where          .= ' AND es.type = :type';
            $params[':type'] = $type;
        }

        $sessions = EventSession::rawQuery(
            "SELECT es.id, es.title, es.type, es.room, es.speaker_name,
                    es.start_time, es.end_time, es.max_attendees, es.is_virtual, es.virtual_link,
                    (SELECT COUNT(*) FROM attendee_sessions att_s
                     WHERE att_s.session_id = es.id AND att_s.status != 'cancelled') AS enrolled_count
             FROM event_sessions es {$where}
             ORDER BY es.start_time ASC",
            $params
        );

        Response::jsonSuccess([
            'event'    => ['id' => $event['id'], 'name' => $event['name'], 'slug' => $event['slug']],
            'sessions' => $sessions,
            'total'    => count($sessions),
        ]);
    }

    /**
     * GET /api/event/{slug}/stats
     * Estadísticas en tiempo real del evento (para dashboard móvil).
     */
    public function eventStats(string $slug): void
    {
        $this->requireAuth();

        $tenantId = (int)($this->apiUser['tenant_id'] ?? 0);
        $event    = Event::findBySlug($tenantId, $slug);

        if (!$event) {
            Response::jsonError('Evento no encontrado.', 404);
        }

        $stats = Event::getStats((int)$event['id']);

        Response::jsonSuccess([
            'event_id'         => $event['id'],
            'event_name'       => $event['name'],
            'total_sessions'   => (int)($stats['total_sessions']   ?? 0),
            'total_attendees'  => (int)($stats['total_attendees']  ?? 0),
            'checked_in'       => (int)($stats['checked_in']       ?? 0),
            'pending'          => (int)($stats['pending']          ?? 0),
            'attendance_rate'  => $stats['total_attendees'] > 0
                ? round((int)$stats['checked_in'] / (int)$stats['total_attendees'] * 100, 1)
                : 0.0,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Check-in
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/checkin
     * Procesa un check-in (QR o manual).
     *
     * Body JSON:
     * {
     *   "code":       "A1B2C3D4",    // Requerido: código del asistente
     *   "method":     "qr_code",     // Opcional: qr_code|manual|mobile|kiosk
     *   "session_id": 5,             // Opcional: null para check-in general
     *   "event_id":   3,             // Requerido para búsqueda por email (kiosk)
     *   "device_id":  "iPhone15...", // Opcional: ID del dispositivo
     * }
     */
    public function checkin(): void
    {
        $this->requireAuth();

        $body = $this->getJsonBody();

        $code      = trim($body['code']       ?? '');
        $method    = $body['method']           ?? 'qr_code';
        $sessionId = !empty($body['session_id']) ? (int)$body['session_id'] : null;

        if (empty($code)) {
            Response::jsonError('El campo "code" es requerido.');
        }

        if (!in_array($method, CheckinContext::available(), true)) {
            Response::jsonError("Método de check-in inválido: {$method}. Disponibles: " . implode(', ', CheckinContext::available()));
        }

        try {
            $context = new CheckinContext($method);
            $result  = $context->execute($code, $sessionId, [
                'user_id'    => $this->apiUser['id'] ?? null,
                'api_token'  => $this->getBearerToken(),
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'device_id'  => $body['device_id']  ?? '',
                'app_version'=> $body['app_version'] ?? '',
                'event_id'   => $body['event_id']    ?? null,
            ]);
        } catch (\Exception $e) {
            Response::jsonError('Error procesando check-in: ' . $e->getMessage(), 500);
        }

        $statusCode = $result['success'] ? 200 : ($result['already_in'] ?? false ? 409 : 400);
        $this->json($result, $statusCode);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Agenda del participante
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/attendee/{code}/agenda
     * Obtiene la agenda personal de un participante.
     */
    public function attendeeAgenda(string $code): void
    {
        $this->requireAuth();

        $attendee = Attendee::findByCode(strtoupper($code));

        if (!$attendee) {
            Response::jsonError('Participante no encontrado.', 404);
        }

        $agenda = AttendeeSession::getAgenda(
            (int)$attendee['id'],
            (int)$attendee['event_id']
        );

        Response::jsonSuccess([
            'attendee' => [
                'id'        => $attendee['id'],
                'full_name' => $attendee['full_name'],
                'email'     => $attendee['email'],
                'company'   => $attendee['company'],
                'status'    => $attendee['status'],
            ],
            'event' => [
                'id'   => $attendee['event_id'],
                'name' => $attendee['event_name'] ?? '',
            ],
            'agenda'       => $agenda,
            'total_sessions' => count($agenda),
        ]);
    }

    /**
     * GET /api/attendee/{code}
     * Obtiene los datos básicos del participante por código.
     */
    public function attendeeByCode(string $code): void
    {
        $this->requireAuth();

        $attendee = Attendee::findByCode(strtoupper($code));

        if (!$attendee) {
            Response::jsonError('Participante no encontrado.', 404);
        }

        Response::jsonSuccess([
            'id'             => $attendee['id'],
            'full_name'      => $attendee['full_name'],
            'email'          => $attendee['email'],
            'company'        => $attendee['company'],
            'position'       => $attendee['position'],
            'status'         => $attendee['status'],
            'checked_in_at'  => $attendee['checked_in_at'],
            'event_id'       => $attendee['event_id'],
            'event_name'     => $attendee['event_name'] ?? '',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica el Bearer token y carga el usuario API.
     * Responde 401 si no está autenticado.
     */
    private function requireAuth(): void
    {
        $token = $this->getBearerToken();

        if (!$token) {
            Response::jsonError('Authorization header requerido.', 401);
        }

        $user = User::rawQueryFirst(
            "SELECT * FROM users WHERE api_token = :token AND role IN ('owner','admin','staff') LIMIT 1",
            [':token' => $token]
        );

        if (!$user) {
            Response::jsonError('Token de API inválido o sin permisos.', 401);
        }

        $this->apiUser = $user;
    }

    /**
     * Extrae el Bearer token del header Authorization.
     */
    private function getBearerToken(): string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }

        return '';
    }

    /**
     * Decodifica el cuerpo JSON de la petición.
     */
    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
