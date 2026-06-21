<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
    http_response_code(404);
    exit('Cours introuvable.');
}

$config = array_merge(Certificate::defaultConfig(), $course['certificate_config'] ? (json_decode((string) $course['certificate_config'], true) ?: []) : []);

if ($action === 'preview' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $previewConfig = array_merge($config, array_intersect_key($_POST, $config));
    $demoVars = [
        'student_name' => 'Étudiant Démo',
        'course_title' => $course['title'],
        'date' => date('d/m/Y'),
        'instructor_name' => 'Formateur Démo',
    ];
    header('Content-Type: image/png');
    echo Certificate::render($previewConfig, $demoVars);
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $errors[] = 'Jeton de sécurité invalide.';
    } else {
        $enabled = isset($_POST['certificate_enabled']) ? 1 : 0;
        $config = [
            'background_color' => (string) ($_POST['background_color'] ?? $config['background_color']),
            'border_color' => (string) ($_POST['border_color'] ?? $config['border_color']),
            'title' => (string) ($_POST['title'] ?? $config['title']),
            'title_color' => (string) ($_POST['title_color'] ?? $config['title_color']),
            'body' => (string) ($_POST['body'] ?? $config['body']),
            'body_color' => (string) ($_POST['body_color'] ?? $config['body_color']),
            'footer' => (string) ($_POST['footer'] ?? $config['footer']),
            'footer_color' => (string) ($_POST['footer_color'] ?? $config['footer_color']),
            'logo_url' => (string) ($_POST['logo_url'] ?? $config['logo_url']),
        ];
        $db->prepare('UPDATE courses SET certificate_enabled = ?, certificate_config = ? WHERE id = ?')
            ->execute([$enabled, json_encode($config), $id]);
        $course['certificate_enabled'] = $enabled;
        header('Location: ' . adminUrl('certificate', ['id' => $id]));
        exit;
    }
}

render('certificate_builder', ['course' => $course, 'config' => $config, 'errors' => $errors]);
