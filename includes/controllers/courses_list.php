<?php

declare(strict_types=1);

$stmt = $db->query("SELECT * FROM courses WHERE status = 'published' ORDER BY created_at DESC");
View::render('courses_list', ['courses' => $stmt->fetchAll()]);
