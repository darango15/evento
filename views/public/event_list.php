<!-- Listado público de eventos del tenant -->
<section class="public-hero">
    <div class="container">
        <h1><?= e($tenant['name']) ?></h1>
        <p class="hero-subtitle">Próximos eventos</p>
    </div>
</section>

<section class="public-events container">
    <?php if (empty($events)): ?>
        <div class="pub-empty">
            <div style="font-size:64px; margin-bottom:16px;">📭</div>
            <h2>Sin eventos disponibles</h2>
            <p>Próximamente se publicarán nuevos eventos. ¡Vuelve pronto!</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $ev): ?>
            <article class="event-card">
                <?php if (!empty($ev['cover_image'])): ?>
                    <div class="event-card-img">
                        <img src="<?= e($ev['cover_image']) ?>" alt="<?= e($ev['name']) ?>">
                    </div>
                <?php else: ?>
                    <div class="event-card-img event-card-placeholder">⚡</div>
                <?php endif; ?>

                <div class="event-card-body">
                    <div class="event-card-date">
                        📅 <?= formatDate($ev['start_date']) ?>
                        <?php if ($ev['start_date'] !== $ev['end_date']): ?>
                            → <?= formatDate($ev['end_date']) ?>
                        <?php endif; ?>
                    </div>
                    <h2 class="event-card-title"><?= e($ev['name']) ?></h2>
                    <?php if ($ev['venue_name'] && !$ev['is_virtual']): ?>
                        <p class="event-card-venue">📍 <?= e($ev['venue_name']) ?></p>
                    <?php elseif ($ev['is_virtual']): ?>
                        <p class="event-card-venue">🌐 Evento virtual</p>
                    <?php endif; ?>
                    <?php if ($ev['description']): ?>
                        <p class="event-card-desc"><?= e(truncate($ev['description'], 120)) ?></p>
                    <?php endif; ?>
                    <div class="event-card-footer">
                        <?php if ($ev['max_capacity']): ?>
                            <span class="event-badge">👥 <?= number_format((int)$ev['max_capacity']) ?> lugares</span>
                        <?php endif; ?>
                        <a href="/eventos/<?= e($ev['slug']) ?>" class="btn-pub-primary">
                            Ver evento →
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
