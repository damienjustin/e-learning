<?php

declare(strict_types=1);

$counts = [
    'courses' => (int) $db->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'enrollments' => (int) $db->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
];

render('dashboard', ['counts' => $counts]);
