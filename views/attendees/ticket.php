<?php /** @var array $attendee */ ?>
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
        background: linear-gradient(135deg, #0098d4 0%, #00ADEF 60%, #00c5ff 100%);
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
        font-size: 18px;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 10px;
        letter-spacing: -0.02em;
        position: relative;
        z-index: 1;
        max-width: 520px;
    }
    .ticket-attendee-name {
        font-size: clamp(24px, 5vw, 36px);
        font-weight: 900;
        color: white;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 14px;
        position: relative;
        z-index: 1;
        word-break: break-word;
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
        padding: 20px 28px 24px;
        background: #ffffff;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        align-items: start;
    }

    /* Tipo de participante */
    .participant-type {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px 3px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .participant-type-dot {
        width: 5px; height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pt-registered { background: #d0f5f2; color: #027a6e; }
    .pt-registered .participant-type-dot { background: #02b6a5; }
    .pt-checked_in { background: #d1fae5; color: #065f46; }
    .pt-checked_in .participant-type-dot { background: #059669; }

    .attendee-name-body {
        font-size: clamp(22px, 4vw, 32px);
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: 14px;
        word-break: break-word;
    }

    .ticket-details {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .detail-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .detail-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .detail-label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 1px;
    }
    .detail-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
    }

    /* ── QR flotante ─────────────────────────────────────── */
    .ticket-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: white;
        padding: 6px;
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
        margin-top: -130px;
        position: relative;
        z-index: 5;
    }
    .qr-frame img {
        display: block;
        width: 270px;
        height: 270px;
        border-radius: 12px;
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
        @page { size: 9cm 7cm; margin: 0; }
        body { background: white; padding: 0; min-height: auto; display: block; }
        .print-btn { display: none !important; }
        .ticket-wrap { width: 9cm; max-width: 9cm; }
        .ticket { box-shadow: none; border: 1px solid #cbd5e1; border-radius: 8px; page-break-inside: avoid; }
        .ticket-header { padding: 10px 14px 12px; }
        .ticket-brand { font-size: 8px; margin-bottom: 4px; }
        .ticket-event-name { font-size: 10px; margin-bottom: 4px; }
        .ticket-attendee-name { font-size: 16px; margin-bottom: 6px; }
        .ticket-meta { font-size: 9px; }
        .ticket-body { padding: 10px 14px; gap: 10px; }
        .participant-type { font-size: 8px; padding: 2px 8px 2px 6px; margin-bottom: 4px; }
        .attendee-email { font-size: 9px; margin-bottom: 8px; }
        .detail-label { font-size: 7px; }
        .detail-value { font-size: 11px; }
        .ticket-qr { padding: 6px; gap: 5px; }
        .qr-frame { padding: 4px; }
        .qr-frame img { width: 110px; height: 110px; }
        .qr-code-text { font-size: 7px; padding: 3px 6px; }
        .ticket-footer { padding: 6px 14px; }
        .ticket-footer-note { font-size: 8px; }
        .ticket-footer-id { font-size: 9px; }
        .ticket-perforation { display: none; }
        .ticket-header {
            background: linear-gradient(135deg, #0098d4, #00ADEF) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    @media (max-width: 600px) {
        body { padding: 0; }

        .ticket-wrap { max-width: 100%; }

        .ticket {
            border-radius: 0;
            box-shadow: none;
        }

        /* Header compacto */
        .ticket-header {
            padding: 20px 20px 70px; /* espacio abajo para el QR flotante */
        }
        .ticket-event-name { font-size: 20px; margin-bottom: 8px; }
        .ticket-brand { font-size: 10px; margin-bottom: 8px; }
        .ticket-meta { font-size: 12px; }

        /* Perforación */
        .ticket-perforation::before { left: -14px; width: 28px; height: 28px; top: -14px; }
        .ticket-perforation::after  { right: -14px; width: 28px; height: 28px; top: -14px; }

        /* Body: columna única, QR arriba centrado */
        .ticket-body {
            grid-template-columns: 1fr;
            padding: 16px 20px 20px;
            gap: 20px;
        }

        /* QR: centrado, sin float negativo */
        .ticket-body > div:last-child {
            order: -1;           /* QR va primero */
            align-items: center;
            margin-top: -60px;   /* flota sobre header */
        }

        .ticket-qr {
            margin-top: 0;
            padding: 6px;
        }

        .qr-frame img {
            width: 180px;
            height: 180px;
        }

        /* Datos: más compactos */
        .attendee-name-body { font-size: 24px; }
        .detail-icon { width: 28px; height: 28px; font-size: 13px; }
        .detail-value { font-size: 13px; }

        /* Footer */
        .ticket-footer { padding: 12px 20px; }
        .ticket-footer-note { font-size: 11px; }

        /* Botón imprimir */
        .print-btn { border-radius: 0; margin-bottom: 0; }
    }
    </style>
</head>
<body>
<div class="ticket-wrap">

    <button onclick="window.print()" class="print-btn">🖨️ Imprimir Ticket</button>

    <div class="ticket">

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-brand">Ticket de Acceso</div>
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
            <?php
                $isCheckedIn = $attendee['status'] === 'checked_in';
                $ptClass     = $isCheckedIn ? 'pt-checked_in' : 'pt-registered';
                $tipoLabels  = [
                    'socio_activo'          => 'Socio Activo',
                    'socio_emerito'         => 'Socio Emérito',
                    'no_socio'              => 'No Socio',
                    'medico_general'        => 'Médico General',
                    'residente_estudiante'  => 'Residente / Estudiante',
                    'enfermera_profesional' => 'Enfermera Profesional',
                    'junta_directiva'       => 'Junta Directiva',
                    'conferencista'         => 'Conferencista',
                    'comite_cientifico'     => 'Comité Científico',
                ];
                $rawTipo          = $attendee['participant_type'] ?? '';
                $participantLabel = $tipoLabels[$rawTipo]
                    ?? (!empty($rawTipo) ? ucwords(str_replace('_', ' ', $rawTipo)) : ($isCheckedIn ? 'Ingresó' : 'Asistente'));
            ?>

            <!-- Izquierda: nombre + badge + datos -->
            <div>
                <span class="participant-type <?= $ptClass ?>">
                    <span class="participant-type-dot"></span>
                    <?= e($participantLabel) ?>
                </span>
                <div class="attendee-name-body"><?= e($attendee['full_name']) ?></div>
                <div class="ticket-details">
                    <?php if (!empty($attendee['id_document'])): ?>
                    <div class="detail-row">
                        <div class="detail-icon">🪪</div>
                        <div>
                            <div class="detail-label">Cédula</div>
                            <div class="detail-value"><?= e($attendee['id_document']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Derecha: QR flotante + código -->
            <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                <div class="ticket-qr">
                    <?php
                        if (!empty($attendee['qr_code_path']) && file_exists(PUBLIC_PATH . $attendee['qr_code_path'])) {
                            $qrImgSrc = asset(ltrim($attendee['qr_code_path'], '/assets/'));
                        } else {
                            $targetUrl = url("/registro/ticket/" . $attendee['check_in_code']);
                            $qrImgSrc  = (new \App\Services\QRGenerator())->generateBase64($targetUrl, '#02b6a5');
                        }
                    ?>
                    <img src="<?= $qrImgSrc ?>"
                         alt="QR <?= e($attendee['check_in_code']) ?>"
                         width="270" height="270">
                </div>
                <div style="font-family:'Courier New',monospace;font-size:15px;color:#027a6e;background:#d0f5f2;padding:8px 18px;border-radius:8px;font-weight:800;letter-spacing:0.05em;text-align:center;"><?= e($attendee['check_in_code']) ?></div>
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
