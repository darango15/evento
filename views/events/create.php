<?php
// Recuperar errores y datos previos si el formulario falló
$errors  = $_SESSION['form_errors'] ?? [];
$oldData = $_SESSION['form_data']   ?? $event ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$isEdit   = isset($event) && !empty($event['id']);
$formAction = $isEdit ? "/admin/events/{$event['id']}" : '/admin/events';
?>

<div class="page-header">
    <div>
        <h2><?= $isEdit ? '✏️ Editar Evento' : '➕ Nuevo Evento' ?></h2>
        <?php if ($isEdit): ?>
            <a href="/admin/events/<?= $event['id'] ?>" class="text-muted text-small">← Volver al evento</a>
        <?php else: ?>
            <a href="/admin/events" class="text-muted text-small">← Volver a eventos</a>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= $formAction ?>" id="event-form" novalidate>
    <?= csrfField() ?>
    <?php if ($isEdit): ?><?= methodField('PUT') ?><?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">

        <!-- Main Column -->
        <div style="display: flex; flex-direction: column; gap: 20px;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">Información básica</h3></div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">

                    <div class="form-group">
                        <label class="form-label">Nombre del evento *</label>
                        <input type="text" name="name" id="event-name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldData['name'] ?? '') ?>" placeholder="Ej: Tech Summit México 2025" required
                               oninput="autoSlug(this.value)">
                        <?php if (isset($errors['name'])): ?>
                            <span class="form-error"><?= e($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug (URL) *</label>
                        <input type="text" name="slug" id="event-slug" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldData['slug'] ?? '') ?>" placeholder="tech-summit-2025" pattern="[a-z0-9\-]+" style="font-family:monospace;">
                        <?php if (isset($errors['slug'])): ?>
                            <span class="form-error"><?= e($errors['slug']) ?></span>
                        <?php else: ?>
                            <span class="form-hint">Solo letras minúsculas, números y guiones. Se auto-genera del nombre.</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe el evento..."><?= e($oldData['description'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">📍 Lugar y Formato</h3></div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_virtual" id="is-virtual" value="1"
                                   <?= !empty($oldData['is_virtual']) ? 'checked' : '' ?>
                                   onchange="toggleVirtual(this.checked)"
                                   style="width:auto;margin-right:8px;">
                            Evento virtual / en línea
                        </label>
                    </div>
                    <div id="venue-fields">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Nombre del venue</label>
                                <input type="text" name="venue_name" class="form-control" value="<?= e($oldData['venue_name'] ?? '') ?>" placeholder="Centro de Convenciones WTC">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Capacidad máxima</label>
                                <input type="number" name="max_capacity" class="form-control <?= isset($errors['max_capacity']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($oldData['max_capacity'] ?? '') ?>" placeholder="500" min="1">
                                <?php if (isset($errors['max_capacity'])): ?>
                                    <span class="form-error"><?= e($errors['max_capacity']) ?></span>
                                <?php else: ?>
                                    <span class="form-hint">Dejar vacío para sin límite</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="venue_address" class="form-control" value="<?= e($oldData['venue_address'] ?? '') ?>" placeholder="Calle, colonia, ciudad">
                        </div>
                    </div>
                    <div id="virtual-field" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Enlace virtual (Zoom, Meet, etc.)</label>
                            <input type="url" name="virtual_link" class="form-control" value="<?= e($oldData['virtual_link'] ?? '') ?>" placeholder="https://meet.google.com/...">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Side Column -->
        <div style="display: flex; flex-direction: column; gap: 20px;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">📆 Fechas</h3></div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Fecha inicio *</label>
                        <input type="date" name="start_date" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldData['start_date'] ?? '') ?>" required>
                        <?php if (isset($errors['start_date'])): ?>
                            <span class="form-error"><?= e($errors['start_date']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha fin *</label>
                        <input type="date" name="end_date" class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldData['end_date'] ?? '') ?>" required>
                        <?php if (isset($errors['end_date'])): ?>
                            <span class="form-error"><?= e($errors['end_date']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zona horaria</label>
                        <select name="timezone" class="form-control">
                            <?php foreach ($timezones as $tz => $label): ?>
                            <option value="<?= e($tz) ?>" <?= ($oldData['timezone'] ?? 'America/Mexico_City') === $tz ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">⚙️ Publicación</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="status" class="form-control" required>
                            <option value="draft"     <?= ($oldData['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>📝 Borrador</option>
                            <option value="published" <?= ($oldData['status'] ?? '') === 'published' ? 'selected' : '' ?>>✅ Publicado</option>
                            <option value="cancelled" <?= ($oldData['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>❌ Cancelado</option>
                            <option value="completed" <?= ($oldData['status'] ?? '') === 'completed' ? 'selected' : '' ?>>🏁 Completado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2" style="flex-direction: column;">
                <button type="submit" class="btn btn-primary btn-full">
                    <?= $isEdit ? '💾 Guardar cambios' : '➕ Crear evento' ?>
                </button>
                <a href="/admin/events" class="btn btn-ghost btn-full">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<script>
function autoSlug(name) {
    const slug = name
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim().replace(/^-+|-+$/g, '');

    const slugField = document.getElementById('event-slug');
    if (!slugField.dataset.userEdited) {
        slugField.value = slug;
    }
}

document.getElementById('event-slug').addEventListener('input', function() {
    this.dataset.userEdited = 'true';
});

function toggleVirtual(isVirtual) {
    document.getElementById('venue-fields').style.display = isVirtual ? 'none' : 'block';
    document.getElementById('virtual-field').style.display = isVirtual ? 'block' : 'none';
}

// Inicializar estado del virtual checkbox
if (document.getElementById('is-virtual').checked) {
    toggleVirtual(true);
}
</script>
