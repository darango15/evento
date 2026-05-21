<?php
$errors  = $_SESSION['form_errors'] ?? [];
$oldData = $_SESSION['form_data']   ?? $session ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$isEdit     = isset($session) && !empty($session['id']);
$formAction = $isEdit
    ? "/admin/events/{$event['id']}/agenda/{$session['id']}"
    : "/admin/events/{$event['id']}/agenda";

// Tipos de sesión
$types = [
    'keynote'    => '⭐ Keynote',
    'workshop'   => '🔧 Taller',
    'panel'      => '🗣️ Panel',
    'networking' => '🤝 Networking',
    'talk'       => '💬 Charla',
    'break'      => '☕ Descanso',
];
?>

<div class="page-header">
    <div>
        <h2><?= $isEdit ? '✏️ Editar Sesión' : '➕ Nueva Sesión' ?></h2>
        <a href="/admin/events/<?= $event['id'] ?>/agenda" class="text-muted text-small">
            ← Volver a la agenda de <?= e($event['name']) ?>
        </a>
    </div>
</div>

<form method="POST" action="<?= $formAction ?>" id="session-form" novalidate>
    <?= csrfField() ?>
    <?php if ($isEdit): ?><?= methodField('PUT') ?><?php endif; ?>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">

        <!-- Main -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">Información de la sesión</h3></div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">

                    <div class="form-group">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldData['title'] ?? '') ?>"
                               placeholder="Ej: Keynote: El Futuro de la IA" required>
                        <?php if (isset($errors['title'])): ?>
                            <span class="form-error"><?= e($errors['title']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="¿De qué trata esta sesión?"><?= e($oldData['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Tipo *</label>
                            <select name="type" class="form-control" required>
                                <?php foreach ($types as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($oldData['type'] ?? 'talk') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-control">
                                <option value="scheduled" <?= ($oldData['status'] ?? 'scheduled') === 'scheduled' ? 'selected' : '' ?>>📅 Programada</option>
                                <option value="ongoing"   <?= ($oldData['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>▶️ En curso</option>
                                <option value="completed" <?= ($oldData['status'] ?? '') === 'completed' ? 'selected' : '' ?>>✅ Completada</option>
                                <option value="cancelled" <?= ($oldData['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>❌ Cancelada</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Speaker -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">🎤 Ponente / Speaker</h3></div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Nombre del ponente</label>
                        <input type="text" name="speaker_name" class="form-control"
                               value="<?= e($oldData['speaker_name'] ?? '') ?>"
                               placeholder="Dr. Juan García">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biografía / Descripción</label>
                        <textarea name="speaker_bio" class="form-control" rows="3"
                                  placeholder="Breve descripción del ponente..."><?= e($oldData['speaker_bio'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Side -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">🕐 Horario *</h3></div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Inicio *</label>
                        <input type="datetime-local" name="start_time"
                               class="form-control <?= isset($errors['start_time']) ? 'is-invalid' : '' ?>"
                               value="<?= e(str_replace(' ', 'T', $oldData['start_time'] ?? '')) ?>" required>
                        <?php if (isset($errors['start_time'])): ?>
                            <span class="form-error"><?= e($errors['start_time']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fin *</label>
                        <input type="datetime-local" name="end_time"
                               class="form-control <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>"
                               value="<?= e(str_replace(' ', 'T', $oldData['end_time'] ?? '')) ?>" required>
                        <?php if (isset($errors['end_time'])): ?>
                            <span class="form-error"><?= e($errors['end_time']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">📍 Sala / Formato</h3></div>
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Sala / Ubicación</label>
                        <input type="text" name="room" class="form-control"
                               value="<?= e($oldData['room'] ?? '') ?>"
                               placeholder="Auditorio Principal, Sala 3...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacidad máxima</label>
                        <input type="number" name="max_attendees" class="form-control"
                               value="<?= e($oldData['max_attendees'] ?? '') ?>"
                               placeholder="Sin límite" min="1">
                        <span class="form-hint">Vacío = sin límite</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_virtual" value="1"
                                   <?= !empty($oldData['is_virtual']) ? 'checked' : '' ?>
                                   style="width:auto; margin-right:8px;">
                            Sesión virtual / en línea
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enlace virtual</label>
                        <input type="url" name="virtual_link" class="form-control"
                               value="<?= e($oldData['virtual_link'] ?? '') ?>"
                               placeholder="https://meet.google.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Orden en agenda</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="<?= e($oldData['sort_order'] ?? 0) ?>" min="0">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <?= $isEdit ? '💾 Guardar cambios' : '➕ Crear sesión' ?>
            </button>
            <a href="/admin/events/<?= $event['id'] ?>/agenda" class="btn btn-ghost btn-full">Cancelar</a>
        </div>
    </div>
</form>
