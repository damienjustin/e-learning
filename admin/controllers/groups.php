<?php

declare(strict_types=1);

switch ($action) {
    case 'create':
    case 'edit':
        $group = ['id' => null, 'name' => ''];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM member_groups WHERE id = ?');
            $stmt->execute([$id]);
            $group = $stmt->fetch();
            if (!$group) {
                http_response_code(404);
                exit('Groupe introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'group') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $name = trim((string) ($_POST['name'] ?? ''));
                if ($name === '') {
                    $errors[] = 'Le nom est obligatoire.';
                }
                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO member_groups (name) VALUES (?)')->execute([$name]);
                        $newId = (int) $db->lastInsertId();
                        header('Location: ' . adminUrl('groups', ['action' => 'edit', 'id' => $newId]));
                        exit;
                    }
                    $db->prepare('UPDATE member_groups SET name = ? WHERE id = ?')->execute([$name, $group['id']]);
                    header('Location: ' . adminUrl('groups', ['action' => 'edit', 'id' => $group['id']]));
                    exit;
                }
                $group = array_merge($group, compact('name'));
            }
        }

        $members = [];
        $courses = [];
        $candidates = [];
        if ($group['id']) {
            $stmt = $db->prepare('SELECT u.* FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY u.name ASC');
            $stmt->execute([$group['id']]);
            $members = $stmt->fetchAll();

            $stmt = $db->prepare('SELECT c.* FROM course_access ca JOIN courses c ON c.id = ca.course_id WHERE ca.group_id = ? ORDER BY c.title ASC');
            $stmt->execute([$group['id']]);
            $courses = $stmt->fetchAll();

            $stmt = $db->prepare('SELECT DISTINCT u.id, u.name, u.email
                FROM enrollments e JOIN users u ON u.id = e.user_id
                WHERE u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)
                ORDER BY u.name ASC');
            $stmt->execute([$group['id']]);
            $candidates = $stmt->fetchAll();
        }

        render('groups_form', ['group' => $group, 'errors' => $errors, 'members' => $members, 'courses' => $courses, 'candidates' => $candidates]);
        break;

    case 'add_member':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $groupId = (int) ($_POST['id'] ?? 0);
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId) {
                $db->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)')->execute([$groupId, $userId]);
            }
        }
        header('Location: ' . adminUrl('groups', ['action' => 'edit', 'id' => (int) ($_POST['id'] ?? 0)]));
        exit;

    case 'remove_member':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $groupId = (int) ($_POST['id'] ?? 0);
            $userId = (int) ($_POST['user_id'] ?? 0);
            $db->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?')->execute([$groupId, $userId]);
        }
        header('Location: ' . adminUrl('groups', ['action' => 'edit', 'id' => (int) ($_POST['id'] ?? 0)]));
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM member_groups WHERE id = ?')->execute([$id]);
        }
        header('Location: ' . adminUrl('groups'));
        exit;

    default:
        $search = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT g.*,
            (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count,
            (SELECT COUNT(*) FROM course_access ca WHERE ca.group_id = g.id) AS course_count
            FROM member_groups g';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE g.name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY g.name ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        render('groups_list', ['groups' => $stmt->fetchAll(), 'search' => $search]);
}
