<?php
$errors  = $_SESSION['form_errors'] ?? [];
$oldData = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Decodificar settings del evento para campos personalizados
$event    = \App\Models\Event::decodeSettings($event);
$settings = $event['settings'] ?? [];
$regFields = $settings['registration_fields'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Registro') ?></title>
    <meta name="description" content="Regístrate para <?= e($event['name']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="register-body">

<!-- Header del evento -->
<header class="reg-header">
    <div class="reg-header-content">
        <a href="/eventos/<?= e($event['slug']) ?>" class="reg-back">← Ver evento</a>
        <h1 class="reg-event-name"><?= e($event['name']) ?></h1>
        <p class="reg-event-meta">
            📅 <?= formatDate($event['start_date']) ?>
            <?php if ($event['venue_name']): ?> · 📍 <?= e($event['venue_name']) ?><?php endif; ?>
        </p>
    </div>
</header>

<main class="reg-main">
    <div class="reg-card">
        <div class="reg-card-header">
            <h2>📝 Registro de Participante</h2>
            <p>Completa el formulario para reservar tu lugar.</p>
        </div>

        <!-- Flash / Errors -->
        <?php foreach ($errors as $field => $err): ?>
            <div class="alert alert-error mb-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form id="register-form" method="POST"
              action="/eventos/<?= e($event['slug']) ?>/registro"
              novalidate>
            <?= csrfField() ?>

            <div class="reg-form-grid">
                <!-- Nombre -->
                <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>" style="grid-column: 1 / -1;">
                    <label class="form-label" for="full_name">Nombre completo *</label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($oldData['full_name'] ?? '') ?>"
                           placeholder="Juan García López" required autocomplete="name">
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="form-error"><?= e($errors['full_name']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label class="form-label" for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email"
                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= e($oldData['email'] ?? '') ?>"
                           placeholder="tu@correo.com" required autocomplete="email">
                    <?php if (isset($errors['email'])): ?>
                        <span class="form-error"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Teléfono -->
                <div class="form-group">
                    <label class="form-label" for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone"
                           class="form-control"
                           value="<?= e($oldData['phone'] ?? '') ?>"
                           placeholder="+52 55 1234 5678" autocomplete="tel">
                </div>

                <!-- Empresa (si aplica) -->
                <?php if (empty($regFields) || in_array('company', $regFields)): ?>
                <div class="form-group">
                    <label class="form-label" for="company">Empresa / Organización</label>
                    <input type="text" id="company" name="company"
                           class="form-control"
                           value="<?= e($oldData['company'] ?? '') ?>"
                           placeholder="Tu empresa" autocomplete="organization">
                </div>

                <!-- Cargo -->
                <div class="form-group">
                    <label class="form-label" for="position">Cargo / Posición</label>
                    <input type="text" id="position" name="position"
                           class="form-control"
                           value="<?= e($oldData['position'] ?? '') ?>"
                           placeholder="CEO, Developer, Estudiante...">
                </div>
                <?php endif; ?>

                <!-- Restricciones alimentarias -->
                <?php if (empty($regFields) || in_array('dietary_restrictions', $regFields)): ?>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="dietary_restrictions">
                        Restricciones alimentarias / Necesidades especiales
                    </label>
                    <input type="text" id="dietary_restrictions" name="dietary_restrictions"
                           class="form-control"
                           value="<?= e($oldData['dietary_restrictions'] ?? '') ?>"
                           placeholder="Vegetariano, alergias, silla de ruedas...">
                </div>
                <?php endif; ?>
            </div>

            <div class="reg-privacy">
                <p>Al registrarte, aceptas que tus datos sean utilizados para la gestión de este evento.
                No compartiremos tu información con terceros.</p>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-primary btn-full" style="padding:14px; font-size:16px; margin-top:8px;">
                ✅ Confirmar Registro
            </button>
        </form>
    </div>
</main>

<script>
document.getElementById('register-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Procesando...';
});
</script>

<style>
.register-body { min-height:100vh; background:linear-gradient(135deg,#0F172A 0%,#1E1B4B 100%); font-family:'Inter',sans-serif; }
.reg-header { padding:24px 0; border-bottom:1px solid rgba(255,255,255,.08); margin-bottom:32px; }
.reg-header-content { max-width:640px; margin:0 auto; padding:0 20px; }
.reg-back { color:#6366F1; font-size:13px; text-decoration:none; display:inline-block; margin-bottom:12px; }
.reg-back:hover { color:#A5B4FC; }
.reg-event-name { font-size:26px; font-weight:800; color:#F1F5F9; margin-bottom:6px; }
.reg-event-meta { color:#64748B; font-size:14px; }
.reg-main { max-width:640px; margin:0 auto; padding:0 20px 48px; }
.reg-card { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); border-radius:20px; padding:32px; backdrop-filter:blur(20px); }
.reg-card-header { margin-bottom:24px; }
.reg-card-header h2 { font-size:20px; font-weight:700; color:#F1F5F9; margin-bottom:4px; }
.reg-card-header p { color:#64748B; font-size:14px; }
.reg-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:560px) { .reg-form-grid { grid-template-columns:1fr; } }
.reg-form-grid .form-label { color:#CBD5E1; }
.reg-form-grid .form-control { background:rgba(255,255,255,.07); border-color:rgba(255,255,255,.12); color:#F1F5F9; }
.reg-form-grid .form-control:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99,102,241,.2); }
.reg-form-grid .form-control::placeholder { color:#475569; }
.reg-form-grid .form-control.is-invalid { border-color:#EF4444; }
.reg-privacy { font-size:12px; color:#475569; margin:12px 0; padding:12px; background:rgba(255,255,255,.03); border-radius:8px; }
.alert { border-radius:8px; padding:10px 14px; font-size:13px; font-weight:500; }
.alert-error { background:rgba(239,68,68,.15); color:#FCA5A5; border:1px solid rgba(239,68,68,.3); }
.mb-2 { margin-bottom:8px; }
</style>
</body>
</html>
