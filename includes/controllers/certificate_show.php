<?php

declare(strict_types=1);

Auth::requireLogin();

$slug = (string) ($_GET['course_slug'] ?? '');

$stmt = $db->prepare('SELECT c.*, u.name AS instructor_name FROM courses c
    JOIN users u ON u.id = c.instructor_id
    WHERE c.slug = ? LIMIT 1');
$stmt->execute([$slug]);
$course = $stmt->fetch();

if (!$course || !$course['certificate_enabled']) {
    notFound();
}

$stmt = $db->prepare('SELECT completed_at FROM enrollments WHERE user_id = ? AND course_id = ?');
$stmt->execute([Auth::id(), $course['id']]);
$enrollment = $stmt->fetch();

if (!$enrollment || !$enrollment['completed_at']) {
    notFound();
}

$config = $course['certificate_config'] ? (json_decode((string) $course['certificate_config'], true) ?: []) : [];

$vars = [
    'student_name' => Auth::user()['name'] ?? '',
    'course_title' => $course['title'],
    'date' => date('d/m/Y', strtotime((string) $enrollment['completed_at'])),
    'instructor_name' => $course['instructor_name'],
];

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="certificat-' . $course['slug'] . '.png"');
echo Certificate::render($config, $vars);
