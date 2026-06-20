<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireRole('admin', 'instructor');

$db = Database::connection();
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

function adminUrl(string $page, array $params = []): string
{
    return '/admin/?page=' . urlencode($page) . ($params ? '&' . http_build_query($params) : '');
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/views/layout.php';
}

$allowedPages = ['dashboard', 'courses', 'modules', 'lessons', 'quizzes', 'users', 'settings', 'updates', 'media'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

// Only admins manage users, global settings and core updates.
if (in_array($page, ['users', 'settings', 'updates'], true)) {
    Auth::requireRole('admin');
}

$controllerFile = __DIR__ . '/controllers/' . $page . '.php';
if (is_file($controllerFile)) {
    require $controllerFile;
} else {
    render('dashboard');
}
