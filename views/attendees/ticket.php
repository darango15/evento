<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket — <?= e($attendee['event_name'] ?? 'Evento') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', sans-serif;
        background-color: #0f172a;
        background-image:
            radial-gradient(ellipse at top right, rgba(99,102,241,0.2) 0%, transparent 50%),
            radial-gradient(ellipse at bottom left, rgba(139,92,246,0.15) 0%, transparent 50%);
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
        padding: 40px 20px;
    }

    .ticket-wrap { width: 100%; max-width: 680px; }

    .print-btn {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 13px 24px;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(12px);
        color: rgba(255,255,255,0.9);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        margin-bottom: 20px;
        font-family: inherit;
        width: 100%;
        transition: all 0.2s ease;
        letter-spacing: 0.01em;
    }
    .print-btn:hover {
        background: rgba(255,255,255,0.13);
        border-color: rgba(255,255,255,0.25);
        transform: translateY(-1px);
    }

    /* ── Ticket shell ───────────────────────────────────── */
    .ticket {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow:
            0 0 0 1px rgba(99,102,241,0.15),
            0 30px 60px -10px rgba(0,0,0,0.55),
            0 0 40px rgba(99,102,241,0.08);
        position: relative;
    }

    /* ── Header ─────────────────────────────────────────── */
    .ticket-header {
        background: linear-gradient(135deg, #4338ca 0%, #6d28d9 60%, #7c3aed 100%);
        padding: 36px 40px 40px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .ticket-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 85% 20%, rgba(255,255,255,0.12) 0%, transparent 40%),
            radial-gradient(circle at 10% 80%, rgba(255,255,255,0.06) 0%, transparent 35%);
    }
    .ticket-header::after {
        content: '';
        position: absolute;
        bottom: -40px;
        right: -40px;
        width: 220px;
        height: 220px;
        border: 40px solid rgba(255,255,255,0.06);
        border-radius: 50%;
    }

    .ticket-brand {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        opacity: 0.65;
        font-weight: 700;
        margin-bottom: 14px;
        position: relative;
        z-index: 1;
    }
    .ticket-event-name {
        font-size: 28px;
        font-weight: 900;
        line-height: 1.15;
        margin-bottom: 18px;
        letter-spacing: -0.03em;
        position: relative;
        z-index: 1;
        max-width: 520px;
    }
    .ticket-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 13px;
        opacity: 0.85;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }
    .ticket-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* ── Perforación ─────────────────────────────────────── */
    .ticket-perforation {
        border-top: 2px dashed #e2e8f0;
        position: relative;
    }
    .ticket-perforation::before,
    .ticket-perforation::after {
        content: '';
        position: absolute;
        width: 36px;
        height: 36px;
        background: #0f172a;
        border-radius: 50%;
        top: -18px;
    }
    .ticket-perforation::before { left: -18px; }
    .ticket-perforation::after  { right: -18px; }

    /* ── Body ────────────────────────────────────────────── */
    .ticket-body {
        padding: 36px 40px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 32px;
        align-items: center;
        background: #ffffff;
    }

    /* Tipo de participante */
    .participant-type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }
    .participant-type-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pt-registered {
        background: #ede9fe;
        color: #5b21b6;
    }
    .pt-registered .participant-type-dot { background: #7c3aed; }
    .pt-checked_in {
        background: #d1fae5;
        color: #065f46;
    }
    .pt-checked_in .participant-type-dot { background: #059669; }

    .attendee-name {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 3px;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .attendee-email {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 28px;
        font-weight: 400;
    }

    .ticket-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px 24px;
    }
    .detail-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .detail-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }

    /* ── QR ──────────────────────────────────────────────── */
    .ticket-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        padding: 20px 18px;
        border-radius: 18px;
        border: 1px solid #e8edf4;
        min-width: 176px;
    }
    .qr-title {
        font-size: 10px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .qr-frame {
        background: white;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    .qr-frame img {
        display: block;
        width: 136px;
        height: 136px;
    }
    .qr-code-text {
        font-family: 'Courier New', monospace;
        font-size: 10.5px;
        color: #5b21b6;
        background: #ede9fe;
        padding: 5px 10px;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 0.04em;
        word-break: break-all;
        max-width: 152px;
        text-align: center;
    }

    /* ── Footer ──────────────────────────────────────────── */
    .ticket-footer {
        background: #f8fafc;
        padding: 16px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px dashed #e2e8f0;
    }
    .ticket-footer-note {
        font-size: 12px;
        color: #94a3b8;
        font-style: italic;
    }
    .ticket-footer-id {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        font-variant-numeric: tabular-nums;
    }

    /* ── Print ───────────────────────────────────────────── */
    @media print {
        body { background: white; padding: 0; min-height: auto; }
        .print-btn { display: none !important; }
        .ticket {
            box-shadow: none;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
        }
        .ticket-perforation::before,
        .ticket-perforation::after { background: white; border: 1px solid #cbd5e1; }
        .ticket-perforation::before { left: -19px; border-right: none; }
        .ticket-perforation::after  { right: -19px; border-left: none; }
        .ticket-header {
            background: linear-gradient(135deg, #4338ca, #7c3aed) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    @media (max-width: 560px) {
        .ticket-body { grid-template-columns: 1fr; gap: 28px; }
        .ticket-qr { flex-direction: row; flex-wrap: wrap; justify-content: center; min-width: unset; }
        .ticket-event-name { font-size: 22px; }
        .ticket-header, .ticket-body, .ticket-footer { padding-left: 24px; padding-right: 24px; }
    }
    </style>
</head>
<body>
<div class="ticket-wrap">

    <button onclick="window.print()" class="print-btn">🖨️ Imprimir Ticket</button>

    <div class="ticket">

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-brand">Ticket de Acceso · EventoSaaS</div>
            <div class="ticket-event-name"><?= e($attendee['event_name']) ?></div>
            <div class="ticket-meta">
                <span class="ticket-meta-item">📅 <?= formatDate($attendee['start_date']) ?></span>
                <?php if (!empty($attendee['venue_name'])): ?>
                <span class="ticket-meta-item">📍 <?= e($attendee['venue_name']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Perforación -->
        <div class="ticket-perforation"></div>

        <!-- Body -->
        <div class="ticket-body">
            <div>
                <?php
                    $isCheckedIn      = $attendee['status'] === 'checked_in';
                    $participantLabel = !empty($attendee['position'])
                        ? $attendee['position']
                        : ($isCheckedIn ? 'Ingresó' : 'Asistente');
                    $ptClass = $isCheckedIn ? 'pt-checked_in' : 'pt-registered';
                ?>
                <span class="participant-type <?= $ptClass ?>">
                    <span class="participant-type-dot"></span>
                    <?= e($participantLabel) ?>
                </span>

                <div class="attendee-name"><?= e($attendee['full_name']) ?></div>
                <div class="attendee-email"><?= e($attendee['email']) ?></div>

                <div class="ticket-details">
                    <?php if (!empty($attendee['company'])): ?>
                    <div>
                        <div class="detail-label">🏥 Institución</div>
                        <div class="detail-value"><?= e($attendee['company']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($attendee['phone'])): ?>
                    <div>
                        <div class="detail-label">📱 Teléfono</div>
                        <div class="detail-value"><?= e($attendee['phone']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="detail-label">🗓 Registro</div>
                        <div class="detail-value"><?= formatDate($attendee['registration_date'], true) ?></div>
                    </div>
                </div>
            </div>

            <!-- QR -->
            <div class="ticket-qr">
                <div class="qr-title">Código de acceso</div>
                <div class="qr-frame">
                    <?php
                        // Usar QR guardado en disco si existe, sino generar vía API
                        if (!empty($attendee['qr_code_path']) && file_exists(PUBLIC_PATH . $attendee['qr_code_path'])) {
                            $qrImgSrc = asset(ltrim($attendee['qr_code_path'], '/assets/'));
                        } else {
                            $targetUrl = url("/registro/ticket/" . $attendee['check_in_code']);
                            $qrImgSrc  = (new \App\Services\QRGenerator())->generateBase64($targetUrl, '#6d28d9');
                        }
                    ?>
                    <img src="<?= $qrImgSrc ?>"
                         alt="QR <?= e($attendee['check_in_code']) ?>"
                         width="136" height="136">
                </div>
                <div class="qr-code-text"><?= e($attendee['check_in_code']) ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <span class="ticket-footer-note">Este ticket es personal e intransferible.</span>
            <span class="ticket-footer-id">ID #<?= (int)($attendee['id'] ?? 0) ?></span>
        </div>

    </div>
</div>
</body>
</html>
