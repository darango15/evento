<?php
/**
 * Configuración general de la aplicación.
 *
 * @return array
 */

declare(strict_types=1);

return [
    'name'        => env('APP_NAME',  'EventoSaaS'),
    'env'         => env('APP_ENV',   'production'),
    'debug'       => env('APP_DEBUG', 'false') === 'true',
    'url'         => env('APP_URL',   'http://localhost'),
    'key'         => env('APP_KEY',   ''),
    'timezone'    => env('APP_TIMEZONE', 'America/Mexico_City'),

    // Multi-tenancy
    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'evento.test'),

    // Sesión
    'session' => [
        'name'     => env('SESSION_NAME',     'evento_session'),
        'lifetime' => (int)env('SESSION_LIFETIME', 7200),
    ],

    // Subida de archivos
    'uploads' => [
        'max_size'     => (int)env('UPLOAD_MAX_SIZE', 5242880),
        'allowed_types'=> explode(',', env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf')),
        'path'         => env('UPLOAD_PATH', 'public/assets/uploads'),
    ],

    // Rate Limiting
    'rate_limit' => [
        'enabled'  => true,
        'max_rpm'  => (int)env('RATE_LIMIT_RPM', 100),
        'window'   => 60,
    ],

    // Planes del sistema SaaS
    'plans' => [
        'basic' => [
            'name'          => 'Basic',
            'max_events'    => 3,
            'max_attendees' => 100,
            'max_sessions'  => 10,
            'features'      => ['qr_checkin', 'basic_reports', 'email_notifications'],
        ],
        'pro' => [
            'name'          => 'Pro',
            'max_events'    => 20,
            'max_attendees' => 1000,
            'max_sessions'  => 50,
            'features'      => ['qr_checkin', 'advanced_reports', 'email_notifications', 'sms_notifications', 'api_access', 'custom_fields'],
        ],
        'enterprise' => [
            'name'          => 'Enterprise',
            'max_events'    => -1,  // Ilimitado
            'max_attendees' => -1,
            'max_sessions'  => -1,
            'features'      => ['all'],
        ],
    ],

    // Roles del sistema
    'roles' => ['owner', 'admin', 'staff', 'attendee'],

    // Tipos de sesión de evento
    'session_types' => [
        'keynote'    => 'Keynote',
        'talk'       => 'Charla',
        'workshop'   => 'Taller',
        'panel'      => 'Panel',
        'networking' => 'Networking',
        'other'      => 'Otro',
    ],

    // Niveles de patrocinio
    'sponsor_tiers' => [
        'platinum' => ['label' => 'Platinum', 'color' => '#e5e4e2'],
        'gold'     => ['label' => 'Gold',     'color' => '#ffd700'],
        'silver'   => ['label' => 'Silver',   'color' => '#c0c0c0'],
        'bronze'   => ['label' => 'Bronze',   'color' => '#cd7f32'],
        'partner'  => ['label' => 'Partner',  'color' => '#6366f1'],
    ],
];
