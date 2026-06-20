<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

switch ($action) {
    case 'create':
    case 'edit':
        $course = ['id' => null, 'title' => '', 'slug' => '', 'summary' => '', 'description' => '', 'price' => '0', 'status' => 'draft', 'instructor_id' => $userId];
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
                $price = (float) ($_POST['price'] ?? 0);
                $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft';
                $slug = Security::slugify($_POST['slug'] !== '' ? (string) $_POST['slug'] : $title);

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }

                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO courses (title, slug, summary, description, price, status, instructor_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                            ->execute([$title, $slug, $summary, $description, $price, $status, $userId]);
                        $newId = (int) $db->lastInsertId();
                        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $newId]));
                        exit;
                    }

                    $db->prepare('UPDATE courses SET title = ?, slug = ?, summary = ?, description = ?, price = ?, status = ? WHERE id = ?')
                        ->execute([$title, $slug, $summary, $description, $price, $status, $course['id']]);
                    header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]));
                    exit;
                }

                $course = array_merge($course, compact('title', 'slug', 'summary', 'description', 'price', 'status'));
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

        render('courses_form', ['course' => $course, 'errors' => $errors, 'modules' => $modules]);
        break;

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
        if ($isAdmin) {
            $stmt = $db->query('SELECT c.*, u.name AS instructor_name FROM courses c JOIN users u ON u.id = c.instructor_id ORDER BY c.created_at DESC');
        } else {
            $stmt = $db->prepare('SELECT c.*, u.name AS instructor_name FROM courses c JOIN users u ON u.id = c.instructor_id WHERE c.instructor_id = ? ORDER BY c.created_at DESC');
            $stmt->execute([$userId]);
        }
        render('courses_list', ['courses' => $stmt->fetchAll()]);
}
