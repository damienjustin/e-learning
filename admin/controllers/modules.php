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
        $module = ['id' => null, 'title' => '', 'position' => 0];
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
                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }
                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO modules (course_id, title, position) VALUES (?, ?, ?)')
                            ->execute([$courseId, $title, $position]);
                    } else {
                        $db->prepare('UPDATE modules SET title = ?, position = ? WHERE id = ? AND course_id = ?')
                            ->execute([$title, $position, $module['id'], $courseId]);
                    }
                    header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
                    exit;
                }
                $module = array_merge($module, compact('title', 'position'));
            }
        }

        render('modules_form', ['module' => $module, 'course' => $course, 'errors' => $errors]);
        break;

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
