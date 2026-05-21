<?php
/**
 * ReportService — Generación de reportes en CSV y datos para gráficos.
 *
 * Genera exportaciones de datos y reportes estadísticos del evento:
 * lista de asistentes, asistencia por sesión, feedback, etc.
 *
 * @package App\Services
 * @version 1.0.0
 *
 * @example
 * ```php
 * $service = new ReportService();
 *
 * // Exportar asistentes a CSV
 * $csv = $service->exportAttendeesToCsv($eventId);
 * Response::downloadContent($csv, 'asistentes.csv', 'text/csv');
 *
 * // Reporte de asistencia
 * $report = $service->getAttendanceReport($eventId);
 *
 * // Datos para Chart.js
 * $chartData = $service->getCheckinsByHourChart($eventId);
 * ```
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Event;
use App\Models\Attendee;

class ReportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exportación CSV
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Exporta la lista completa de asistentes de un evento a CSV.
     *
     * @param  int    $eventId
     * @param  string $status  Filtrar por estado ('', 'registered', 'checked_in', etc.)
     * @return string Contenido CSV con BOM UTF-8
     */
    public function exportAttendeesToCsv(int $eventId, string $status = ''): string
    {
        $params = [':eid' => $eventId];
        $where  = 'WHERE a.event_id = :eid';

        if ($status) {
            $where          .= ' AND a.status = :status';
            $params[':status'] = $status;
        }

        $rows = $this->db->query(
            "SELECT a.full_name, a.email, a.phone, a.company, a.position,
                    a.status, a.check_in_code,
                    DATE_FORMAT(a.registration_date, '%d/%m/%Y %H:%i') AS registered_at,
                    DATE_FORMAT(a.checked_in_at,     '%d/%m/%Y %H:%i') AS checked_in_at,
                    a.dietary_restrictions, a.special_needs
             FROM attendees a {$where}
             ORDER BY a.full_name ASC",
            $params
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->buildCsv([
            'Nombre Completo', 'Email', 'Teléfono', 'Empresa', 'Puesto',
            'Estado', 'Código Check-in', 'Fecha Registro', 'Fecha Check-in',
            'Restricciones Dietéticas', 'Necesidades Especiales',
        ], $rows);
    }

    /**
     * Exporta la asistencia por sesión a CSV.
     *
     * @param  int $eventId
     * @return string
     */
    public function exportSessionAttendanceToCsv(int $eventId): string
    {
        $rows = $this->db->query(
            "SELECT
                es.title AS sesion,
                es.type  AS tipo,
                es.room  AS sala,
                DATE_FORMAT(es.start_time, '%H:%i') AS hora_inicio,
                DATE_FORMAT(es.end_time,   '%H:%i') AS hora_fin,
                es.speaker_name AS speaker,
                es.max_capacity AS cupo_maximo,
                COUNT(DISTINCT att_s.attendee_id) AS inscritos,
                SUM(att_s.checkin_at IS NOT NULL) AS asistentes,
                ROUND(
                    IF(COUNT(DISTINCT att_s.attendee_id) > 0,
                       SUM(att_s.checkin_at IS NOT NULL) / COUNT(DISTINCT att_s.attendee_id) * 100,
                       0), 1) AS porcentaje_asistencia
             FROM event_sessions es
             LEFT JOIN attendee_sessions att_s ON att_s.session_id = es.id AND att_s.status != 'cancelled'
             WHERE es.event_id = :eid AND es.status != 'cancelled'
             GROUP BY es.id
             ORDER BY es.start_time ASC",
            [':eid' => $eventId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->buildCsv([
            'Sesión', 'Tipo', 'Sala', 'Hora Inicio', 'Hora Fin', 'Speaker',
            'Cupo Máximo', 'Inscritos', 'Asistentes', '% Asistencia',
        ], $rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reportes estadísticos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Reporte general de asistencia del evento.
     *
     * @param  int $eventId
     * @return array
     */
    public function getAttendanceReport(int $eventId): array
    {
        $event   = Event::find($eventId);
        $summary = Attendee::attendanceSummary($eventId);

        $total      = (int)($summary['total']      ?? 0);
        $checkedIn  = (int)($summary['checked_in'] ?? 0);
        $percentage = $total > 0 ? round($checkedIn / $total * 100, 1) : 0;

        return [
            'event'              => $event,
            'summary'            => $summary,
            'attendance_rate'    => $percentage,
            'no_show_rate'       => $total > 0 ? round((int)($summary['no_show'] ?? 0) / $total * 100, 1) : 0,
            'cancellation_rate'  => $total > 0 ? round((int)($summary['cancelled'] ?? 0) / $total * 100, 1) : 0,
        ];
    }

    /**
     * Reporte de asistencia por sesión con porcentajes.
     *
     * @param  int $eventId
     * @return array
     */
    public function getSessionAttendanceReport(int $eventId): array
    {
        return $this->db->query(
            "SELECT
                es.id, es.title, es.type, es.room, es.speaker_name,
                DATE_FORMAT(es.start_time, '%H:%i') AS start_time,
                DATE_FORMAT(es.end_time,   '%H:%i') AS end_time,
                es.max_capacity,
                COUNT(DISTINCT att_s.attendee_id)    AS enrolled,
                SUM(att_s.checkin_at IS NOT NULL)    AS attended,
                ROUND(
                    IF(COUNT(DISTINCT att_s.attendee_id) > 0,
                       SUM(att_s.checkin_at IS NOT NULL) / COUNT(DISTINCT att_s.attendee_id) * 100,
                       0), 1) AS attendance_pct
             FROM event_sessions es
             LEFT JOIN attendee_sessions att_s ON att_s.session_id = es.id AND att_s.status != 'cancelled'
             WHERE es.event_id = :eid AND es.status != 'cancelled'
             GROUP BY es.id
             ORDER BY es.start_time ASC",
            [':eid' => $eventId]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Datos de check-in por hora para gráfico de barras (Chart.js).
     *
     * @param  int $eventId
     * @return array ['labels' => ['08:00','09:00',...], 'data' => [5, 12, ...]]
     */
    public function getCheckinsByHourChart(int $eventId): array
    {
        $rows = $this->db->query(
            "SELECT HOUR(checked_in_at) AS hour, COUNT(*) AS count
             FROM attendees
             WHERE event_id = :eid AND status = 'checked_in' AND checked_in_at IS NOT NULL
             GROUP BY HOUR(checked_in_at)
             ORDER BY hour ASC",
            [':eid' => $eventId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $byHour = [];
        foreach ($rows as $row) {
            $byHour[(int)$row['hour']] = (int)$row['count'];
        }

        $labels = [];
        $data   = [];
        for ($h = 0; $h <= 23; $h++) {
            if (isset($byHour[$h]) || !empty($data)) {
                $labels[] = sprintf('%02d:00', $h);
                $data[]   = $byHour[$h] ?? 0;
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Distribución de asistentes por empresa para gráfico de pastel.
     *
     * @param  int $eventId
     * @param  int $topN   Solo las N empresas principales
     * @return array
     */
    public function getAttendeesByCompanyChart(int $eventId, int $topN = 10): array
    {
        $rows = $this->db->query(
            "SELECT COALESCE(NULLIF(company, ''), 'Sin empresa') AS label,
                    COUNT(*) AS value
             FROM attendees
             WHERE event_id = :eid AND status != 'cancelled'
             GROUP BY company
             ORDER BY value DESC
             LIMIT :n",
            [':eid' => $eventId, ':n' => $topN]
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'labels' => array_column($rows, 'label'),
            'data'   => array_map('intval', array_column($rows, 'value')),
        ];
    }

    /**
     * Reporte de sponsors de un evento.
     *
     * @param  int $eventId
     * @return array
     */
    public function getSponsorsReport(int $eventId): array
    {
        return $this->db->query(
            "SELECT name, tier, website, contact_name, contact_email, display_order,
                    (SELECT COUNT(*) FROM events WHERE id = :eid2) AS event_attendees
             FROM sponsors
             WHERE event_id = :eid1
             ORDER BY FIELD(tier, 'platinum','gold','silver','bronze','partner'), display_order ASC",
            [':eid1' => $eventId, ':eid2' => $eventId]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye contenido CSV con BOM UTF-8 para compatibilidad con Excel.
     *
     * @param  array $headers Cabeceras de columna
     * @param  array $rows    Filas de datos
     * @return string
     */
    private function buildCsv(array $headers, array $rows): string
    {
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= $this->csvRow($headers);

        foreach ($rows as $row) {
            $output .= $this->csvRow(array_values($row));
        }

        return $output;
    }

    /**
     * Convierte un array en una línea CSV correctamente escapada.
     *
     * @param  array $fields
     * @return string
     */
    private function csvRow(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $value = (string)($field ?? '');
            // Escapar comillas dobles y envolver en comillas si hay comas/saltos
            if (str_contains($value, '"') || str_contains($value, ',') || str_contains($value, "\n")) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            return $value;
        }, $fields);

        return implode(',', $escaped) . "\r\n";
    }
}
