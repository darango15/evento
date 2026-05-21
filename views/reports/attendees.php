<?php /** @var array $event @var array $report */ ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($pageTitle ?? 'Reporte de Asistentes') ?></h1>
        <p class="page-subtitle"><?= e($event['name']) ?></p>
    </div>
    <div class="header-actions">
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/attendees"
           class="btn btn-primary">⬇️ Descargar CSV</a>
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/attendees?status=checked_in"
           class="btn btn-secondary">⬇️ Solo Asistentes</a>
        <a href="/admin/events/<?= $event['id'] ?>/reports" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<?php $s = $report['summary'] ?? []; $total = (int)($s['total'] ?? 0); ?>

<!-- Métricas de resumen -->
<div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-value"><?= number_format($total) ?></div>
        <div class="stat-label">Total Registrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#10b981;"><?= (int)($s['checked_in'] ?? 0) ?></div>
        <div class="stat-label">Con Check-in</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#3b82f6;"><?= (int)($s['registered'] ?? 0) ?></div>
        <div class="stat-label">Pendientes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#f59e0b;"><?= (int)($s['no_show'] ?? 0) ?></div>
        <div class="stat-label">No Shows</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#6366f1;"><?= $report['attendance_rate'] ?? 0 ?>%</div>
        <div class="stat-label">Tasa de Asistencia</div>
    </div>
</div>

<!-- Barra visual de estado -->
<?php if ($total > 0): ?>
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <p style="font-weight:600; margin-bottom:0.75rem;">Distribución de Estado</p>
        <div style="display:flex; border-radius:999px; overflow:hidden; height:24px; width:100%;">
            <?php
            $checkedPct  = round((int)($s['checked_in'] ?? 0) / $total * 100, 1);
            $pendPct     = round((int)($s['registered'] ?? 0) / $total * 100, 1);
            $noShowPct   = round((int)($s['no_show']    ?? 0) / $total * 100, 1);
            $cancelPct   = round((int)($s['cancelled']  ?? 0) / $total * 100, 1);
            ?>
            <?php if ($checkedPct > 0): ?><div style="width:<?= $checkedPct ?>%; background:#10b981;" title="Check-in: <?= $checkedPct ?>%"></div><?php endif; ?>
            <?php if ($pendPct > 0):    ?><div style="width:<?= $pendPct ?>%; background:#3b82f6;"   title="Pendiente: <?= $pendPct ?>%"></div><?php endif; ?>
            <?php if ($noShowPct > 0):  ?><div style="width:<?= $noShowPct ?>%; background:#f59e0b;" title="No Show: <?= $noShowPct ?>%"></div><?php endif; ?>
            <?php if ($cancelPct > 0):  ?><div style="width:<?= $cancelPct ?>%; background:#ef4444;" title="Cancelado: <?= $cancelPct ?>%"></div><?php endif; ?>
        </div>
        <div style="display:flex; gap:1.5rem; margin-top:0.75rem; font-size:0.8rem;">
            <span><span style="color:#10b981;">■</span> Check-in <?= $checkedPct ?>%</span>
            <span><span style="color:#3b82f6;">■</span> Pendiente <?= $pendPct ?>%</span>
            <span><span style="color:#f59e0b;">■</span> No Show <?= $noShowPct ?>%</span>
            <span><span style="color:#ef4444;">■</span> Cancelado <?= $cancelPct ?>%</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Enlace a lista completa -->
<div class="card">
    <div class="card-body" style="text-align:center; padding: 2rem;">
        <p style="font-size:1.1rem; color:#6b7280; margin-bottom:1.5rem;">
            Para ver la lista detallada de asistentes, usa la sección de Participantes o descarga el CSV.
        </p>
        <a href="/admin/events/<?= $event['id'] ?>/attendees" class="btn btn-primary">
            Ver Lista de Participantes
        </a>
        &nbsp;
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/attendees" class="btn btn-secondary">
            Descargar CSV Completo
        </a>
    </div>
</div>
