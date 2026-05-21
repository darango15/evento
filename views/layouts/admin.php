<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'EventoSaaS') ?></title>
    <meta name="description" content="Panel de administración EventoSaaS">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">

<!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">⚡</div>
        <div class="brand-text">
            <span class="brand-name">EventoSaaS</span>
            <span class="brand-tenant"><?= e(currentTenant()['name'] ?? 'Panel Admin') ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php $u = authUser(); $eid = $_GET['event_id'] ?? ''; ?>

        <div class="nav-section">
            <span class="nav-section-label">Principal</span>
            <a href="/admin/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="/admin/events" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/events') && !str_contains($_SERVER['REQUEST_URI'], '/attendees') && !str_contains($_SERVER['REQUEST_URI'], '/agenda') && !str_contains($_SERVER['REQUEST_URI'], '/checkin') ? 'active' : '' ?>">
                <span class="nav-icon">📅</span> Eventos
            </a>
        </div>

        <?php 
        $currentEventId = null;
        if (isset($event['id'])) {
            $currentEventId = $event['id'];
        } elseif (!empty($_GET['eid'])) {
            $currentEventId = $_GET['eid'];
        } elseif (preg_match('/\/events\/(\d+)/', $_SERVER['REQUEST_URI'], $m)) {
            $currentEventId = $m[1];
        }
        ?>
        <?php if ($currentEventId): ?>
        <div class="nav-section">
            <span class="nav-section-label">Evento Actual</span>
            <a href="/admin/events/<?= $currentEventId ?>/attendees" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/attendees') ? 'active' : '' ?>">
                <span class="nav-icon">👥</span> Participantes
            </a>
            <a href="/admin/events/<?= $currentEventId ?>/agenda" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/agenda') ? 'active' : '' ?>">
                <span class="nav-icon">🗓️</span> Agenda
            </a>
            <a href="/admin/events/<?= $currentEventId ?>/checkin" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/checkin') ? 'active' : '' ?>">
                <span class="nav-icon">✅</span> Check-in
            </a>
            <a href="/admin/events/<?= $currentEventId ?>/sponsors" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/sponsors') ? 'active' : '' ?>">
                <span class="nav-icon">🏆</span> Sponsors
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?></div>
            <div class="user-details">
                <span class="user-name"><?= e($u['name'] ?? '') ?></span>
                <span class="user-role"><?= e($u['role'] ?? '') ?></span>
            </div>
        </div>
        <a href="/logout" class="logout-btn" title="Cerrar sesión">⬅</a>
    </div>
</aside>

<!-- ── Main Content ──────────────────────────────────────────────────────── -->
<main class="main-content">
    <header class="top-bar">
        <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Abrir menú">☰</button>
        <h1 class="page-title"><?= e($pageTitle ?? $title ?? 'Panel') ?></h1>
        <div class="top-bar-actions">
            <a href="/" target="_blank" class="btn btn-ghost btn-sm">🌐 Ver sitio</a>
        </div>
    </header>

    <!-- Flash messages -->
    <?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
        <?php foreach (flashMessages($type) as $msg): ?>
            <div class="alert alert-<?= $type ?>" role="alert">
                <?= e($msg) ?>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="page-content">
        <?= $content ?>
    </div>
</main>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
