<?php /** @var array $event @var array $attendance @var array $sessionReport @var array $checkinChart @var array $companyChart @var array $sponsors */ ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($pageTitle ?? 'Reportes') ?></h1>
        <p class="page-subtitle"><?= e($event['name']) ?></p>
    </div>
    <div class="header-actions">
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/attendees"
           class="btn btn-secondary">
            ⬇️ Exportar Asistentes CSV
        </a>
        <a href="/admin/events/<?= $event['id'] ?>/reports/export/sessions"
           class="btn btn-secondary">
            ⬇️ Exportar Sesiones CSV
        </a>
    </div>
</div>

<!-- Métricas principales -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
    <?php
    $summary = $attendance['summary'] ?? [];
    $total   = (int)($summary['total']      ?? 0);
    $checked = (int)($summary['checked_in'] ?? 0);
    $rate    = $attendance['attendance_rate'] ?? 0;
    ?>

    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;">👥</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($total) ?></div>
            <div class="stat-label">Total Registrados</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;">✅</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($checked) ?></div>
            <div class="stat-label">Asistieron (Check-in)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;">📊</div>
        <div class="stat-info">
            <div class="stat-value"><?= $rate ?>%</div>
            <div class="stat-label">Tasa de Asistencia</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fce7f3;">❌</div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($summary['no_show'] ?? 0) ?></div>
            <div class="stat-label">No Shows</div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">

    <!-- Check-ins por hora -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Check-ins por Hora</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($checkinChart['data'])): ?>
            <canvas id="checkinChart" height="250"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('checkinChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode($checkinChart['labels']) ?>,
                            datasets: [{
                                label: 'Check-ins',
                                data: <?= json_encode($checkinChart['data']) ?>,
                                backgroundColor: 'rgba(59,130,246,0.7)',
                                borderColor: 'rgba(59,130,246,1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    });
                });
            </script>
            <?php else: ?>
            <p class="empty-text">Sin datos de check-in aún.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Asistentes por empresa -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Empresas Representadas</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($companyChart['data'])): ?>
            <canvas id="companyChart" height="250"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx2 = document.getElementById('companyChart').getContext('2d');
                    const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'];
                    new Chart(ctx2, {
                        type: 'doughnut',
                        data: {
                            labels: <?= json_encode($companyChart['labels']) ?>,
                            datasets: [{
                                data: <?= json_encode($companyChart['data']) ?>,
                                backgroundColor: colors
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'right' } }
                        }
                    });
                });
            </script>
            <?php else: ?>
            <p class="empty-text">Sin datos de empresas aún.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Asistencia por sesión -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Asistencia por Sesión</h3>
        <a href="/admin/events/<?= $event['id'] ?>/reports/sessions" class="btn btn-sm btn-secondary">
            Ver completo
        </a>
    </div>
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sesión</th>
                    <th>Tipo</th>
                    <th>Sala</th>
                    <th class="text-center">Inscritos</th>
                    <th class="text-center">Asistieron</th>
                    <th class="text-center">% Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessionReport)): ?>
                <tr><td colspan="6" class="text-center text-gray">Sin sesiones registradas.</td></tr>
                <?php else: ?>
                <?php foreach (array_slice($sessionReport, 0, 10) as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><span class="badge badge-blue"><?= e($s['type']) ?></span></td>
                    <td><?= e($s['room'] ?: '—') ?></td>
                    <td class="text-center"><?= (int)$s['enrolled'] ?></td>
                    <td class="text-center"><?= (int)$s['attended'] ?></td>
                    <td class="text-center">
                        <div style="display:flex; align-items:center; gap:0.5rem; justify-content:center;">
                            <div style="background:#e5e7eb; border-radius:999px; height:8px; width:80px;">
                                <div style="background:#3b82f6; border-radius:999px; height:8px; width:<?= min(100, (float)$s['attendance_pct']) ?>%;"></div>
                            </div>
                            <span><?= $s['attendance_pct'] ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
