<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Registro Confirmado! — <?= e($attendee['event_name'] ?? 'EventoSaaS') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0F172A, #1E1B4B); min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #F1F5F9; }
    .confirm-card { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 24px; width: 100%; max-width: 520px; overflow: hidden; backdrop-filter: blur(20px); }
    .confirm-header { background: linear-gradient(135deg, #10B981, #059669); padding: 40px 32px; text-align: center; }
    .confirm-icon { font-size: 56px; margin-bottom: 12px; }
    .confirm-header h1 { font-size: 24px; font-weight: 800; color: white; margin-bottom: 6px; }
    .confirm-header p { color: rgba(255,255,255,.8); font-size: 14px; }
    .confirm-body { padding: 32px; }
    .info-block { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .info-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .info-row:last-child { margin-bottom: 0; }
    .info-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748B; min-width: 100px; padding-top: 2px; }
    .info-value { font-size: 14px; color: #E2E8F0; font-weight: 500; flex: 1; }
    .qr-section { text-align: center; margin-bottom: 24px; }
    .qr-section h3 { font-size: 14px; font-weight: 700; color: #94A3B8; margin-bottom: 12px; }
    .qr-img { display: inline-block; background: white; padding: 16px; border-radius: 12px; }
    .qr-img img { width: 180px; height: 180px; display: block; image-rendering: pixelated; }
    .code-display { margin-top: 10px; font-family: monospace; font-size: 13px; color: #6366F1; background: rgba(99,102,241,.1); padding: 6px 16px; border-radius: 6px; display: inline-block; letter-spacing: .05em; }
    .actions { display: flex; flex-direction: column; gap: 10px; }
    .btn-confirm { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; transition: opacity .2s; }
    .btn-print { background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white; }
    .btn-print:hover { color: white; opacity: .9; }
    .btn-home { background: rgba(255,255,255,.08); color: #CBD5E1; border: 1px solid rgba(255,255,255,.1); }
    .btn-home:hover { color: white; background: rgba(255,255,255,.12); }
    .note { font-size: 12px; color: #475569; text-align: center; margin-top: 16px; }
    </style>
</head>
<body>
<div class="confirm-card">
    <div class="confirm-header">
        <div class="confirm-icon">🎉</div>
        <h1>¡Registro Confirmado!</h1>
        <p>Tu lugar está reservado. Guarda este código para el check-in.</p>
    </div>

    <div class="confirm-body">
        <!-- Datos del asistente -->
        <div class="info-block">
            <div class="info-row">
                <span class="info-label">👤 Nombre</span>
                <span class="info-value"><?= e($attendee['full_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📧 Email</span>
                <span class="info-value"><?= e($attendee['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📅 Evento</span>
                <span class="info-value"><?= e($attendee['event_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📆 Fecha</span>
                <span class="info-value"><?= formatDate($attendee['start_date']) ?></span>
            </div>
            <?php if (!empty($attendee['venue_name'])): ?>
            <div class="info-row">
                <span class="info-label">📍 Lugar</span>
                <span class="info-value"><?= e($attendee['venue_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- QR Code -->
        <div class="qr-section">
            <h3>🔲 Tu código QR de acceso</h3>
            <?php if (!empty($attendee['qr_code_path'])): ?>
                <div class="qr-img">
                    <img src="<?= e($attendee['qr_code_path']) ?>"
                         alt="Código QR — <?= e($attendee['check_in_code']) ?>"
                         width="180" height="180">
                </div>
            <?php else: ?>
                <div class="qr-img" style="width:180px; height:180px; display:inline-flex; align-items:center; justify-content:center; flex-direction:column; gap:8px;">
                    <span style="font-size:40px;">⬛</span>
                    <span style="font-size:11px; color:#94A3B8;">QR generando...</span>
                </div>
            <?php endif; ?>
            <div>
                <span class="code-display"><?= e($attendee['check_in_code']) ?></span>
            </div>
        </div>

        <!-- Acciones -->
        <div class="actions">
            <a href="/registro/ticket/<?= e($attendee['check_in_code']) ?>" class="btn-confirm btn-print" target="_blank">
                🖨️ Imprimir ticket
            </a>
            <a href="/" class="btn-confirm btn-home">
                ← Volver al inicio
            </a>
        </div>

        <p class="note">
            Presenta este código QR en la entrada del evento para hacer check-in.
            También recibirás un correo de confirmación.
        </p>
    </div>
</div>
</body>
</html>
