<!-- Agenda Index -->
<div class="page-header">
    <div>
        <h2>🗓️ Agenda — <?= e($event['name']) ?></h2>
        <p class="text-muted"><?= formatDate($event['start_date']) ?> → <?= formatDate($event['end_date']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>" class="btn btn-ghost btn-sm">← Evento</a>
        <a href="/admin/events/<?= $event['id'] ?>/agenda/create" class="btn btn-primary">+ Nueva Sesión</a>
    </div>
</div>

<?php if (empty($sessions)): ?>
    <div class="card">
        <div class="empty-state" style="padding:56px;">
            <div class="empty-icon">🗓️</div>
            <h3 style="margin-bottom:8px;">Agenda vacía</h3>
            <p>Añade charlas, talleres y keynotes a la agenda de tu evento.</p>
            <a href="/admin/events/<?= $event['id'] ?>/agenda/create" class="btn btn-primary mt-3">Crear primera sesión</a>
        </div>
    </div>
<?php else: ?>

<?php
// Agrupar sesiones por día
$grouped = [];
foreach ($sessions as $s) {
    $day = substr($s['start_time'], 0, 10);
    $grouped[$day][] = $s;
}
ksort($grouped);

// Iconos por tipo de sesión
$typeIcons = [
    'keynote'    => '⭐',
    'workshop'   => '🔧',
    'panel'      => '🗣️',
    'networking' => '🤝',
    'talk'       => '💬',
    'break'      => '☕',
];
?>

<?php foreach ($grouped as $day => $daySessions): ?>
<div class="agenda-day-header">
    <?= formatDate($day) ?> · <?= count($daySessions) ?> sesion<?= count($daySessions) !== 1 ? 'es' : '' ?>
</div>

<div class="card" style="margin-bottom:16px;">
    <table class="table">
        <thead>
            <tr>
                <th style="width:100px;">Horario</th>
                <th>Sesión</th>
                <th>Tipo</th>
                <th>Sala</th>
                <th>Capacidad</th>
                <th>Estado</th>
                <th style="width:120px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($daySessions as $s): ?>
            <tr>
                <td>
                    <div style="font-weight:700; color:var(--primary); font-size:14px;">
                        <?= substr($s['start_time'], 11, 5) ?>
                    </div>
                    <div style="font-size:11px; color:var(--text-muted);">
                        <?= substr($s['end_time'], 11, 5) ?>
                    </div>
                </td>
                <td>
                    <div class="fw-500"><?= e($s['title']) ?></div>
                    <?php if ($s['speaker_name']): ?>
                        <div style="font-size:12px; color:var(--text-muted);">
                            🎤 <?= e($s['speaker_name']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($s['description']): ?>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            <?= e(truncate($s['description'], 80)) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size:16px;"><?= $typeIcons[$s['type']] ?? '📌' ?></span>
                    <span style="font-size:12px; color:var(--text-secondary); display:block; text-transform:capitalize;">
                        <?= e($s['type']) ?>
                    </span>
                </td>
                <td class="text-small"><?= $s['room'] ? e($s['room']) : '—' ?></td>
                <td class="text-small">
                    <?php
                    $cnt = \App\Models\EventSession::countAttendees((int)$s['id']);
                    $cap = $s['max_attendees'];
                    ?>
                    <span style="font-weight:600;"><?= $cnt ?></span>
                    <?= $cap ? "/ {$cap}" : '/ ∞' ?>
                    <?php if ($cap && $cnt >= $cap): ?>
                        <span style="font-size:10px; color:var(--danger); display:block;">Lleno</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $s['status'] === 'scheduled' ? 'registered' : e($s['status']) ?>">
                        <?= e($s['status']) ?>
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="/admin/events/<?= $event['id'] ?>/agenda/<?= $s['id'] ?>"
                           class="btn btn-ghost btn-xs" title="Ver Asistentes">👁️</a>
                        <a href="/admin/events/<?= $event['id'] ?>/agenda/<?= $s['id'] ?>/edit"
                           class="btn btn-ghost btn-xs" title="Editar">✏️</a>
                        <form method="POST" action="/admin/events/<?= $event['id'] ?>/agenda/<?= $s['id'] ?>"
                              onsubmit="return confirm('¿Eliminar esta sesión?');" style="display:inline;">
                            <?= csrfField() ?>
                            <?= methodField('DELETE') ?>
                            <button type="submit" class="btn btn-ghost btn-xs" title="Eliminar" style="color:var(--danger);">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php endif; ?>

<style>
.agenda-day-header {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-muted);
    padding: 8px 0 6px;
    border-bottom: 2px solid var(--primary);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
