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
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $stmt = $db->prepare('SELECT id FROM modules WHERE id = ? AND course_id = ?');
        $stmt->execute([$moduleId, $courseId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['ok' => false]);
            exit;
        }
        $kind = $_POST['kind'] ?? '';
        $ids = json_decode($_POST['order'] ?? '[]', true);
        if (!in_array($kind, ['lessons', 'quizzes'], true) || !is_array($ids)) {
            http_response_code(400);
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

    default:
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;
}
