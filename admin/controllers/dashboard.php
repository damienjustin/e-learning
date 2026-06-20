<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

$courseFilter = $isAdmin ? '' : ' WHERE instructor_id = ?';
$courseParams = $isAdmin ? [] : [$userId];

$stmt = $db->prepare("SELECT COUNT(*) FROM courses{$courseFilter}");
$stmt->execute($courseParams);
$courseCount = (int) $stmt->fetchColumn();

$counts = [
    'courses' => $courseCount,
    'users' => $isAdmin ? (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() : null,
    'enrollments' => 0,
];

$enrollFilter = $isAdmin ? '' : ' JOIN courses c ON c.id = e.course_id WHERE c.instructor_id = ?';
$stmt = $db->prepare("SELECT COUNT(*) FROM enrollments e{$enrollFilter}");
$stmt->execute($isAdmin ? [] : [$userId]);
$counts['enrollments'] = (int) $stmt->fetchColumn();

$popularSql = "SELECT c.id, c.title, COUNT(e.id) AS enrollment_count
    FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id";
$popularSql .= $isAdmin ? '' : ' WHERE c.instructor_id = ?';
$popularSql .= ' GROUP BY c.id ORDER BY enrollment_count DESC LIMIT 5';
$stmt = $db->prepare($popularSql);
$stmt->execute($isAdmin ? [] : [$userId]);
$popularCourses = $stmt->fetchAll();

$recentSql = "SELECT e.enrolled_at, u.name AS user_name, c.title AS course_title
    FROM enrollments e JOIN users u ON u.id = e.user_id JOIN courses c ON c.id = e.course_id";
$recentSql .= $isAdmin ? '' : ' WHERE c.instructor_id = ?';
$recentSql .= ' ORDER BY e.enrolled_at DESC LIMIT 10';
$stmt = $db->prepare($recentSql);
$stmt->execute($isAdmin ? [] : [$userId]);
$recentEnrollments = $stmt->fetchAll();

$completionSql = "SELECT c.id, c.title,
        (SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = c.id) AS total_lessons,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS total_enrolled,
        (SELECT COUNT(*) FROM lesson_progress lp
            JOIN lessons l ON l.id = lp.lesson_id
            JOIN modules m ON m.id = l.module_id
            WHERE m.course_id = c.id) AS total_completions
    FROM courses c";
$completionSql .= $isAdmin ? '' : ' WHERE c.instructor_id = ?';
$stmt = $db->prepare($completionSql);
$stmt->execute($isAdmin ? [] : [$userId]);
$completionRows = $stmt->fetchAll();

$completionRates = [];
foreach ($completionRows as $row) {
    $possible = (int) $row['total_lessons'] * (int) $row['total_enrolled'];
    $rate = $possible > 0 ? round(((int) $row['total_completions'] / $possible) * 100) : null;
    $completionRates[] = ['title' => $row['title'], 'rate' => $rate];
}

render('dashboard', [
    'counts' => $counts,
    'popularCourses' => $popularCourses,
    'recentEnrollments' => $recentEnrollments,
    'completionRates' => $completionRates,
    'isAdmin' => $isAdmin,
]);
