<?php
/** @var callable $content */
$appName = Config::get('app')['name'] ?? 'E-Learning CMS';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::e($appName) ?></title>
    <link rel="stylesheet" href="/themes/default/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="/"><?= Security::e($appName) ?></a>
        <nav>
            <a href="/courses">Cours</a>
            <?php if (Auth::check()): ?>
                <a href="/dashboard">Mon espace</a>
                <?php if (Auth::hasRole('admin', 'instructor')): ?>
                    <a href="/admin/">Administration</a>
                <?php endif; ?>
                <a href="/logout">Déconnexion</a>
            <?php else: ?>
                <a href="/login">Connexion</a>
                <a href="/register" class="btn-link">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
    <?php $content(); ?>
</main>
<footer class="site-footer">
    <div class="container">&copy; <?= date('Y') ?> <?= Security::e($appName) ?></div>
</footer>
<script src="/themes/default/assets/js/main.js"></script>
</body>
</html>
