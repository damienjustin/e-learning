<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$db = Database::connection();
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/');
$segments = $path === '' ? [] : explode('/', $path);

function notFound(): void
{
    http_response_code(404);
    View::render('404');
    exit;
}

// ---- Routing ----------------------------------------------------------

if ($segments === []) {
    $stmt = $db->query("SELECT * FROM courses WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
    View::render('home', ['courses' => $stmt->fetchAll()]);
    exit;
}

switch ($segments[0]) {
    case 'login':
        require CMS_ROOT . '/includes/controllers/auth_login.php';
        exit;

    case 'register':
        require CMS_ROOT . '/includes/controllers/auth_register.php';
        exit;

    case 'logout':
        Auth::logout();
        header('Location: /');
        exit;

    case 'dashboard':
        require CMS_ROOT . '/includes/controllers/dashboard.php';
        exit;

    case 'courses':
        require CMS_ROOT . '/includes/controllers/courses_list.php';
        exit;

    case 'course':
        if (!isset($segments[1])) {
            notFound();
        }
        $_GET['slug'] = $segments[1];
        require CMS_ROOT . '/includes/controllers/course_show.php';
        exit;

    case 'learn':
        if (!isset($segments[1], $segments[2])) {
            notFound();
        }
        $_GET['course_slug'] = $segments[1];
        $_GET['lesson_slug'] = $segments[2];
        require CMS_ROOT . '/includes/controllers/lesson_show.php';
        exit;

    case 'quiz':
        if (!isset($segments[1])) {
            notFound();
        }
        $_GET['quiz_id'] = $segments[1];
        require CMS_ROOT . '/includes/controllers/quiz_take.php';
        exit;

    default:
        notFound();
}
