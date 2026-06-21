<?php

declare(strict_types=1);

Auth::requireLogin();

$courseSlug = (string) ($_GET['course_slug'] ?? '');
$lessonSlug = (string) ($_GET['lesson_slug'] ?? '');

$stmt = $db->prepare('SELECT c.* FROM courses c WHERE c.slug = ? LIMIT 1');
$stmt->execute([$courseSlug]);
$course = $stmt->fetch();

$isOwnerOrAdmin = $course && (Auth::hasRole('admin') || (int) $course['instructor_id'] === Auth::id());

if (!$course || ($course['status'] !== 'published' && !$isOwnerOrAdmin)) {
    notFound();
}

$stmt = $db->prepare('SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?');
$stmt->execute([Auth::id(), $course['id']]);
if (!$stmt->fetch() && !$isOwnerOrAdmin) {
    header('Location: /course/' . $course['slug']);
    exit;
}

$stmt = $db->prepare('SELECT l.* FROM lessons l
    JOIN modules m ON m.id = l.module_id
    WHERE m.course_id = ? AND l.slug = ? LIMIT 1');
$stmt->execute([$course['id'], $lessonSlug]);
$lesson = $stmt->fetch();
if (!$lesson) {
    notFound();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        http_response_code(400);
        exit('Jeton de sécurité invalide.');
    }
    $db->prepare('INSERT IGNORE INTO lesson_progress (user_id, lesson_id) VALUES (?, ?)')
        ->execute([Auth::id(), $lesson['id']]);

    $stmt = $db->prepare('SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = ?');
    $stmt->execute([$course['id']]);
    $totalLessons = (int) $stmt->fetchColumn();

    $stmt = $db->prepare('SELECT COUNT(*) FROM lesson_progress lp
        JOIN lessons l ON l.id = lp.lesson_id
        JOIN modules m ON m.id = l.module_id
        WHERE m.course_id = ? AND lp.user_id = ?');
    $stmt->execute([$course['id'], Auth::id()]);
    $completedLessons = (int) $stmt->fetchColumn();

    if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
        $db->prepare('UPDATE enrollments SET completed_at = NOW() WHERE user_id = ? AND course_id = ? AND completed_at IS NULL')
            ->execute([Auth::id(), $course['id']]);
    }
}

$stmt = $db->prepare('SELECT lesson_id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?');
$stmt->execute([Auth::id(), $lesson['id']]);
$completed = (bool) $stmt->fetch();

// Build full lesson/quiz navigation for the sidebar.
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

View::render('lesson_show', [
    'course' => $course,
    'lesson' => $lesson,
    'modules' => $modules,
    'completed' => $completed,
]);
