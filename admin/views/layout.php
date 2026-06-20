<?php
/** @var string $page */
$page = $page ?? ($_GET['page'] ?? 'dashboard');
$appName = Config::get('app')['name'] ?? 'Bloomin LMS';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration &middot; <?= Security::e($appName) ?></title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><?= Security::e($appName) ?></div>
        <nav>
            <a href="<?= adminUrl('dashboard') ?>" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
            <a href="<?= adminUrl('courses') ?>" class="<?= $page === 'courses' ? 'active' : '' ?>">Cours</a>
            <a href="<?= adminUrl('media') ?>" class="<?= $page === 'media' ? 'active' : '' ?>">Médias</a>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="<?= adminUrl('users') ?>" class="<?= $page === 'users' ? 'active' : '' ?>">Utilisateurs</a>
                <a href="<?= adminUrl('settings') ?>" class="<?= $page === 'settings' ? 'active' : '' ?>">Réglages</a>
                <a href="<?= adminUrl('updates') ?>" class="<?= $page === 'updates' ? 'active' : '' ?>">Mises à jour</a>
            <?php endif; ?>
            <a href="/">Voir le site</a>
            <a href="/logout">Déconnexion</a>
        </nav>
        <div class="admin-credit">
            &copy; <?= date('Y') ?> <a href="https://bloomin.agency" target="_blank" rel="noopener">Bloomin LMS</a>
        </div>
    </aside>
    <main class="admin-content">
        <?php require __DIR__ . '/' . $view . '.php'; ?>
    </main>
</div>
<script src="/admin/assets/js/builder.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
