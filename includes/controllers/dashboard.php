<?php

declare(strict_types=1);

Auth::requireLogin();
$user = Auth::user();

$stmt = $db->prepare("SELECT c.*, e.enrolled_at, e.completed_at FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.user_id = ? ORDER BY e.enrolled_at DESC");
$stmt->execute([Auth::id()]);
$enrollments = $stmt->fetchAll();

View::render('dashboard', [
    'user' => $user,
    'enrollments' => $enrollments,
]);
