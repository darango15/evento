<!-- Sponsors Manager -->
<div class="page-header">
    <div>
        <h2>🏆 Patrocinadores — <?= e($event['name']) ?></h2>
    </div>
    <a href="/admin/events/<?= $event['id'] ?>" class="btn btn-ghost btn-sm">← Evento</a>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">

    <!-- Lista de sponsors por tier -->
    <div>
        <?php
        $tierLabels = [
            'platinum' => ['label' => '🥇 Platinum', 'color' => '#7C3AED'],
            'gold'     => ['label' => '🥇 Gold',     'color' => '#D97706'],
            'silver'   => ['label' => '🥈 Silver',   'color' => '#6B7280'],
            'bronze'   => ['label' => '🥉 Bronze',   'color' => '#92400E'],
            'partner'  => ['label' => '🤝 Partner',  'color' => '#0369A1'],
        ];
        ?>

        <?php if (empty($sponsors)): ?>
        <div class="card">
            <div class="empty-state" style="padding:48px;">
                <div class="empty-icon">🏆</div>
                <h3 style="margin-bottom:8px;">Sin patrocinadores</h3>
                <p>Agrega los patrocinadores de tu evento usando el formulario.</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($sponsors as $tier => $tierSponsors): ?>
        <div style="margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; padding:8px 0; border-bottom:2px solid; border-color:<?= $tierLabels[$tier]['color'] ?? '#6366F1' ?>;">
                <span style="font-weight:700; font-size:14px; color:<?= $tierLabels[$tier]['color'] ?? '#6366F1' ?>;">
                    <?= $tierLabels[$tier]['label'] ?? ucfirst($tier) ?>
                </span>
                <span class="text-muted text-small">(<?= count($tierSponsors) ?>)</span>
            </div>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Sitio web</th>
                            <th>Contacto</th>
                            <th>Orden</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tierSponsors as $sp): ?>
                        <tr>
                            <td class="fw-500"><?= e($sp['name']) ?></td>
                            <td>
                                <?php if ($sp['website']): ?>
                                    <a href="<?= e($sp['website']) ?>" target="_blank" class="text-small" style="color:var(--primary);">
                                        <?= e(parse_url($sp['website'], PHP_URL_HOST) ?: $sp['website']) ?>
                                    </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-small">
                                <?= $sp['contact_name'] ? e($sp['contact_name']) : '—' ?>
                                <?php if ($sp['contact_email']): ?>
                                    <br><a href="mailto:<?= e($sp['contact_email']) ?>" style="color:var(--text-muted); font-size:11px;"><?= e($sp['contact_email']) ?></a>
                                <?php endif; ?>
                            </td>
                            <td class="text-small"><?= (int)$sp['sort_order'] ?></td>
                            <td>
                                <form method="POST"
                                      action="/admin/events/<?= $event['id'] ?>/sponsors/<?= $sp['id'] ?>"
                                      onsubmit="return confirm('¿Eliminar a <?= e($sp['name']) ?>?');">
                                    <?= csrfField() ?>
                                    <?= methodField('DELETE') ?>
                                    <button type="submit" class="btn btn-ghost btn-xs" style="color:var(--danger);" title="Eliminar">🗑</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Formulario de nuevo sponsor -->
    <div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">➕ Añadir Patrocinador</h3></div>
            <div class="card-body">
                <form method="POST" action="/admin/events/<?= $event['id'] ?>/sponsors"
                      style="display:flex; flex-direction:column; gap:14px;">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control" placeholder="Empresa ABC" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nivel *</label>
                        <select name="tier" class="form-control" required>
                            <option value="platinum">🥇 Platinum</option>
                            <option value="gold">🥇 Gold</option>
                            <option value="silver">🥈 Silver</option>
                            <option value="bronze">🥉 Bronze</option>
                            <option value="partner" selected>🤝 Partner</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sitio web</label>
                        <input type="url" name="website" class="form-control" placeholder="https://empresa.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción breve</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Breve descripción..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombre de contacto</label>
                        <input type="text" name="contact_name" class="form-control" placeholder="Juan Martínez">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email de contacto</label>
                        <input type="email" name="contact_email" class="form-control" placeholder="contacto@empresa.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Orden</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        <span class="form-hint">Menor número = mayor prioridad</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">➕ Añadir patrocinador</button>
                </form>
            </div>
        </div>
    </div>
</div>
