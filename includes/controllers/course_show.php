<?php

declare(strict_types=1);

$slug = (string) ($_GET['slug'] ?? '');

$stmt = $db->prepare("SELECT c.*, u.name AS instructor_name FROM courses c
    JOIN users u ON u.id = c.instructor_id
    WHERE c.slug = ? AND c.status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$course = $stmt->fetch();

if (!$course) {
    notFound();
}

$enrolled = false;
if (Auth::check()) {
    $stmt = $db->prepare('SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?');
    $stmt->execute([Auth::id(), $course['id']]);
    $enrolled = (bool) $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            exit('Jeton de sécurité invalide.');
        }
        $db->prepare('INSERT IGNORE INTO enrollments (user_id, course_id) VALUES (?, ?)')
            ->execute([Auth::id(), $course['id']]);
        header('Location: /course/' . $course['slug']);
        exit;
    }
}

$modules = $db->prepare('SELECT * FROM modules WHERE course_id = ? ORDER BY position ASC, id ASC');
$modules->execute([$course['id']]);
$modules = $modules->fetchAll();

foreach ($modules as &$module) {
    $lessons = $db->prepare('SELECT * FROM lessons WHERE module_id = ? ORDER BY position ASC, id ASC');
    $lessons->execute([$module['id']]);
    $module['lessons'] = $lessons->fetchAll();

    $quizzes = $db->prepare('SELECT * FROM quizzes WHERE module_id = ? ORDER BY position ASC, id ASC');
    $quizzes->execute([$module['id']]);
    $module['quizzes'] = $quizzes->fetchAll();
}
unset($module);

View::render('course_show', [
    'course' => $course,
    'modules' => $modules,
    'enrolled' => $enrolled,
]);
