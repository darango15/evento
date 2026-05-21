-- =============================================================================
-- EventoSaaS — Datos de prueba mínimos (Demo Seed)
-- Incluye: 1 tenant, 2 usuarios, 1 evento, 2 sesiones, 3 asistentes
-- Contraseña de todos los usuarios: Admin1234!
-- =============================================================================

USE `evento_saas`;

-- -----------------------------------------------------------------------------
-- 1. Tenant demo
-- -----------------------------------------------------------------------------
INSERT INTO `tenants` (`subdomain`, `name`, `email`, `phone`, `plan`, `status`, `trial_ends_at`, `settings`)
VALUES (
    'demo',
    'TechConf México',
    'hola@techconfmx.com',
    '+52 55 1234 5678',
    'pro',
    'active',
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    JSON_OBJECT(
        'primary_color', '#6366F1',
        'secondary_color', '#8B5CF6',
        'timezone', 'America/Mexico_City',
        'language', 'es'
    )
);

-- Guarda el ID del tenant (asumimos que será 1)
SET @tenant_id = LAST_INSERT_ID();

-- -----------------------------------------------------------------------------
-- 2. Usuarios
-- Contraseña: Admin1234! → hash argon2id
-- Generado con: password_hash('Admin1234!', PASSWORD_ARGON2ID)
-- -----------------------------------------------------------------------------

-- Superadmin central (sin tenant)
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `is_active`)
VALUES (
    NULL,
    'superadmin@evento.test',
    '$argon2id$v=19$m=65536,t=4,p=1$QXpFaXRUb3pJTFkxV3hRVw$3XPjKlBzWqK5S8y2N1mD4YLJgKpVfRgJ7mX8sT2eCa4',
    'Super Administrador',
    'superadmin',
    1
);

-- Owner del tenant demo
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `is_active`)
VALUES (
    @tenant_id,
    'admin@techconfmx.com',
    '$argon2id$v=19$m=65536,t=4,p=1$QXpFaXRUb3pJTFkxV3hRVw$3XPjKlBzWqK5S8y2N1mD4YLJgKpVfRgJ7mX8sT2eCa4',
    'Carlos Ramírez',
    'owner',
    1
);

-- Staff del tenant demo
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `is_active`)
VALUES (
    @tenant_id,
    'staff@techconfmx.com',
    '$argon2id$v=19$m=65536,t=4,p=1$QXpFaXRUb3pJTFkxV3hRVw$3XPjKlBzWqK5S8y2N1mD4YLJgKpVfRgJ7mX8sT2eCa4',
    'María González',
    'staff',
    1
);

-- -----------------------------------------------------------------------------
-- 3. Evento demo
-- -----------------------------------------------------------------------------
INSERT INTO `events` (
    `tenant_id`, `slug`, `name`, `description`, `start_date`, `end_date`,
    `timezone`, `venue_name`, `venue_address`, `max_capacity`,
    `is_virtual`, `status`, `settings`
)
VALUES (
    @tenant_id,
    'tech-summit-2025',
    'Tech Summit México 2025',
    'El evento de tecnología más importante del año. Tres días de conferencias, talleres y networking con los líderes de la industria tech.',
    '2025-09-15',
    '2025-09-17',
    'America/Mexico_City',
    'Centro de Convenciones WTC Ciudad de México',
    'Montecito 38, Nápoles, Benito Juárez, 03810 Ciudad de México, CDMX',
    500,
    0,
    'published',
    JSON_OBJECT(
        'primary_color', '#6366F1',
        'registration_fields', JSON_ARRAY('company', 'position', 'dietary_restrictions'),
        'allow_session_selection', true,
        'send_confirmation_email', true
    )
);

SET @event_id = LAST_INSERT_ID();

-- -----------------------------------------------------------------------------
-- 4. Sesiones (agenda)
-- -----------------------------------------------------------------------------
INSERT INTO `event_sessions` (
    `event_id`, `title`, `description`, `type`,
    `speaker_name`, `speaker_bio`,
    `start_time`, `end_time`, `room`, `max_attendees`, `sort_order`, `status`
)
VALUES (
    @event_id,
    'Keynote: El Futuro de la IA en Latinoamérica',
    'Una visión profunda sobre cómo la inteligencia artificial está transformando las industrias en América Latina, con casos de uso reales y proyecciones para los próximos 5 años.',
    'keynote',
    'Dr. Alejandro Torres',
    'Director de IA en Tecnova. PhD en Computer Science por el MIT. Más de 15 años de experiencia en machine learning aplicado a industrias.',
    '2025-09-15 09:00:00',
    '2025-09-15 10:30:00',
    'Auditorio Principal',
    500,
    1,
    'scheduled'
);

SET @session1_id = LAST_INSERT_ID();

INSERT INTO `event_sessions` (
    `event_id`, `title`, `description`, `type`,
    `speaker_name`, `speaker_bio`,
    `start_time`, `end_time`, `room`, `max_attendees`, `sort_order`, `status`
)
VALUES (
    @event_id,
    'Taller Práctico: Desarrollo de APIs con PHP Moderno',
    'Aprende a construir APIs REST escalables usando PHP 8.2, patrones de diseño avanzados y las mejores prácticas de seguridad. Sesión 100% hands-on.',
    'workshop',
    'Ing. Patricia Morales',
    'Senior Backend Engineer en CloudMX. Contribuidora activa de proyectos open source PHP. Autora del libro "PHP Moderno en Producción".',
    '2025-09-15 11:00:00',
    '2025-09-15 13:00:00',
    'Sala 3 - Taller',
    40,
    2,
    'scheduled'
);

SET @session2_id = LAST_INSERT_ID();

-- -----------------------------------------------------------------------------
-- 5. Asistentes (3 registros)
-- -----------------------------------------------------------------------------
INSERT INTO `attendees` (
    `tenant_id`, `event_id`, `email`, `full_name`, `phone`,
    `company`, `position`, `check_in_code`, `status`,
    `dietary_restrictions`
)
VALUES
(
    @tenant_id, @event_id,
    'juan.garcia@ejemplo.com', 'Juan García López', '+52 55 9876 5432',
    'Startup MX', 'CTO',
    'A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6',
    'registered', NULL
),
(
    @tenant_id, @event_id,
    'ana.martinez@ejemplo.com', 'Ana Martínez Reyes', '+52 33 2345 6789',
    'FinTech Labs', 'Product Manager',
    'B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7',
    'registered', 'Vegetariana'
),
(
    @tenant_id, @event_id,
    'luis.hernandez@ejemplo.com', 'Luis Hernández Vega', '+52 81 3456 7890',
    'UNAM Facultad de Ingeniería', 'Estudiante de Doctorado',
    'C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8',
    'registered', NULL
);

-- Guardar IDs de asistentes
SET @att1 = (SELECT id FROM attendees WHERE check_in_code = 'A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6');
SET @att2 = (SELECT id FROM attendees WHERE check_in_code = 'B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7');
SET @att3 = (SELECT id FROM attendees WHERE check_in_code = 'C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8');

-- -----------------------------------------------------------------------------
-- 6. Agenda personal de asistentes
-- -----------------------------------------------------------------------------
INSERT INTO `attendee_sessions` (`attendee_id`, `session_id`, `attendance_status`)
VALUES
    (@att1, @session1_id, 'pending'),
    (@att1, @session2_id, 'pending'),
    (@att2, @session1_id, 'pending'),
    (@att3, @session1_id, 'pending'),
    (@att3, @session2_id, 'pending');

-- -----------------------------------------------------------------------------
-- 7. Sponsor demo
-- -----------------------------------------------------------------------------
INSERT INTO `sponsors` (`event_id`, `name`, `website`, `description`, `tier`, `sort_order`)
VALUES
    (@event_id, 'CloudMX', 'https://cloudmx.ejemplo.com', 'Proveedor líder de soluciones cloud en México', 'platinum', 1),
    (@event_id, 'TechConf Patrocinador Gold', 'https://gold.ejemplo.com', NULL, 'gold', 2);

-- Verificación final
SELECT 'Datos de prueba insertados correctamente.' AS mensaje;
SELECT COUNT(*) AS tenants FROM tenants;
SELECT COUNT(*) AS users FROM users;
SELECT COUNT(*) AS events FROM events;
SELECT COUNT(*) AS sessions FROM event_sessions;
SELECT COUNT(*) AS attendees FROM attendees;
