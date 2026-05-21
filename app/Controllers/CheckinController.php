<?php
/**
 * CheckinController — Escáner QR y check-in de asistentes.
 *
 * Orquesta el Strategy Pattern para procesar check-ins
 * por QR o de forma manual.
 *
 * @package App\Controllers
 * @version 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\Checkin;
use App\Services\CheckinStrategy\CheckinContext;

class CheckinController extends Controller
{
    /**
     * GET /admin/events/{eventId}/checkin
     * Página del escáner de check-in.
     */
    public function scanner(string $eventId): void
    {
        $event   = $this->findEventOrAbort((int)$eventId);
        $summary = Attendee::attendanceSummary((int)$eventId);

        $this->render('checkin/scanner', [
            'title'     => 'Check-in — ' . $event['name'],
            'event'     => $event,
            'summary'   => $summary,
            'pageTitle' => 'Escáner de Check-in',
        ]);
    }

    /**
     * POST /admin/events/{eventId}/checkin
     * Procesa un check-in por QR (request AJAX o normal).
     *
     * Body esperado: { code: 'ABC...', session_id: null|int, method: 'qr_code' }
     */
    public function process(string $eventId): void
    {
        $this->validateCsrf();
        $this->findEventOrAbort((int)$eventId);

        $code      = trim($_POST['code'] ?? '');
        $sessionId = !empty($_POST['session_id']) ? (int)$_POST['session_id'] : null;
        $method    = $_POST['method'] ?? 'qr_code';

        $user = authUser();

        try {
            $context = new CheckinContext($method);
            $result  = $context->execute($code, $sessionId, [
                'event_id' => (int)$eventId,
                'user_id'  => $user['id'] ?? null,
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
                'device'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (\InvalidArgumentException $e) {
            $result = ['success' => false, 'message' => '❌ Método de check-in inválido.'];
        }

        // Responder JSON si es AJAX
        if ($this->isAjax()) {
            $this->json($result, $result['success'] ? 200 : 422);
            return;
        }

        // Respuesta normal (sin JS)
        $type = $result['success'] ? 'success' : ($result['already_in'] ?? false ? 'warning' : 'error');
        $this->flash($type, $result['message']);
        $this->redirect("/admin/events/{$eventId}/checkin");
    }


    /**
     * GET /admin/events/{eventId}/checkin/list
     * Lista de check-ins realizados.
     */
    public function list(string $eventId): void
    {
        $event    = $this->findEventOrAbort((int)$eventId);
        $checkins = Checkin::byEvent((int)$eventId);
        $summary  = Attendee::attendanceSummary((int)$eventId);

        $this->render('checkin/list', [
            'title'     => 'Registro de Check-ins — ' . $event['name'],
            'event'     => $event,
            'checkins'  => $checkins,
            'summary'   => $summary,
            'pageTitle' => 'Registro de Check-ins',
        ]);
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
