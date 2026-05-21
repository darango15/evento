<!-- Session Detail -->
<div class="page-header">
    <div>
        <h2><?= $session['type'] === 'workshop' ? '🔧' : '💬' ?> <?= e($session['title']) ?></h2>
        <p class="text-muted">
            <?= formatDate($session['start_time'], true) ?> — <?= substr($session['end_time'], 11, 5) ?> 
            · Sala: <?= $session['room'] ? e($session['room']) : '—' ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>/agenda" class="btn btn-ghost btn-sm">← Volver a Agenda</a>
        <a href="/admin/events/<?= $event['id'] ?>/agenda/<?= $session['id'] ?>/edit" class="btn btn-primary btn-sm">✏️ Editar</a>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">
    
    <!-- Sidebar: Session Info -->
    <div style="display:flex; flex-direction:column; gap:16px;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">ℹ️ Información</h3></div>
            <div class="card-body">
                <dl style="font-size:14px; display:grid; grid-template-columns:100px 1fr; gap:10px;">
                    <dt style="color:var(--text-muted); font-weight:600;">Tipo</dt>
                    <dd style="text-transform:capitalize;"><?= e($session['type']) ?></dd>
                    
                    <dt style="color:var(--text-muted); font-weight:600;">Ponente</dt>
                    <dd><?= $session['speaker_name'] ? e($session['speaker_name']) : '—' ?></dd>

                    <dt style="color:var(--text-muted); font-weight:600;">Estado</dt>
                    <dd><span class="badge badge-<?= e($session['status']) ?>"><?= e($session['status']) ?></span></dd>

                    <dt style="color:var(--text-muted); font-weight:600;">Capacidad</dt>
                    <dd><?= $session['max_attendees'] ?: '∞' ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($session['description']): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">📝 Descripción</h3></div>
            <div class="card-body">
                <p style="font-size:14px; white-space:pre-wrap;"><?= e($session['description']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Main: Attendees List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">👥 Participantes Inscritos</h3>
            <span class="badge badge-primary"><?= count($attendees) ?> inscritos</span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($attendees)): ?>
                <div style="padding:48px; text-align:center; color:var(--text-muted);">
                    Todavía no hay participantes inscritos en esta charla.
                </div>
            <?php else: ?>
                <table class="table" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="padding-left:20px;">Nombre</th>
                            <th>Email / Empresa</th>
                            <th>Check-in</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendees as $a): ?>
                        <tr>
                            <td style="padding-left:20px;">
                                <a href="/admin/attendees/<?= $a['id'] ?>" class="fw-600"><?= e($a['full_name']) ?></a>
                            </td>
                            <td>
                                <div style="font-size:12px;"><?= e($a['email']) ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?= e($a['company'] ?: '—') ?></div>
                            </td>
                            <td>
                                <?php if ($a['checkin_at']): ?>
                                    <span class="badge badge-success">✅ Presente</span>
                                    <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">
                                        <?= formatDate($a['checkin_at'], true) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-ghost">⏳ Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/attendees/<?= $a['id'] ?>" class="btn btn-ghost btn-xs">👁️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
