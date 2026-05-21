<?php
use App\Models\EventSession;
use App\Models\Sponsor;

$sessions = EventSession::groupedByDay((int)$event['id']);
$sponsors = Sponsor::byEventGrouped((int)$event['id']);

$typeIcons = [
    'keynote'    => '⭐',
    'workshop'   => '🔧',
    'panel'      => '🗣️',
    'networking' => '🤝',
    'talk'       => '💬',
    'break'      => '☕',
];

$isFull  = $event['max_capacity'] && (int)($stats['total_attendees'] ?? 0) >= (int)$event['max_capacity'];
$isOpen  = $event['status'] === 'published' && !$isFull;
?>

<!-- Hero del evento -->
<section class="pub-event-hero">
    <div class="container">
        <div class="pub-event-meta-top">
            <a href="/" class="pub-back">← Todos los eventos</a>
            <span class="pub-status-badge">
                <?= $event['is_virtual'] ? '🌐 Virtual' : '📍 Presencial' ?>
            </span>
        </div>
        <h1 class="pub-event-title"><?= e($event['name']) ?></h1>
        <div class="pub-event-meta">
            <span>📅 <?= formatDate($event['start_date']) ?><?= $event['start_date'] !== $event['end_date'] ? ' → ' . formatDate($event['end_date']) : '' ?></span>
            <?php if ($event['venue_name']): ?>
                <span>📍 <?= e($event['venue_name']) ?></span>
            <?php endif; ?>
            <?php if ((int)($stats['total_attendees'] ?? 0) > 0): ?>
                <span>👥 <?= number_format((int)$stats['total_attendees']) ?> registrados</span>
            <?php endif; ?>
        </div>

        <!-- CTA de registro -->
        <?php if ($isOpen): ?>
        <a href="/eventos/<?= e($event['slug']) ?>/registro" class="pub-cta-btn">
            📝 Regístrate gratis →
        </a>
        <?php elseif ($isFull): ?>
        <div class="pub-cta-full">🎫 Cupos agotados</div>
        <?php else: ?>
        <div class="pub-cta-full">Registro cerrado</div>
        <?php endif; ?>
    </div>
</section>

<div class="container pub-event-body">

    <div class="pub-event-grid">

        <!-- Columna principal -->
        <main>
            <!-- Descripción -->
            <?php if ($event['description']): ?>
            <section class="pub-section">
                <h2 class="pub-section-title">Acerca del evento</h2>
                <div class="pub-description">
                    <?= nl2br(e($event['description'])) ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Agenda -->
            <?php if (!empty($sessions)): ?>
            <section class="pub-section">
                <h2 class="pub-section-title">🗓️ Agenda</h2>
                <?php foreach ($sessions as $day => $daySessions): ?>
                    <div class="pub-agenda-day">📅 <?= formatDate($day) ?></div>
                    <?php foreach ($daySessions as $s): ?>
                    <div class="pub-session">
                        <div class="pub-session-time">
                            <?= substr($s['start_time'], 11, 5) ?>
                            <span>–<?= substr($s['end_time'], 11, 5) ?></span>
                        </div>
                        <div class="pub-session-body">
                            <div class="pub-session-type">
                                <?= $typeIcons[$s['type']] ?? '📌' ?> <?= ucfirst($s['type']) ?>
                                <?php if ($s['room']): ?> · <?= e($s['room']) ?><?php endif; ?>
                            </div>
                            <h3 class="pub-session-title"><?= e($s['title']) ?></h3>
                            <?php if ($s['speaker_name']): ?>
                                <p class="pub-session-speaker">🎤 <?= e($s['speaker_name']) ?></p>
                            <?php endif; ?>
                            <?php if ($s['description']): ?>
                                <p class="pub-session-desc"><?= e(truncate($s['description'], 140)) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <!-- Sponsors -->
            <?php if (!empty($sponsors)): ?>
            <section class="pub-section">
                <h2 class="pub-section-title">🏆 Patrocinadores</h2>
                <?php
                $tierLabels = ['platinum' => '🥇 Platinum', 'gold' => '🥇 Gold', 'silver' => '🥈 Silver', 'bronze' => '🥉 Bronze', 'partner' => '🤝 Partner'];
                foreach ($sponsors as $tier => $tierSponsors): ?>
                <div class="pub-sponsor-tier">
                    <span class="pub-tier-badge"><?= $tierLabels[$tier] ?? ucfirst($tier) ?></span>
                    <div class="pub-sponsor-list">
                        <?php foreach ($tierSponsors as $sp): ?>
                        <div class="pub-sponsor-item">
                            <?php if ($sp['website']): ?>
                                <a href="<?= e($sp['website']) ?>" target="_blank" rel="noopener">
                                    <?= e($sp['name']) ?>
                                </a>
                            <?php else: ?>
                                <span><?= e($sp['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        </main>

        <!-- Sidebar -->
        <aside class="pub-event-sidebar">
            <div class="pub-sidebar-card">
                <h3>Detalles del evento</h3>
                <ul class="pub-detail-list">
                    <li>
                        <span class="detail-icon">📅</span>
                        <div>
                            <strong>Fechas</strong>
                            <span><?= formatDate($event['start_date']) ?><?= $event['start_date'] !== $event['end_date'] ? ' — ' . formatDate($event['end_date']) : '' ?></span>
                        </div>
                    </li>
                    <?php if ($event['venue_name']): ?>
                    <li>
                        <span class="detail-icon">📍</span>
                        <div>
                            <strong>Sede</strong>
                            <span><?= e($event['venue_name']) ?></span>
                            <?php if ($event['venue_address']): ?>
                            <span style="font-size:12px; opacity:.7;"><?= e($event['venue_address']) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($event['max_capacity']): ?>
                    <li>
                        <span class="detail-icon">👥</span>
                        <div>
                            <strong>Capacidad</strong>
                            <span><?= number_format((int)$event['max_capacity']) ?> participantes</span>
                        </div>
                    </li>
                    <?php endif; ?>
                    <li>
                        <span class="detail-icon">🌐</span>
                        <div>
                            <strong>Formato</strong>
                            <span><?= $event['is_virtual'] ? 'Virtual / En línea' : 'Presencial' ?></span>
                        </div>
                    </li>
                </ul>

                <?php if ($isOpen): ?>
                <a href="/eventos/<?= e($event['slug']) ?>/registro" class="pub-sidebar-cta">
                    📝 Registrarse ahora →
                </a>
                <?php elseif ($isFull): ?>
                <div class="pub-sidebar-full">🎫 Sin cupos disponibles</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
