<?php
/** @var array $event */
/** @var array $stats */
use App\Models\EventSession;
use App\Models\Sponsor;

$sessions = EventSession::byEvent((int)$event['id']);
$grouped  = EventSession::groupedByDay((int)$event['id']);
$sponsors = Sponsor::byEventGrouped((int)$event['id']);
$event    = \App\Models\Event::decodeSettings($event);
?>

<div class="page-header">
    <div>
        <h2><?= e($event['name']) ?></h2>
        <p class="text-muted">
            <span class="badge badge-<?= e($event['status']) ?>"><?= e($event['status']) ?></span>
            &nbsp;<?= formatDate($event['start_date']) ?>
            <?php if ($event['start_date'] !== $event['end_date']): ?>
                → <?= formatDate($event['end_date']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>/edit" class="btn btn-ghost">✏️ Editar</a>
        <a href="/eventos/<?= e($event['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">🌐 Ver público</a>
    </div>
</div>

<!-- ── Estadísticas ──────────────────────────────────────────────────────── -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
    <div class="stat-card stat-card--primary">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['total_attendees'] ?? 0) ?></div>
            <div class="stat-label">Participantes</div>
        </div>
        <?php if ($event['max_capacity']): ?>
        <div class="stat-badge"><?= (int)$event['max_capacity'] ?> cap.</div>
        <?php endif; ?>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['checked_in'] ?? 0) ?></div>
            <div class="stat-label">Check-ins</div>
        </div>
    </div>
    <div class="stat-card stat-card--violet">
        <div class="stat-icon">🗓️</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['total_sessions'] ?? 0) ?></div>
            <div class="stat-label">Sesiones</div>
        </div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-icon">🏆</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['total_sponsors'] ?? 0) ?></div>
            <div class="stat-label">Sponsors</div>
        </div>
    </div>
</div>

<!-- ── Acciones rápidas ──────────────────────────────────────────────────── -->
<div class="quick-actions mb-4">
    <div class="actions-grid">
        <a href="/admin/events/<?= $event['id'] ?>/attendees" class="action-card">
            <span class="action-icon">👥</span><span>Participantes</span>
        </a>
        <a href="/admin/events/<?= $event['id'] ?>/agenda" class="action-card">
            <span class="action-icon">🗓️</span><span>Agenda</span>
        </a>
        <a href="/admin/events/<?= $event['id'] ?>/checkin" class="action-card">
            <span class="action-icon">✅</span><span>Check-in</span>
        </a>
        <a href="/admin/events/<?= $event['id'] ?>/sponsors" class="action-card">
            <span class="action-icon">🏆</span><span>Sponsors</span>
        </a>
        <a href="/eventos/<?= e($event['slug']) ?>/registro" target="_blank" class="action-card">
            <span class="action-icon">📝</span><span>Formulario</span>
        </a>
    </div>
</div>

<!-- ── Contenido en dos columnas ─────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 3fr 2fr; gap: 20px;">

    <!-- Agenda Preview -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🗓️ Agenda</h3>
            <a href="/admin/events/<?= $event['id'] ?>/agenda/create" class="btn btn-primary btn-sm">+ Sesión</a>
        </div>
        <?php if (empty($sessions)): ?>
            <div class="empty-state" style="padding:32px;">
                <div class="empty-icon">📭</div>
                <p>Aún no hay sesiones en la agenda.</p>
                <a href="/admin/events/<?= $event['id'] ?>/agenda/create" class="btn btn-primary btn-sm">Crear primera sesión</a>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $day => $daySessions): ?>
            <div style="padding: 12px 20px 4px; background: var(--bg-body); border-bottom: 1px solid var(--border);">
                <strong style="font-size:12px; color: var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">
                    <?= formatDate($day) ?>
                </strong>
            </div>
            <?php foreach ($daySessions as $s): ?>
            <div style="display:flex; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border); align-items:flex-start;">
                <div style="text-align:right; min-width:80px; flex-shrink:0;">
                    <div style="font-size:13px; font-weight:600; color:var(--primary);">
                        <?= substr($s['start_time'], 11, 5) ?>
                    </div>
                    <div style="font-size:11px; color:var(--text-muted);">
                        <?= substr($s['end_time'], 11, 5) ?>
                    </div>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600; font-size:14px;"><?= e($s['title']) ?></div>
                    <?php if ($s['speaker_name']): ?>
                        <div style="font-size:12px; color:var(--text-muted);">🎤 <?= e($s['speaker_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($s['room']): ?>
                        <div style="font-size:12px; color:var(--text-muted);">📍 <?= e($s['room']) ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge badge-<?= $s['status'] === 'scheduled' ? 'registered' : e($s['status']) ?>" style="font-size:10px;">
                    <?= e($s['type']) ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Side Info -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Datos del evento -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ℹ️ Información</h3>
            </div>
            <div class="card-body">
                <dl style="display:grid; grid-template-columns: auto 1fr; gap:8px 16px; font-size:13px;">
                    <dt style="color:var(--text-muted); font-weight:600;">Slug</dt>
                    <dd style="font-family:monospace; color:var(--primary); font-size:12px;"><?= e($event['slug']) ?></dd>

                    <dt style="color:var(--text-muted); font-weight:600;">Inicio</dt>
                    <dd><?= formatDate($event['start_date']) ?></dd>

                    <dt style="color:var(--text-muted); font-weight:600;">Fin</dt>
                    <dd><?= formatDate($event['end_date']) ?></dd>

                    <dt style="color:var(--text-muted); font-weight:600;">Zona</dt>
                    <dd><?= e($event['timezone']) ?></dd>

                    <?php if ($event['venue_name']): ?>
                    <dt style="color:var(--text-muted); font-weight:600;">Venue</dt>
                    <dd><?= e($event['venue_name']) ?></dd>
                    <?php endif; ?>

                    <?php if ($event['max_capacity']): ?>
                    <dt style="color:var(--text-muted); font-weight:600;">Capacidad</dt>
                    <dd><?= number_format((int)$event['max_capacity']) ?> personas</dd>
                    <?php endif; ?>

                    <dt style="color:var(--text-muted); font-weight:600;">Formato</dt>
                    <dd><?= $event['is_virtual'] ? '🌐 Virtual' : '📍 Presencial' ?></dd>
                </dl>

                <?php if ($event['description']): ?>
                <hr style="border:none; border-top:1px solid var(--border); margin:12px 0;">
                <p style="font-size:13px; color:var(--text-secondary); line-height:1.6;">
                    <?= e(truncate($event['description'], 200)) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Links de acceso -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">🔗 Links</h3></div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:8px;">
                <a href="/eventos/<?= e($event['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">
                    🌐 Página pública
                </a>
                <a href="/eventos/<?= e($event['slug']) ?>/registro" target="_blank" class="btn btn-ghost btn-sm">
                    📝 Formulario de registro
                </a>
                <a href="/admin/events/<?= $event['id'] ?>/attendees" class="btn btn-ghost btn-sm">
                    👥 Ver participantes
                </a>
                <a href="/admin/events/<?= $event['id'] ?>/checkin" class="btn btn-success btn-sm">
                    ✅ Abrir Check-in
                </a>
            </div>
        </div>

        <!-- Sponsors -->
        <?php if (!empty($sponsors)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏆 Sponsors</h3>
                <a href="/admin/events/<?= $event['id'] ?>/sponsors" class="btn btn-ghost btn-xs">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php foreach ($sponsors as $tier => $tierSponsors): ?>
                <?php foreach ($tierSponsors as $sp): ?>
                <div style="display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid var(--border);">
                    <span class="badge badge-gray" style="text-transform:capitalize;"><?= e($tier) ?></span>
                    <span style="font-size:13px; font-weight:500;"><?= e($sp['name']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
