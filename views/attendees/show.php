<!-- Attendee Detail -->
<div class="page-header">
    <div>
        <h2>👤 <?= e($attendee['full_name']) ?></h2>
        <p class="text-muted">
            <span class="badge badge-<?= e($attendee['status']) ?>"><?= e($attendee['status']) ?></span>
            &nbsp;Registrado el <?= formatDate($attendee['registration_date'], true) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>/attendees" class="btn btn-ghost btn-sm">← Volver</a>
        <?php if (!empty($attendee['qr_code_path'])): ?>
        <a href="/registro/ticket/<?= e($attendee['check_in_code']) ?>" target="_blank" class="btn btn-primary btn-sm">
            🎫 Ver ticket
        </a>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">

    <!-- Info principal -->
    <div style="display:flex; flex-direction:column; gap:16px;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">📋 Datos del participante</h3></div>
            <div class="card-body">
                <dl style="display:grid; grid-template-columns:140px 1fr; gap:12px 20px; font-size:14px;">
                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Nombre</dt>
                    <dd class="fw-500"><?= e($attendee['full_name']) ?></dd>

                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Email</dt>
                    <dd><a href="mailto:<?= e($attendee['email']) ?>"><?= e($attendee['email']) ?></a></dd>

                    <?php if ($attendee['phone']): ?>
                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Teléfono</dt>
                    <dd><?= e($attendee['phone']) ?></dd>
                    <?php endif; ?>

                    <?php if ($attendee['company']): ?>
                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Empresa</dt>
                    <dd><?= e($attendee['company']) ?></dd>
                    <?php endif; ?>

                    <?php if ($attendee['position']): ?>
                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Cargo</dt>
                    <dd><?= e($attendee['position']) ?></dd>
                    <?php endif; ?>

                    <?php if ($attendee['dietary_restrictions']): ?>
                    <dt style="color:var(--text-muted); font-weight:600; align-self:center;">Alimentación</dt>
                    <dd><?= e($attendee['dietary_restrictions']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if (!empty($attendee['special_needs'])): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">♿ Necesidades especiales</h3></div>
            <div class="card-body">
                <p style="font-size:14px; color:var(--text-secondary);"><?= e($attendee['special_needs']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Agenda Personal -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">🗓️ Agenda Personal</h3>
                <span class="badge badge-ghost"><?= count($agenda) ?> sesiones</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($agenda)): ?>
                    <div style="padding:40px; text-align:center; color:var(--text-muted);">
                        No hay sesiones agendadas para este participante.
                    </div>
                <?php else: ?>
                    <table class="table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="padding-left:20px;">Sesión</th>
                                <th>Horario</th>
                                <th>Sala</th>
                                <th>Check-in</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agenda as $session): ?>
                            <tr>
                                <td style="padding-left:20px;">
                                    <div class="fw-600"><?= e($session['title']) ?></div>
                                    <div style="font-size:11px; color:var(--text-muted);"><?= e($session['type']) ?></div>
                                </td>
                                <td>
                                    <div style="font-size:13px; font-weight:500;">
                                        <?= substr($session['start_time'], 11, 5) ?> - <?= substr($session['end_time'], 11, 5) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted);">
                                        <?= formatDate($session['start_time']) ?>
                                    </div>
                                </td>
                                <td><span class="text-small"><?= $session['room'] ? e($session['room']) : '—' ?></span></td>
                                <td>
                                    <?php if ($session['checkin_at']): ?>
                                        <span class="badge badge-success">✅ Presente</span>
                                        <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">
                                            <?= formatDate($session['checkin_at'], true) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-ghost">⏳ Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel QR y acciones -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <div class="card">
            <div class="card-header"><h3 class="card-title">🔲 Código QR</h3></div>
            <div class="card-body" style="text-align:center;">
                <?php
                    if (!empty($attendee['qr_code_path']) && file_exists(PUBLIC_PATH . $attendee['qr_code_path'])) {
                        $qrImgSrc = asset(ltrim($attendee['qr_code_path'], '/assets/'));
                    } else {
                        $targetUrl = url("/registro/ticket/" . $attendee['check_in_code']);
                        $qrImgSrc  = (new \App\Services\QRGenerator())->generateBase64($targetUrl, '#6366F1');
                    }
                ?>
                <div style="display:inline-block; background:white; border:3px solid var(--border); border-radius:12px; padding:12px; margin-bottom:10px;">
                    <img src="<?= $qrImgSrc ?>"
                         alt="QR Code" width="150" height="150"
                         style="display:block; image-rendering:pixelated;">
                </div>
                <div style="font-family:monospace; font-size:11px; color:var(--primary); background:var(--bg-body); padding:6px 10px; border-radius:6px; word-break:break-all;">
                    <?= e($attendee['check_in_code']) ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">ℹ️ Estado</h3></div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:10px;">
                <div>
                    <span style="font-size:11px; color:var(--text-muted); font-weight:600;">Estado actual</span>
                    <div class="mt-1">
                        <span class="badge badge-<?= e($attendee['status']) ?>" style="font-size:13px; padding:6px 14px;">
                            <?= e($attendee['status']) ?>
                        </span>
                    </div>
                </div>
                <?php if ($attendee['checked_in_at']): ?>
                <div>
                    <span style="font-size:11px; color:var(--text-muted); font-weight:600;">Check-in realizado</span>
                    <div style="font-size:13px; font-weight:500; margin-top:4px; color:var(--success);">
                        ✅ <?= formatDate($attendee['checked_in_at'], true) ?>
                    </div>
                </div>
                <?php endif; ?>

                <hr style="border:none; border-top:1px solid var(--border); margin:4px 0;">

                <?php if ($attendee['status'] !== 'cancelled'): ?>
                <form method="POST"
                      action="/admin/events/<?= $event['id'] ?>/attendees/<?= $attendee['id'] ?>/cancel"
                      onsubmit="return confirm('¿Cancelar el registro de este participante?');">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-warning btn-full btn-sm">🚫 Cancelar registro</button>
                </form>
                <?php endif; ?>

                <form method="POST"
                      action="/admin/events/<?= $event['id'] ?>/attendees/<?= $attendee['id'] ?>"
                      onsubmit="return confirm('¿Eliminar permanentemente? Esta acción no se puede deshacer.');">
                    <?= csrfField() ?>
                    <?= methodField('DELETE') ?>
                    <button type="submit" class="btn btn-danger btn-full btn-sm">🗑 Eliminar participante</button>
                </form>
            </div>
        </div>
    </div>
</div>
