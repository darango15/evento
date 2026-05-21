<!-- Dashboard -->
<div class="dashboard">

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-card--primary">
            <div class="stat-icon">📅</div>
            <div class="stat-body">
                <div class="stat-value"><?= (int)($stats['total_events'] ?? 0) ?></div>
                <div class="stat-label">Total Eventos</div>
            </div>
            <div class="stat-badge"><?= (int)($stats['published_events'] ?? 0) ?> publicados</div>
        </div>

        <div class="stat-card stat-card--green">
            <div class="stat-icon">👥</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((int)($stats['total_attendees'] ?? 0)) ?></div>
                <div class="stat-label">Total Participantes</div>
            </div>
        </div>

        <div class="stat-card stat-card--violet">
            <div class="stat-icon">✅</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((int)($stats['checked_in'] ?? 0)) ?></div>
                <div class="stat-label">Check-ins Realizados</div>
            </div>
            <?php
                $total  = (int)($stats['total_attendees'] ?? 0);
                $cin    = (int)($stats['checked_in'] ?? 0);
                $pct    = $total > 0 ? round($cin / $total * 100) : 0;
            ?>
            <div class="stat-badge"><?= $pct ?>% asistencia</div>
        </div>

        <div class="stat-card stat-card--orange">
            <div class="stat-icon">🗓️</div>
            <div class="stat-body">
                <div class="stat-value"><?= (int)($stats['total_sessions'] ?? 0) ?></div>
                <div class="stat-label">Sesiones en Agenda</div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="dashboard-grid">

        <!-- Upcoming Events -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📅 Próximos Eventos</h2>
                <a href="/admin/events/create" class="btn btn-primary btn-sm">+ Nuevo</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingEvents)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>No hay eventos próximos.</p>
                        <a href="/admin/events/create" class="btn btn-primary btn-sm">Crear primer evento</a>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Asistentes</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingEvents as $ev): ?>
                            <tr>
                                <td class="fw-500"><?= e($ev['name']) ?></td>
                                <td><?= formatDate($ev['start_date']) ?></td>
                                <td>
                                    <span class="badge badge-gray"><?= (int)$ev['attendee_count'] ?></span>
                                    <?php if ($ev['max_capacity']): ?>
                                        / <?= (int)$ev['max_capacity'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $ev['status'] ?>"><?= e($ev['status']) ?></span></td>
                                <td>
                                    <a href="/admin/events/<?= $ev['id'] ?>" class="btn btn-ghost btn-xs">Ver →</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Check-ins -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">✅ Últimos Check-ins</h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentCheckins)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🚫</div>
                        <p>Sin check-ins registrados todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="checkin-list">
                        <?php foreach ($recentCheckins as $ci): ?>
                        <div class="checkin-item">
                            <div class="checkin-avatar">
                                <?= strtoupper(substr($ci['full_name'], 0, 1)) ?>
                            </div>
                            <div class="checkin-info">
                                <strong><?= e($ci['full_name']) ?></strong>
                                <span><?= e($ci['company'] ?? $ci['email']) ?></span>
                            </div>
                            <div class="checkin-time">
                                <?= $ci['checked_in_at'] ? formatDate($ci['checked_in_at'], true) : '—' ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2 class="section-title">Acciones Rápidas</h2>
        <div class="actions-grid">
            <a href="/admin/events/create" class="action-card">
                <span class="action-icon">➕</span>
                <span>Nuevo Evento</span>
            </a>
            <a href="/admin/events" class="action-card">
                <span class="action-icon">📋</span>
                <span>Ver Eventos</span>
            </a>
        </div>
    </div>
</div>
