<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

switch ($action) {
    case 'create':
    case 'edit':
        $course = ['id' => null, 'title' => '', 'slug' => '', 'summary' => '', 'description' => '', 'description_blocks' => '[]', 'price' => '0', 'status' => 'draft', 'visibility' => 'public', 'instructor_id' => $userId];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
            $stmt->execute([$id]);
            $course = $stmt->fetch();
            if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
                http_response_code(404);
                exit('Cours introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $summary = trim((string) ($_POST['summary'] ?? ''));
                $description = (string) ($_POST['description'] ?? '');
                $blocks = Blocks::sanitize(Blocks::decode($_POST['description_blocks'] ?? '[]'));
                $descriptionBlocks = json_encode($blocks);
                $price = (float) ($_POST['price'] ?? 0);
                $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft';
                $visibility = in_array($_POST['visibility'] ?? '', ['public', 'restricted'], true) ? $_POST['visibility'] : 'public';
                $slug = Security::slugify($_POST['slug'] !== '' ? (string) $_POST['slug'] : $title);

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }

                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO courses (title, slug, summary, description, description_blocks, price, status, visibility, instructor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                            ->execute([$title, $slug, $summary, $description, $descriptionBlocks, $price, $status, $visibility, $userId]);
                        $newId = (int) $db->lastInsertId();
                        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $newId]));
                        exit;
                    }

                    $db->prepare('UPDATE courses SET title = ?, slug = ?, summary = ?, description = ?, description_blocks = ?, price = ?, status = ?, visibility = ? WHERE id = ?')
                        ->execute([$title, $slug, $summary, $description, $descriptionBlocks, $price, $status, $visibility, $course['id']]);
                    header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]));
                    exit;
                }

                $course = array_merge($course, compact('title', 'slug', 'summary', 'description', 'price', 'status', 'visibility'), ['description_blocks' => $descriptionBlocks]);
            }
        }

        $modules = [];
        if ($course['id']) {
            $stmt = $db->prepare('SELECT * FROM modules WHERE course_id = ? ORDER BY position ASC, id ASC');
            $stmt->execute([$course['id']]);
            $modules = $stmt->fetchAll();
            foreach ($modules as &$module) {
                $lessons = $db->prepare('SELECT * FROM lessons WHERE module_id = ? ORDER BY position ASC, id ASC');
                $lessons->execute([$module['id']]);
                $module['lessons'] = $lessons->fetchAll();

                $quizzes = $db->prepare('SELECT * FROM quizzes WHERE module_id = ? ORDER BY position ASC, id ASC');
                $quizzes->execute([$module['id']]);
                $module['quizzes'] = $quizzes->fetchAll();
            }
            unset($module);
        }

        $accessGroups = [];
        $accessUsers = [];
        $allGroups = [];
        if ($course['id']) {
            $stmt = $db->prepare('SELECT ca.id AS access_id, g.name FROM course_access ca JOIN groups g ON g.id = ca.group_id WHERE ca.course_id = ?');
            $stmt->execute([$course['id']]);
            $accessGroups = $stmt->fetchAll();

            $stmt = $db->prepare('SELECT ca.id AS access_id, u.name, u.email FROM course_access ca JOIN users u ON u.id = ca.user_id WHERE ca.course_id = ?');
            $stmt->execute([$course['id']]);
            $accessUsers = $stmt->fetchAll();

            $allGroups = $db->query('SELECT * FROM groups ORDER BY name ASC')->fetchAll();
        }

        render('courses_form', ['course' => $course, 'errors' => $errors, 'modules' => $modules, 'accessGroups' => $accessGroups, 'accessUsers' => $accessUsers, 'allGroups' => $allGroups]);
        break;

    case 'grant_access':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT instructor_id FROM courses WHERE id = ?');
            $stmt->execute([$id]);
            $owner = $stmt->fetchColumn();
            if ($owner !== false && ($isAdmin || (int) $owner === $userId)) {
                $groupId = (int) ($_POST['group_id'] ?? 0);
                $email = trim((string) ($_POST['user_email'] ?? ''));
                if ($groupId > 0) {
                    $db->prepare('INSERT INTO course_access (course_id, group_id) VALUES (?, ?)')->execute([$id, $groupId]);
                } elseif ($email !== '') {
                    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    $targetUserId = $stmt->fetchColumn();
                    if ($targetUserId) {
                        $db->prepare('INSERT INTO course_access (course_id, user_id) VALUES (?, ?)')->execute([$id, $targetUserId]);
                    }
                }
            }
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => (int) ($_POST['id'] ?? 0)]));
        exit;

    case 'revoke_access':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT instructor_id FROM courses WHERE id = ?');
            $stmt->execute([$id]);
            $owner = $stmt->fetchColumn();
            if ($owner !== false && ($isAdmin || (int) $owner === $userId)) {
                $accessId = (int) ($_POST['access_id'] ?? 0);
                $db->prepare('DELETE FROM course_access WHERE id = ? AND course_id = ?')->execute([$accessId, $id]);
            }
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => (int) ($_POST['id'] ?? 0)]));
        exit;

    case 'duplicate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
            $stmt->execute([$id]);
            $src = $stmt->fetch();
            if ($src && ($isAdmin || (int) $src['instructor_id'] === $userId)) {
                $slug = Security::slugify($src['title'] . '-copie-' . time());
                $db->prepare('INSERT INTO courses (title, slug, summary, description, description_blocks, price, status, visibility, instructor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$src['title'] . ' (copie)', $slug, $src['summary'], $src['description'], $src['description_blocks'], $src['price'], 'draft', $src['visibility'], $userId]);
                $newCourseId = (int) $db->lastInsertId();

                $modulesStmt = $db->prepare('SELECT * FROM modules WHERE course_id = ? ORDER BY position ASC, id ASC');
                $modulesStmt->execute([$id]);
                foreach ($modulesStmt->fetchAll() as $mod) {
                    $db->prepare('INSERT INTO modules (course_id, title, description_blocks, position) VALUES (?, ?, ?, ?)')
                        ->execute([$newCourseId, $mod['title'], $mod['description_blocks'], $mod['position']]);
                    $newModuleId = (int) $db->lastInsertId();

                    $lessonsStmt = $db->prepare('SELECT * FROM lessons WHERE module_id = ? ORDER BY position ASC, id ASC');
                    $lessonsStmt->execute([$mod['id']]);
                    foreach ($lessonsStmt->fetchAll() as $lesson) {
                        $db->prepare('INSERT INTO lessons (module_id, title, slug, content_type, content, content_blocks, video_url, position, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                            ->execute([$newModuleId, $lesson['title'], $lesson['slug'] . '-' . $newModuleId, $lesson['content_type'], $lesson['content'], $lesson['content_blocks'], $lesson['video_url'], $lesson['position'], $lesson['duration_minutes']]);
                    }

                    $quizzesStmt = $db->prepare('SELECT * FROM quizzes WHERE module_id = ? ORDER BY position ASC, id ASC');
                    $quizzesStmt->execute([$mod['id']]);
                    foreach ($quizzesStmt->fetchAll() as $quiz) {
                        $db->prepare('INSERT INTO quizzes (module_id, title, pass_score, max_attempts, position) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$newModuleId, $quiz['title'], $quiz['pass_score'], $quiz['max_attempts'], $quiz['position']]);
                        $newQuizId = (int) $db->lastInsertId();

                        $questionsStmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC');
                        $questionsStmt->execute([$quiz['id']]);
                        foreach ($questionsStmt->fetchAll() as $question) {
                            $db->prepare('INSERT INTO quiz_questions (quiz_id, question, type, position) VALUES (?, ?, ?, ?)')
                                ->execute([$newQuizId, $question['question'], $question['type'], $question['position']]);
                            $newQuestionId = (int) $db->lastInsertId();

                            $answersStmt = $db->prepare('SELECT * FROM quiz_answers WHERE question_id = ? ORDER BY position ASC, id ASC');
                            $answersStmt->execute([$question['id']]);
                            foreach ($answersStmt->fetchAll() as $answer) {
                                $db->prepare('INSERT INTO quiz_answers (question_id, answer, is_correct, position) VALUES (?, ?, ?, ?)')
                                    ->execute([$newQuestionId, $answer['answer'], $answer['is_correct'], $answer['position']]);
                            }
                        }
                    }
                }
                header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $newCourseId]));
                exit;
            }
        }
        header('Location: ' . adminUrl('courses'));
        exit;

    case 'preview':
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
        $stmt->execute([$id]);
        $course = $stmt->fetch();
        if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
            http_response_code(404);
            exit('Cours introuvable.');
        }
        header('Location: /course/' . $course['slug']);
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT instructor_id FROM courses WHERE id = ?');
            $stmt->execute([$id]);
            $owner = $stmt->fetchColumn();
            if ($owner !== false && ($isAdmin || (int) $owner === $userId)) {
                $db->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
            }
        }
        header('Location: ' . adminUrl('courses'));
        exit;

    default:
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = in_array($_GET['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_GET['status'] : '';

        $sql = 'SELECT c.*, u.name AS instructor_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrollment_count
                FROM courses c JOIN users u ON u.id = c.instructor_id WHERE 1=1';
        $params = [];
        if (!$isAdmin) {
            $sql .= ' AND c.instructor_id = ?';
            $params[] = $userId;
        }
        if ($search !== '') {
            $sql .= ' AND c.title LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if ($statusFilter !== '') {
            $sql .= ' AND c.status = ?';
            $params[] = $statusFilter;
        }
        $sql .= ' ORDER BY c.created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        render('courses_list', ['courses' => $stmt->fetchAll(), 'search' => $search, 'statusFilter' => $statusFilter]);
}
