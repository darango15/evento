<?php /** @var array $event @var array $sessions */ ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($pageTitle ?? 'Asistencia por Sesión') ?></h1>
        <p class="page-subtitle"><?= e($event['name']) ?></p>
    </div>
    <div class="header-actions">
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/sessions" class="btn btn-primary">
            ⬇️ Descargar CSV
        </a>
        <a href="/admin/events/<?= $event['id'] ?>/reports" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<?php if (empty($sessions)): ?>
<div class="card">
    <div class="card-body text-center" style="padding: 3rem;">
        <p class="empty-text">No hay sesiones con datos de asistencia aún.</p>
    </div>
</div>
<?php else: ?>

<!-- Resumen agregado -->
<?php
$totalEnrolled = array_sum(array_column($sessions, 'enrolled'));
$totalAttended = array_sum(array_column($sessions, 'attended'));
$avgPct        = count($sessions) > 0 ? round(array_sum(array_column($sessions, 'attendance_pct')) / count($sessions), 1) : 0;
?>
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-value"><?= count($sessions) ?></div>
        <div class="stat-label">Sesiones con datos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalEnrolled) ?></div>
        <div class="stat-label">Total Inscripciones</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $avgPct ?>%</div>
        <div class="stat-label">Asistencia Promedio</div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Sesión / Speaker</th>
                    <th>Tipo</th>
                    <th>Sala</th>
                    <th class="text-center">Cupo</th>
                    <th class="text-center">Inscritos</th>
                    <th class="text-center">Asistieron</th>
                    <th style="width:160px;">% Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <?php
                $pct = (float)$s['attendance_pct'];
                $barColor = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                ?>
                <tr>
                    <td style="white-space:nowrap; font-size:0.85rem;">
                        <?= e($s['start_time'] ?? '—') ?>
                    </td>
                    <td>
                        <div style="font-weight:600;"><?= e($s['title']) ?></div>
                        <?php if (!empty($s['speaker_name'])): ?>
                        <div style="font-size:0.8rem; color:#6b7280;"><?= e($s['speaker_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-blue"><?= e($s['type']) ?></span>
                    </td>
                    <td><?= e($s['room'] ?: '—') ?></td>
                    <td class="text-center"><?= $s['max_capacity'] ? (int)$s['max_capacity'] : '∞' ?></td>
                    <td class="text-center"><?= (int)$s['enrolled'] ?></td>
                    <td class="text-center"><?= (int)$s['attended'] ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <div style="background:#e5e7eb; border-radius:999px; height:8px; flex:1;">
                                <div style="background:<?= $barColor ?>; border-radius:999px; height:8px; width:<?= min(100, $pct) ?>%;"></div>
                            </div>
                            <span style="font-size:0.85rem; font-weight:600; min-width:40px;"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
