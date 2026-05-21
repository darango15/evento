# EventoSaaS — Sistema Multi-Tenant de Gestión de Eventos

> Sistema SaaS completo para gestionar eventos, agenda, participantes y check-in QR.
> Multi-tenant por subdominios, PHP 8.1+ puro, sin frameworks.

---

## 🚀 Características

| Módulo | Descripción |
|--------|-------------|
| Multi-tenancy | Aislamiento por subdominio (`demo.evento.test`) |
| Eventos | CRUD completo con slugs únicos por tenant |
| Agenda | Sesiones/charlas con horarios y speakers |
| Registro | Formulario público con generación de QR |
| Check-in | Escáner QR + manual — Strategy Pattern |
| Dashboard | Estadísticas en tiempo real |
| Patrocinadores | Gestión por tiers (Platinum → Partner) |
| Seguridad | CSRF, Argon2id, prepared statements, session fixation protection |

---

## 📋 Requisitos

| Requisito | Versión mínima |
|-----------|---------------|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0+ / 10.5+ |
| Apache / Nginx | mod_rewrite activo |
| Extensiones PHP | pdo, pdo_mysql, json, mbstring, openssl, gd |
| Composer | 2.x |

---

## 📁 Estructura de Archivos

```
evento/
├── public/               ← Document root del servidor web
│   ├── index.php         ← Front controller
│   ├── install.php       ← Instalador (eliminar tras instalar)
│   ├── .htaccess
│   └── assets/
│       ├── css/admin.css
│       ├── css/app.css
│       └── uploads/qrcodes/
├── app/
│   ├── Core/             ← Database, Router, Controller, Model, Middlewares
│   ├── Controllers/      ← Auth, Event, Agenda, Registration, Checkin, Dashboard
│   ├── Models/           ← Tenant, User, Event, EventSession, Attendee, Checkin, Sponsor
│   ├── Services/         ← TenantContext, QRGenerator, CheckinStrategy/*
│   ├── Helpers/          ← functions.php (helpers globales)
│   └── Config/
├── views/
│   ├── layouts/          ← admin.php, public.php, auth.php
│   ├── errors/           ← 404.php, 403.php, 500.php
│   ├── auth/             ← login.php
│   ├── dashboard/        ← index.php
│   ├── events/           ← index.php, create.php, edit.php, show.php
│   ├── agenda/           ← index.php, session_form.php
│   ├── attendees/        ← index.php, register.php, confirmation.php, ticket.php
│   └── checkin/          ← scanner.php, list.php
├── database/
│   ├── schema.sql        ← Schema completo con índices
│   └── seeds/demo_data.sql
├── logs/
├── .env                  ← Configuración (generar desde .env.example)
├── .env.example
└── composer.json
```

---

## ⚡ Instalación Paso a Paso

### Opción A — Instalador Web (Recomendado)

1. **Clonar / copiar** el proyecto en tu directorio web:
   ```bash
   # XAMPP en Windows
   cp -r evento/ C:/laragon/www/evento/

   # o en Linux
   cp -r evento/ /var/www/html/evento/
   ```

2. **Instalar dependencias** con Composer:
   ```bash
   cd /ruta/a/evento
   composer install
   ```

3. **Configurar el virtual host** en Apache/Laragon:
   ```apache
   # Agrega a httpd-vhosts.conf o crea en /etc/apache2/sites-available/
   <VirtualHost *:80>
       ServerName evento.test
       ServerAlias *.evento.test
       DocumentRoot "C:/laragon/www/evento/public"
       <Directory "C:/laragon/www/evento/public">
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

4. **Agregar al archivo hosts** (`C:\Windows\System32\drivers\etc\hosts` en Windows o `/etc/hosts` en Linux):
   ```
   127.0.0.1   evento.test
   127.0.0.1   demo.evento.test
   ```

5. **Ejecutar el instalador** en el navegador:
   ```
   http://evento.test/install.php
   ```
   El instalador:
   - Verifica los requisitos del sistema
   - Crea la base de datos y las tablas
   - Genera el archivo `.env`
   - Crea el usuario superadmin

6. **¡Listo!** Accede al panel:
   ```
   http://demo.evento.test/login
   ```

---

### Opción B — Instalación Manual

1. **Instalar Composer**:
   ```bash
   composer install
   ```

2. **Copiar y editar `.env`**:
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales
   ```

3. **Crear la base de datos**:
   ```sql
   -- En MySQL / phpMyAdmin
   CREATE DATABASE evento_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Ejecutar el schema**:
   ```bash
   mysql -u root -p evento_saas < database/schema.sql
   ```

5. **Cargar datos de prueba** (opcional):
   ```bash
   mysql -u root -p evento_saas < database/seeds/demo_data.sql
   ```

6. **Crear directorios con permisos de escritura**:
   ```bash
   mkdir -p logs
   mkdir -p public/assets/uploads/qrcodes
   mkdir -p public/assets/uploads/logos
   chmod 775 logs public/assets/uploads public/assets/uploads/qrcodes
   ```

7. **Crear usuario superadmin manualmente**:
   ```sql
   INSERT INTO users (tenant_id, email, password, name, role, is_active)
   VALUES (
       NULL,
       'admin@evento.test',
       -- Generar con: php -r "echo password_hash('TuPassword123!', PASSWORD_ARGON2ID);"
       '$argon2id$v=19$...',
       'Super Admin',
       'superadmin',
       1
   );
   ```

---

## 🔧 Configuración del `.env`

```env
# Aplicación
APP_NAME="EventoSaaS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://evento.test
APP_KEY=tu_clave_aleatoria_de_32_chars

# Multi-tenancy
TENANT_BASE_DOMAIN=evento.test

# Base de datos
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=evento_saas
DB_USER=root
DB_PASS=

# Sesión
SESSION_NAME=evento_session
SESSION_LIFETIME=7200

# Correo
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

# Timezone
APP_TIMEZONE=America/Mexico_City
```

---

## 👤 Credenciales de Demo

Con los datos de prueba cargados (`demo_data.sql`):

| Usuario | Email | Contraseña | Rol |
|---------|-------|-----------|-----|
| Super Admin | superadmin@evento.test | Admin1234! | superadmin |
| Carlos Ramírez | admin@techconfmx.com | Admin1234! | owner |
| María González | staff@techconfmx.com | Admin1234! | staff |

Acceso al panel del tenant demo:
```
http://demo.evento.test/login
```

---

## 🗺️ Rutas de la Aplicación

### Públicas
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/` | Lista de eventos del tenant |
| GET | `/eventos/{slug}` | Detalle del evento |
| GET | `/eventos/{slug}/registro` | Formulario de registro |
| POST | `/eventos/{slug}/registro` | Procesar registro |
| GET | `/registro/confirmacion/{code}` | Confirmación + QR |
| GET | `/registro/ticket/{code}` | Ticket imprimible |

### Admin (requieren login)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/admin/dashboard` | Panel principal |
| GET/POST | `/admin/events` | Lista / Crear evento |
| GET/POST | `/admin/events/{id}` | Ver / Actualizar evento |
| DELETE | `/admin/events/{id}` | Cancelar evento |
| GET/POST | `/admin/events/{id}/agenda` | Agenda del evento |
| GET | `/admin/events/{id}/attendees` | Lista de participantes |
| GET/POST | `/admin/events/{id}/checkin` | Escáner de check-in |
| POST | `/checkin/manual` | Check-in por email (AJAX) |

---

## 🏗️ Arquitectura y Patrones

```
Petición HTTP
    ↓
public/index.php (Front Controller)
    ↓
Router → Middleware → Controller → Model → Database
                                      ↓
                                   View (layout + partial)
```

| Patrón | Implementación |
|--------|---------------|
| **Singleton** | `Database`, `TenantContext` |
| **Strategy** | `CheckinContext` + `QRCheckinStrategy` + `ManualCheckinStrategy` |
| **Repository** | `BaseRepository`, `EventRepository` |
| **Front Controller** | `public/index.php` |
| **MVC** | Controllers → Models → Views |
| **Middleware** | `AuthMiddleware`, `TenantMiddleware` |

---

## 🔐 Seguridad

- ✅ **CSRF** en todos los formularios con tokens de sesión
- ✅ **XSS** — función `e()` en todas las salidas HTML
- ✅ **SQL Injection** — solo prepared statements
- ✅ **Session Fixation** — `session_regenerate_id()` en login
- ✅ **Session Timeout** — expiración configurable
- ✅ **Argon2id** para contraseñas
- ✅ **Cabeceras de seguridad** en `.htaccess`
- ✅ **Sin exposición de errores** en producción (`APP_DEBUG=false`)
- ✅ **Tenant isolation** — cada query filtra por `tenant_id`

---

## 📊 Índices de Base de Datos

Columnas optimizadas con índices:

| Tabla | Columna(s) | Tipo |
|-------|-----------|------|
| tenants | subdomain | UNIQUE |
| events | tenant_id | INDEX |
| events | status | INDEX |
| events | start_date, end_date | INDEX compuesto |
| attendees | check_in_code | UNIQUE |
| attendees | event_id, email | UNIQUE compuesto |
| attendees | event_id, status | INDEX compuesto |
| attendees | email | INDEX |
| event_sessions | event_id | INDEX |
| event_sessions | start_time | INDEX |

---

## 🛠️ Desarrollo Local

```bash
# Servidor de desarrollo PHP incorporado (sin Apache)
php -S localhost:8000 -t public/

# Acceso: http://localhost:8000
```

> **Nota:** Para multi-tenancy con subdominios, se necesita Apache/Nginx con virtual hosts configurados.

---

## 📝 Notas de Actualización

Ver el archivo [CHANGELOG.md](CHANGELOG.md) para historial de cambios.

---

## 📄 Licencia

MIT License — EventoSaaS Team © 2025
