<?php
/**
 * ValidationService — Servicio centralizado de validación de datos.
 *
 * Proporciona validaciones específicas del dominio del sistema
 * de gestión de eventos, más allá de las reglas básicas del ValidationTrait.
 *
 * @package App\Services
 * @version 1.0.0
 *
 * @example
 * ```php
 * $validator = new ValidationService();
 *
 * // Validar datos de registro de asistente
 * $errors = $validator->validateAttendeeRegistration($_POST, $eventId);
 *
 * // Validar formulario de evento
 * $errors = $validator->validateEvent($_POST, $tenantId);
 *
 * // Validar horario de sesión
 * $ok = $validator->validateSessionTime($startTime, $endTime, $eventId, $room);
 * ```
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Traits\ValidationTrait;
use App\Models\Event;
use App\Models\Attendee;

class ValidationService
{
    use ValidationTrait;

    // ─────────────────────────────────────────────────────────────────────────
    // Validaciones de dominio específicas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Valida el formulario de registro de un asistente.
     *
     * @param  array $data    Datos del formulario POST
     * @param  int   $eventId Evento al que se registra
     * @return array Errores encontrados (vacío = sin errores)
     */
    public function validateAttendeeRegistration(array $data, int $eventId): array
    {
        $errors = $this->validate($data, [
            'full_name' => 'required|min:3|max:200',
            'email'     => 'required|email|max:255',
            'phone'     => 'nullable|min:7|max:30',
            'company'   => 'nullable|max:200',
            'position'  => 'nullable|max:200',
        ], [
            'full_name' => 'Nombre completo',
            'email'     => 'Correo electrónico',
            'phone'     => 'Teléfono',
            'company'   => 'Empresa',
            'position'  => 'Puesto',
        ]);

        // Verificar email único en el evento
        if (empty($errors['email']) && !empty($data['email'])) {
            if (Attendee::isRegistered($eventId, trim($data['email']))) {
                $errors['email'] = 'Este email ya está registrado en el evento.';
            }
        }

        return $errors;
    }

    /**
     * Valida el formulario de creación/edición de evento.
     *
     * @param  array $data
     * @param  int   $tenantId
     * @param  int   $excludeId ID a excluir en validación de slug único
     * @return array
     */
    public function validateEvent(array $data, int $tenantId, int $excludeId = 0): array
    {
        $errors = $this->validate($data, [
            'name'         => 'required|min:3|max:200',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date',
            'timezone'     => 'required',
            'max_capacity' => 'nullable|integer|min:1',
            'virtual_link' => 'nullable|url',
            'status'       => 'required|in:draft,published,cancelled,completed',
        ], [
            'name'         => 'Nombre',
            'start_date'   => 'Fecha de inicio',
            'end_date'     => 'Fecha de fin',
            'timezone'     => 'Zona horaria',
            'max_capacity' => 'Capacidad máxima',
            'virtual_link' => 'Enlace virtual',
            'status'       => 'Estado',
        ]);

        // Fecha fin no puede ser antes que fecha inicio
        if (empty($errors['start_date']) && empty($errors['end_date'])) {
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                if ($data['end_date'] < $data['start_date']) {
                    $errors['end_date'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
                }
            }
        }

        // Unicidad de slug
        if (!empty($data['slug'])) {
            if (Event::slugExists($tenantId, $data['slug'], $excludeId)) {
                $errors['slug'] = 'Ya existe un evento con este slug en tu cuenta.';
            }
        }

        return $errors;
    }

    /**
     * Valida los datos de una sesión/charla.
     *
     * @param  array $data
     * @return array
     */
    public function validateSession(array $data): array
    {
        $errors = $this->validate($data, [
            'title'        => 'required|min:3|max:200',
            'type'         => 'required|in:keynote,talk,workshop,panel,networking,other',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'max_capacity' => 'nullable|integer|min:1',
            'virtual_link' => 'nullable|url',
            'status'       => 'required|in:scheduled,cancelled,completed',
        ], [
            'title'        => 'Título',
            'type'         => 'Tipo',
            'start_time'   => 'Hora de inicio',
            'end_time'     => 'Hora de fin',
            'max_capacity' => 'Cupo máximo',
        ]);

        // Hora fin después de hora inicio
        if (empty($errors['start_time']) && empty($errors['end_time'])) {
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                if ($data['end_time'] <= $data['start_time']) {
                    $errors['end_time'] = 'La hora de fin debe ser posterior a la hora de inicio.';
                }
            }
        }

        return $errors;
    }

    /**
     * Valida los datos de un patrocinador.
     *
     * @param  array $data
     * @return array
     */
    public function validateSponsor(array $data): array
    {
        return $this->validate($data, [
            'name'          => 'required|min:2|max:200',
            'tier'          => 'required|in:platinum,gold,silver,bronze,partner',
            'website'       => 'nullable|url',
            'contact_email' => 'nullable|email',
        ], [
            'name'          => 'Nombre',
            'tier'          => 'Nivel',
            'website'       => 'Sitio web',
            'contact_email' => 'Email de contacto',
        ]);
    }

    /**
     * Valida el formulario de login.
     *
     * @param  array $data
     * @return array
     */
    public function validateLogin(array $data): array
    {
        return $this->validate($data, [
            'email'    => 'required|email',
            'password' => 'required|min:1',
        ], [
            'email'    => 'Email',
            'password' => 'Contraseña',
        ]);
    }

    /**
     * Valida el formulario de registro de tenant (empresa).
     *
     * @param  array $data
     * @return array
     */
    public function validateTenantRegistration(array $data): array
    {
        return $this->validate($data, [
            'company_name'  => 'required|min:2|max:200',
            'subdomain'     => 'required|min:3|max:50',
            'email'         => 'required|email',
            'password'      => 'required|min:8|confirmed',
            'full_name'     => 'required|min:3|max:200',
        ], [
            'company_name' => 'Nombre de empresa',
            'subdomain'    => 'Subdominio',
            'email'        => 'Email',
            'password'     => 'Contraseña',
            'full_name'    => 'Tu nombre completo',
        ]);
    }

    /**
     * Sanitiza un array de entrada removiendo etiquetas HTML y espacios.
     *
     * @param  array $data
     * @param  array $fields Campos a sanitizar (todos si vacío)
     * @return array
     */
    public function sanitize(array $data, array $fields = []): array
    {
        $targets = $fields ?: array_keys($data);

        foreach ($targets as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        return $data;
    }
}
