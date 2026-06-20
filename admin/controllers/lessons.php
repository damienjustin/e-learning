<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

$courseId = (int) ($_GET['course_id'] ?? 0);
$moduleId = (int) ($_GET['module_id'] ?? 0);

$stmt = $db->prepare('SELECT c.* FROM courses c WHERE c.id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
    http_response_code(404);
    exit('Cours introuvable.');
}

$stmt = $db->prepare('SELECT * FROM modules WHERE id = ? AND course_id = ?');
$stmt->execute([$moduleId, $courseId]);
$module = $stmt->fetch();
if (!$module) {
    http_response_code(404);
    exit('Module introuvable.');
}

switch ($action) {
    case 'create':
    case 'edit':
        $lesson = ['id' => null, 'title' => '', 'slug' => '', 'content_type' => 'text', 'content' => '', 'video_url' => '', 'position' => 0, 'duration_minutes' => null];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM lessons WHERE id = ? AND module_id = ?');
            $stmt->execute([$id, $moduleId]);
            $lesson = $stmt->fetch();
            if (!$lesson) {
                http_response_code(404);
                exit('Leçon introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $slug = Security::slugify($_POST['slug'] !== '' ? (string) $_POST['slug'] : $title);
                $contentType = in_array($_POST['content_type'] ?? '', ['text', 'video', 'file'], true) ? $_POST['content_type'] : 'text';
                $content = (string) ($_POST['content'] ?? '');
                $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
                $position = (int) ($_POST['position'] ?? 0);
                $duration = $_POST['duration_minutes'] !== '' ? (int) $_POST['duration_minutes'] : null;

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }
                if ($contentType === 'video' && $videoUrl !== '' && !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                    $errors[] = 'URL vidéo invalide.';
                }

                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO lessons (module_id, title, slug, content_type, content, video_url, position, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                            ->execute([$moduleId, $title, $slug, $contentType, $content, $videoUrl ?: null, $position, $duration]);
                    } else {
                        $db->prepare('UPDATE lessons SET title = ?, slug = ?, content_type = ?, content = ?, video_url = ?, position = ?, duration_minutes = ? WHERE id = ? AND module_id = ?')
                            ->execute([$title, $slug, $contentType, $content, $videoUrl ?: null, $position, $duration, $lesson['id'], $moduleId]);
                    }
                    header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
                    exit;
                }
                $lesson = array_merge($lesson, compact('title', 'slug', 'contentType', 'content', 'videoUrl', 'position', 'duration'));
            }
        }

        render('lessons_form', ['lesson' => $lesson, 'course' => $course, 'module' => $module, 'errors' => $errors]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM lessons WHERE id = ? AND module_id = ?')->execute([$id, $moduleId]);
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;

    default:
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;
}
