<?php
/** @var array $event */
/** @var array[] $attendees */
/** @var int $total */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets — <?= e($event['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Inter', sans-serif;
        background: #0f172a;
        color: white;
        padding: 24px;
    }

    /* ── Barra superior ─────────────────────────────────── */
    .controls {
        max-width: 1120px;
        margin: 0 auto 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .controls h2 { font-size: 20px; font-weight: 800; }
    .controls p  { font-size: 14px; color: #94a3b8; margin-top: 4px; }

    .btn-back {
        color: #94a3b8; font-size: 14px; text-decoration: none;
        padding: 10px 16px; border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .btn-print {
        background: #4f46e5; color: white;
        border: none; padding: 12px 28px;
        border-radius: 10px; font-size: 15px;
        font-weight: 700; cursor: pointer;
        font-family: inherit;
    }

    /* ── Grid: 2 columnas, misma altura por fila ────────── */
    .ticket-grid {
        max-width: 1120px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(2, 9cm);
        gap: 0.45cm;
        justify-content: center;
        align-items: stretch;  /* iguala altura entre los dos de cada fila */
    }

    /* ── Ticket ──────────────────────────────────────────── */
    .ticket {
        width: 9cm;
        /* sin altura fija — el grid iguala la fila al ticket más alto */
        background: white;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }

    /* ── Header ──────────────────────────────────────────── */
    .ticket-header {
        background: linear-gradient(135deg, #0098d4 0%, #00ADEF 60%, #00c5ff 100%);
        padding: 10px 12px 12px;
        color: white;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }
    .ticket-header::after {
        content: '';
        position: absolute;
        bottom: -28px; right: -28px;
        width: 110px; height: 110px;
        border: 24px solid rgba(255,255,255,0.07);
        border-radius: 50%;
    }
    .ticket-brand {
        font-size: 6.5px;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        opacity: 0.65;
        font-weight: 700;
        margin-bottom: 4px;
        position: relative; z-index: 1;
    }
    .ticket-event-label {
        font-size: 8px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 3px;
        position: relative; z-index: 1;
    }
    .ticket-date {
        font-size: 7.5px;
        opacity: 0.85;
        font-weight: 600;
        position: relative; z-index: 1;
    }

    /* ── Perforación ─────────────────────────────────────── */
    .ticket-perforation {
        border-top: 1.5px dashed #d1d5db;
        position: relative;
        flex-shrink: 0;
    }
    .ticket-perforation::before,
    .ticket-perforation::after {
        content: '';
        position: absolute;
        width: 13px; height: 13px;
        background: #0f172a;
        border-radius: 50%;
        top: -6.5px;
    }
    .ticket-perforation::before { left: -6.5px; }
    .ticket-perforation::after  { right: -6.5px; }

    /* ── Body: 2 columnas ────────────────────────────────── */
    .ticket-body {
        flex: 1;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        padding: 8px 12px 8px;
        background: white;
        align-items: start;
        min-height: 0;
    }

    /* ── Footer ──────────────────────────────────────────── */
    .ticket-footer {
        background: #f8fafc;
        padding: 6px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px dashed #e2e8f0;
        flex-shrink: 0;
    }
    .ticket-footer-note {
        font-size: 6.5px;
        color: #94a3b8;
        font-style: italic;
    }
    .ticket-footer-id {
        font-size: 7.5px;
        font-weight: 700;
        color: #475569;
        font-variant-numeric: tabular-nums;
    }

    /* Columna izquierda: nombre + badge + datos */
    .ticket-left {
        display: flex;
        flex-direction: column;
        gap: 5px;
        overflow: hidden;
        min-width: 0;
    }

    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 7px 2px 5px;
        border-radius: 999px;
        font-size: 6.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        background: #d0f5f2;
        color: #027a6e;
        width: fit-content;
        flex-shrink: 0;
    }
    .type-badge-dot {
        width: 4px; height: 4px;
        border-radius: 50%;
        background: #02b6a5;
        flex-shrink: 0;
    }

    /* Nombre del asistente — en el body como el ticket individual */
    .attendee-name {
        font-size: 15px;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.15;
        word-break: break-word;
    }

    .detail-row {
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
    }
    .detail-icon {
        width: 20px; height: 20px;
        background: #f1f5f9;
        border-radius: 4px;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
    }
    .detail-text {
        font-size: 8px;
        color: #1e293b;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Columna derecha: QR flotante + código */
    .ticket-right {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        margin-top: -55px;   /* flota hacia el header */
        position: relative;
        z-index: 5;
    }

    .qr-box {
        background: white;
        padding: 5px;
        border-radius: 12px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.22), 0 2px 8px rgba(0,0,0,0.08);
        line-height: 0;
    }
    .qr-box img {
        display: block;
        width: 120px;
        height: 120px;
        border-radius: 6px;
    }

    .qr-code-text {
        font-family: 'Courier New', monospace;
        font-size: 6.5px;
        font-weight: 800;
        color: #027a6e;
        background: #d0f5f2;
        padding: 2px 7px;
        border-radius: 4px;
        letter-spacing: 0.04em;
        text-align: center;
        white-space: nowrap;
    }

    /* ── Print ──────────────────────────────────────────── */
    @media print {
        @page { size: 8.5in 11in; margin: 0.35in 0.45in; }

        body { background: white; padding: 0; }

        .controls { display: none !important; }

        .ticket-grid {
            max-width: none;
            margin: 0;
            gap: 0.35cm;
        }

        .ticket {
            box-shadow: none;
            border: 1px solid #cbd5e1;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .ticket-header {
            background: linear-gradient(135deg, #0098d4, #00ADEF) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ticket-perforation::before,
        .ticket-perforation::after {
            background: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .type-badge {
            background: #d0f5f2 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .qr-code-text {
            background: #d0f5f2 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .detail-icon {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ticket-footer {
            background: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .qr-box img { width: 120px; height: 120px; }
    }
    </style>
</head>
<body>

<div class="controls">
    <div>
        <h2>🖨️ Tickets — <?= e($event['name']) ?></h2>
        <p><?= (int)$total ?> participantes · 2 por fila</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="/admin/events/<?= (int)$event['id'] ?>/attendees" class="btn-back">← Volver</a>
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Exportar PDF</button>
    </div>
</div>

<div class="ticket-grid">
    <?php
    $tipoLabels = [
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

    foreach ($attendees as $att):
        $rawTipo   = $att['participant_type'] ?? '';
        $typeLabel = $tipoLabels[$rawTipo]
            ?? (!empty($rawTipo) ? ucwords(str_replace('_', ' ', $rawTipo)) : 'Asistente');
        $qrSrc = '/registro/qr/' . urlencode($att['check_in_code']);
    ?>
    <div class="ticket">

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-brand">Ticket de Acceso</div>
            <div class="ticket-event-label"><?= e($event['name']) ?></div>
            <div class="ticket-date">📅 <?= formatDate($event['start_date']) ?></div>
        </div>
        <div class="ticket-perforation"></div>

        <!-- Body: nombre + datos (izq) | QR flotante (der) -->
        <div class="ticket-body">
            <div class="ticket-left">
                <span class="type-badge">
                    <span class="type-badge-dot"></span>
                    <?= e($typeLabel) ?>
                </span>
                <div class="attendee-name"><?= e($att['full_name']) ?></div>
                <?php if (!empty($att['id_document'])): ?>
                <div class="detail-row">
                    <div class="detail-icon">🪪</div>
                    <div class="detail-text"><?= e($att['id_document']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="ticket-right">
                <div class="qr-box">
                    <img src="<?= e($qrSrc) ?>"
                         alt="QR <?= e($att['check_in_code']) ?>">
                </div>
                <div class="qr-code-text"><?= e($att['check_in_code']) ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <span class="ticket-footer-note">Este ticket es personal e intransferible.</span>
            <span class="ticket-footer-id">ID #<?= (int)($att['id'] ?? 0) ?></span>
        </div>

    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
