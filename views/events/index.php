<!-- Events Index -->
<div class="page-header">
    <h2>📅 Mis Eventos</h2>
    <a href="/admin/events/create" class="btn btn-primary">+ Nuevo Evento</a>
</div>

<!-- Filtros -->
<div class="card mb-4" style="padding: 16px 20px;">
    <form method="GET" action="/admin/events" class="d-flex gap-2 align-center flex-wrap">
        <?php $statuses = ['', 'draft', 'published', 'cancelled', 'completed']; ?>
        <?php $labels   = ['' => 'Todos', 'draft' => 'Borrador', 'published' => 'Publicado', 'cancelled' => 'Cancelado', 'completed' => 'Completado']; ?>
        <?php foreach ($statuses as $s): ?>
        <a href="/admin/events<?= $s ? '?status=' . $s : '' ?>"
           class="btn btn-sm <?= ($filter ?? '') === $s ? 'btn-primary' : 'btn-ghost' ?>">
            <?= $labels[$s] ?>
        </a>
        <?php endforeach; ?>
    </form>
</div>

<!-- Events List -->
<?php if (empty($events)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3 style="margin-bottom:8px;">Sin eventos todavía</h3>
            <p>Crea tu primer evento para comenzar a gestionar participantes.</p>
            <a href="/admin/events/create" class="btn btn-primary mt-3">Crear primer evento</a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="overflow:visible;">
        <table class="table">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Fechas</th>
                    <th>Sede</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <div class="fw-500"><?= e($ev['name']) ?></div>
                        <div class="text-muted text-small">/<?= e($ev['slug']) ?></div>
                    </td>
                    <td>
                        <div><?= formatDate($ev['start_date']) ?></div>
                        <?php if ($ev['end_date'] !== $ev['start_date']): ?>
                            <div class="text-muted text-small">→ <?= formatDate($ev['end_date']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-small"><?= e($ev['venue_name'] ?? ($ev['is_virtual'] ? '🌐 Virtual' : '—')) ?></td>
                    <td><span class="badge badge-<?= e($ev['status']) ?>"><?= e($ev['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="/admin/events/<?= $ev['id'] ?>" class="btn btn-ghost btn-xs">Ver</a>
                            <a href="/admin/events/<?= $ev['id'] ?>/edit" class="btn btn-ghost btn-xs">✏️</a>
                            <a href="/admin/events/<?= $ev['id'] ?>/attendees" class="btn btn-ghost btn-xs">👥</a>
                            <a href="/admin/events/<?= $ev['id'] ?>/checkin" class="btn btn-ghost btn-xs">✅</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
