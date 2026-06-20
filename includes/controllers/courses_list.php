<?php

declare(strict_types=1);

$stmt = $db->query("SELECT * FROM courses WHERE status = 'published' ORDER BY created_at DESC");
$courses = $stmt->fetchAll();

$userId = Auth::id();
$courses = array_values(array_filter($courses, function ($course) use ($db, $userId) {
    if ($course['visibility'] !== 'restricted') {
        return true;
    }
    if (!$userId) {
        return false;
    }
    $stmt = $db->prepare('SELECT 1 FROM course_access WHERE course_id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM group_members WHERE user_id = ?)) LIMIT 1');
    $stmt->execute([$course['id'], $userId, $userId]);
    return (bool) $stmt->fetch();
}));

View::render('courses_list', ['courses' => $courses]);
