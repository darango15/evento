<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'EventoSaaS') ?></title>
    <meta name="description" content="<?= e($title ?? 'Sistema de gestión de eventos') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="public-body">
    <header class="public-header">
        <div class="container">
            <?php $t = currentTenant(); ?>
            <a href="/" class="public-logo">
                <?php if (!empty($t['logo'])): ?>
                    <img src="<?= e($t['logo']) ?>" alt="<?= e($t['name']) ?>" class="logo-img">
                <?php else: ?>
                    <span class="logo-text">⚡ <?= e($t['name'] ?? 'EventoSaaS') ?></span>
                <?php endif; ?>
            </a>
            <a href="/login" class="btn btn-outline-primary btn-sm">Acceso Staff</a>
        </div>
    </header>

    <main class="public-main">
        <?= $content ?>
    </main>

    <footer class="public-footer">
        <div class="container">
            <p>© <?= date('Y') ?> <?= e($t['name'] ?? 'EventoSaaS') ?> · Powered by EventoSaaS</p>
        </div>
    </footer>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
