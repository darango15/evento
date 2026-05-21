<!-- Check-in List / Audit Log -->
<div class="page-header">
    <div>
        <h2>📋 Registro de Check-ins — <?= e($event['name']) ?></h2>
        <p class="text-muted">Historial completo de entradas al evento</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/events/<?= $event['id'] ?>/checkin" class="btn btn-success btn-sm">✅ Ir al escáner</a>
        <a href="/admin/events/<?= $event['id'] ?>" class="btn btn-ghost btn-sm">← Evento</a>
    </div>
</div>

<!-- Resumen -->
<div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:20px;">
    <div class="stat-card stat-card--green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['checked_in'] ?? 0) ?></div>
            <div class="stat-label">Check-ins realizados</div>
        </div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-icon">⏳</div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($summary['registered'] ?? 0) ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="stat-card stat-card--orange">
        <div class="stat-icon">📊</div>
        <div class="stat-body">
            <?php
            $total = (int)($summary['total'] ?? 0);
            $cin   = (int)($summary['checked_in'] ?? 0);
            $pct   = $total > 0 ? round($cin / $total * 100) : 0;
            ?>
            <div class="stat-value"><?= $pct ?>%</div>
            <div class="stat-label">% de asistencia</div>
        </div>
    </div>
</div>

<!-- Tabla de check-ins -->
<?php if (empty($checkins)): ?>
    <div class="card">
        <div class="empty-state" style="padding:56px;">
            <div class="empty-icon">📟</div>
            <h3 style="margin-bottom:8px;">Sin check-ins registrados</h3>
            <p>Los check-ins aparecerán aquí en tiempo real.</p>
            <a href="/admin/events/<?= $event['id'] ?>/checkin" class="btn btn-success mt-3">
                ✅ Abrir escáner de check-in
            </a>
        </div>
    </div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Participante</th>
                <th>Empresa</th>
                <th>Método</th>
                <th>Sesión</th>
                <th>Registrado por</th>
                <th>Fecha y hora</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checkins as $i => $ci): ?>
            <tr>
                <td class="text-muted text-small"><?= count($checkins) - $i ?></td>
                <td>
                    <div class="d-flex align-center gap-2">
                        <div style="width:30px;height:30px;background:linear-gradient(135deg,#6366F1,#8B5CF6);border-radius:50%;display:grid;place-items:center;color:white;font-weight:700;font-size:11px;flex-shrink:0;">
                            <?= strtoupper(substr($ci['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-500" style="font-size:13px;"><?= e($ci['full_name']) ?></div>
                            <div style="font-size:11px; color:var(--text-muted);"><?= e($ci['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td class="text-small"><?= $ci['company'] ? e($ci['company']) : '—' ?></td>
                <td>
                    <?php
                    $methodIcons = ['qr_code' => '📷', 'manual' => '✍️', 'kiosk' => '🖥️', 'mobile' => '📱'];
                    $methodLabels = ['qr_code' => 'QR', 'manual' => 'Manual', 'kiosk' => 'Kiosko', 'mobile' => 'Móvil'];
                    ?>
                    <span title="<?= e($ci['checkin_method']) ?>">
                        <?= $methodIcons[$ci['checkin_method']] ?? '❓' ?>
                        <span class="text-small"><?= $methodLabels[$ci['checkin_method']] ?? e($ci['checkin_method']) ?></span>
                    </span>
                </td>
                <td class="text-small">
                    <?= $ci['session_title'] ? e(truncate($ci['session_title'], 30)) : '<span style="color:var(--text-muted)">General</span>' ?>
                </td>
                <td class="text-small"><?= $ci['checked_by_name'] ? e($ci['checked_by_name']) : '—' ?></td>
                <td class="text-small" style="white-space:nowrap;">
                    <?= formatDate($ci['checked_in_at'], true) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="text-muted text-small mt-2"><?= count($checkins) ?> check-in<?= count($checkins) !== 1 ? 's' : '' ?> en total.</p>
<?php endif; ?>
