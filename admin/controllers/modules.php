<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

function assertCourseAccess(PDO $db, int $courseId, int $userId, bool $isAdmin): array
{
    $stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
        http_response_code(404);
        exit('Cours introuvable.');
    }
    return $course;
}

$courseId = (int) ($_GET['course_id'] ?? 0);
$course = assertCourseAccess($db, $courseId, $userId, $isAdmin);

switch ($action) {
    case 'create':
    case 'edit':
        $module = ['id' => null, 'title' => '', 'position' => 0, 'description_blocks' => '[]'];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM modules WHERE id = ? AND course_id = ?');
            $stmt->execute([$id, $courseId]);
            $module = $stmt->fetch();
            if (!$module) {
                http_response_code(404);
                exit('Module introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $position = (int) ($_POST['position'] ?? 0);
                $blocks = Blocks::sanitize(Blocks::decode($_POST['description_blocks'] ?? '[]'));
                $descriptionBlocks = json_encode($blocks);
                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }
                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO modules (course_id, title, description_blocks, position) VALUES (?, ?, ?, ?)')
                            ->execute([$courseId, $title, $descriptionBlocks, $position]);
                    } else {
                        $db->prepare('UPDATE modules SET title = ?, description_blocks = ?, position = ? WHERE id = ? AND course_id = ?')
                            ->execute([$title, $descriptionBlocks, $position, $module['id'], $courseId]);
                    }
                    header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
                    exit;
                }
                $module = array_merge($module, compact('title', 'position'), ['description_blocks' => $descriptionBlocks]);
            }
        }

        render('modules_form', ['module' => $module, 'course' => $course, 'errors' => $errors]);
        break;

    case 'reorder':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['ok' => false]);
            exit;
        }
        $kind = $_POST['kind'] ?? '';
        $ids = json_decode($_POST['order'] ?? '[]', true);
        if (!in_array($kind, ['lessons', 'quizzes', 'modules'], true) || !is_array($ids)) {
            http_response_code(400);
            echo json_encode(['ok' => false]);
            exit;
        }

        if ($kind === 'modules') {
            $update = $db->prepare('UPDATE modules SET position = ? WHERE id = ? AND course_id = ?');
            foreach (array_values($ids) as $position => $id) {
                $update->execute([$position, (int) $id, $courseId]);
            }
            echo json_encode(['ok' => true]);
            exit;
        }

        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $stmt = $db->prepare('SELECT id FROM modules WHERE id = ? AND course_id = ?');
        $stmt->execute([$moduleId, $courseId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['ok' => false]);
            exit;
        }
        $table = $kind === 'lessons' ? 'lessons' : 'quizzes';
        $update = $db->prepare("UPDATE {$table} SET position = ? WHERE id = ? AND module_id = ?");
        foreach (array_values($ids) as $position => $id) {
            $update->execute([$position, (int) $id, $moduleId]);
        }
        echo json_encode(['ok' => true]);
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM modules WHERE id = ? AND course_id = ?')->execute([$id, $courseId]);
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;

    case 'duplicate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM modules WHERE id = ? AND course_id = ?');
            $stmt->execute([$id, $courseId]);
            $src = $stmt->fetch();
            if ($src) {
                $db->prepare('INSERT INTO modules (course_id, title, description_blocks, position) VALUES (?, ?, ?, ?)')
                    ->execute([$courseId, $src['title'] . ' (copie)', $src['description_blocks'], $src['position']]);
                $newModuleId = (int) $db->lastInsertId();

                $lessons = $db->prepare('SELECT * FROM lessons WHERE module_id = ? ORDER BY position ASC, id ASC');
                $lessons->execute([$id]);
                foreach ($lessons->fetchAll() as $lesson) {
                    $db->prepare('INSERT INTO lessons (module_id, title, slug, content_type, content, content_blocks, video_url, position, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$newModuleId, $lesson['title'], $lesson['slug'] . '-copie-' . $newModuleId, $lesson['content_type'], $lesson['content'], $lesson['content_blocks'], $lesson['video_url'], $lesson['position'], $lesson['duration_minutes']]);
                }

                $quizzes = $db->prepare('SELECT * FROM quizzes WHERE module_id = ? ORDER BY position ASC, id ASC');
                $quizzes->execute([$id]);
                foreach ($quizzes->fetchAll() as $quiz) {
                    $db->prepare('INSERT INTO quizzes (module_id, title, pass_score, max_attempts, position) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$newModuleId, $quiz['title'], $quiz['pass_score'], $quiz['max_attempts'], $quiz['position']]);
                    $newQuizId = (int) $db->lastInsertId();

                    $questions = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC');
                    $questions->execute([$quiz['id']]);
                    foreach ($questions->fetchAll() as $question) {
                        $db->prepare('INSERT INTO quiz_questions (quiz_id, question, type, position) VALUES (?, ?, ?, ?)')
                            ->execute([$newQuizId, $question['question'], $question['type'], $question['position']]);
                        $newQuestionId = (int) $db->lastInsertId();

                        $answers = $db->prepare('SELECT * FROM quiz_answers WHERE question_id = ? ORDER BY position ASC, id ASC');
                        $answers->execute([$question['id']]);
                        foreach ($answers->fetchAll() as $answer) {
                            $db->prepare('INSERT INTO quiz_answers (question_id, answer, is_correct, position) VALUES (?, ?, ?, ?)')
                                ->execute([$newQuestionId, $answer['answer'], $answer['is_correct'], $answer['position']]);
                        }
                    }
                }
            }
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;

    default:
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;
}
