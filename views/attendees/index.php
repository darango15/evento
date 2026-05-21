<!-- Attendees Index -->
<div class="page-header">
    <div>
        <h2>👥 Participantes — <?= e($event['name']) ?></h2>
        <p class="text-muted"><?= formatDate($event['start_date']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>" class="btn btn-ghost btn-sm">← Evento</a>
        <a href="/admin/events/<?= $event['id'] ?>/checkin" class="btn btn-success btn-sm">✅ Check-in</a>
    </div>
</div>

<!-- Resumen de asistencia -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
    <div class="stat-card stat-card--primary">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['total'] ?? 0) ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="stat-card stat-card--primary" style="--primary:#3B82F6;">
        <div class="stat-icon">⏳</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['registered'] ?? 0) ?></div>
            <div class="stat-label">Registrados</div>
        </div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['checked_in'] ?? 0) ?></div>
            <div class="stat-label">Check-in</div>
        </div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-icon">❌</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['cancelled'] ?? 0) ?></div>
            <div class="stat-label">Cancelados</div>
        </div>
    </div>
</div>

<!-- Filtros y búsqueda -->
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" action="/admin/events/<?= $event['id'] ?>/attendees"
          style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <input type="text" name="q" value="<?= e($search ?? '') ?>"
                   class="form-control" placeholder="🔍 Buscar por nombre, email, empresa...">
        </div>
        <div style="display:flex; gap:6px;">
            <?php $statuses = ['' => 'Todos', 'registered' => 'Registrado', 'checked_in' => 'Check-in', 'cancelled' => 'Cancelado', 'no_show' => 'No asistió']; ?>
            <?php foreach ($statuses as $s => $label): ?>
            <a href="?status=<?= $s ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               class="btn btn-sm <?= ($filter ?? '') === $s ? 'btn-primary' : 'btn-ghost' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
    </form>
</div>

<!-- Tabla de asistentes -->
<?php if (empty($attendees)): ?>
    <div class="card">
        <div class="empty-state" style="padding:56px;">
            <div class="empty-icon">👤</div>
            <h3 style="margin-bottom:8px;">Sin participantes</h3>
            <p>
                <?= $search || $filter
                    ? 'No se encontraron participantes con ese filtro.'
                    : 'Aún no hay participantes registrados para este evento.' ?>
            </p>
            <?php if ($event['status'] === 'published'): ?>
            <a href="/eventos/<?= e($event['slug']) ?>/registro" target="_blank" class="btn btn-primary mt-3">
                Ver formulario de registro
            </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
<div class="card" style="overflow:visible;">
    <table class="table">
        <thead>
            <tr>
                <th>Participante</th>
                <th>Empresa / Cargo</th>
                <th>Registro</th>
                <th>Estado</th>
                <th>Check-in</th>
                <th style="width:100px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attendees as $att): ?>
            <tr>
                <td>
                    <div class="d-flex align-center gap-2">
                        <div style="width:34px;height:34px;background:linear-gradient(135deg,#6366F1,#8B5CF6);border-radius:50%;display:grid;place-items:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">
                            <?= strtoupper(substr($att['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-500"><?= e($att['full_name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= e($att['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td class="text-small">
                    <?php if ($att['company']): ?>
                        <div class="fw-500"><?= e($att['company']) ?></div>
                    <?php endif; ?>
                    <?php if ($att['position']): ?>
                        <div style="color:var(--text-muted);"><?= e($att['position']) ?></div>
                    <?php endif; ?>
                    <?php if (!$att['company'] && !$att['position']): ?>—<?php endif; ?>
                </td>
                <td class="text-small"><?= formatDate($att['registration_date'], true) ?></td>
                <td>
                    <span class="badge badge-<?= e($att['status']) ?>">
                        <?= e($att['status']) ?>
                    </span>
                </td>
                <td class="text-small">
                    <?= $att['checked_in_at'] ? formatDate($att['checked_in_at'], true) : '—' ?>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="/admin/attendees/<?= $att['id'] ?>"
                           class="btn btn-ghost btn-xs" title="Ver detalle">👁</a>

                        <a href="/registro/ticket/<?= e($att['check_in_code']) ?>" target="_blank"
                           class="btn btn-ghost btn-xs" title="Ver ticket">🎫</a>

                        <?php if ($att['status'] !== 'cancelled'): ?>
                        <form method="POST"
                              action="/admin/attendees/<?= $att['id'] ?>"
                              onsubmit="return confirm('¿Cancelar el registro de <?= e($att['full_name']) ?>?');"
                              style="display:inline;">
                            <?= csrfField() ?>
                            <?= methodField('DELETE') ?>
                            <button type="submit" class="btn btn-ghost btn-xs"
                                    style="color:var(--warning);" title="Cancelar">🚫</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if (($pages ?? 1) > 1): ?>
    <div style="display:flex; justify-content:center; gap:8px; padding:16px 20px; border-top:1px solid var(--border);">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?><?= $filter ? '&status='.$filter : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<p class="text-muted text-small mt-2">
    Mostrando <?= count($attendees) ?> de <?= $total ?> participantes.
</p>
<?php endif; ?>
