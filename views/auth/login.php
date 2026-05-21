<!-- Login Form -->
<div class="auth-card">
    <div class="auth-header">
        <div class="auth-logo">⚡</div>
        <h1 class="auth-title">EventoSaaS</h1>
        <p class="auth-subtitle">Panel de Administración</p>
    </div>

    <!-- Flash messages -->
    <?php foreach (['error', 'success', 'warning', 'info'] as $type): ?>
        <?php foreach (flashMessages($type) as $msg): ?>
            <div class="alert alert-<?= $type ?> mb-3"><?= e($msg) ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <form id="login-form" action="<?= e($_SERVER['REQUEST_URI']) ?>" method="POST" class="auth-form" novalidate>
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email" class="form-label">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="<?= e($_SESSION['form_data']['email'] ?? '') ?>"
                placeholder="admin@tuempresa.com"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="input-addon" onclick="togglePassword()" aria-label="Ver contraseña">👁</button>
            </div>
        </div>

        <button type="submit" id="login-btn" class="btn btn-primary btn-full">
            Iniciar Sesión
        </button>
    </form>

    <p class="auth-footer">
        ¿Problemas para acceder? Contacta al administrador del sistema.
    </p>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
}

// Deshabilitar botón al enviar para evitar doble submit
document.getElementById('login-form').addEventListener('submit', function() {
    document.getElementById('login-btn').disabled = true;
    document.getElementById('login-btn').textContent = 'Iniciando sesión...';
});
</script>

<?php unset($_SESSION['form_data']); ?>
