<?php

declare(strict_types=1);

switch ($action) {
    case 'create':
    case 'edit':
        $user = ['id' => null, 'name' => '', 'email' => '', 'role' => 'student', 'status' => 'active'];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) {
                http_response_code(404);
                exit('Utilisateur introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $role = in_array($_POST['role'] ?? '', ['admin', 'instructor', 'student'], true) ? $_POST['role'] : 'student';
                $status = in_array($_POST['status'] ?? '', ['active', 'suspended'], true) ? $_POST['status'] : 'active';
                $password = (string) ($_POST['password'] ?? '');

                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Nom et email valide requis.';
                }
                if ($action === 'create' && strlen($password) < 8) {
                    $errors[] = 'Mot de passe d\'au moins 8 caractères requis.';
                }

                if (!$errors) {
                    $dupStmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                    $dupStmt->execute([$email, (int) ($user['id'] ?? 0)]);
                    if ($dupStmt->fetch()) {
                        $errors[] = 'Cet email est déjà utilisé.';
                    }
                }

                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
                    } else {
                        if ($password !== '') {
                            if (strlen($password) < 8) {
                                $errors[] = 'Mot de passe d\'au moins 8 caractères requis.';
                            } else {
                                $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                            }
                        }
                        if (!$errors) {
                            $db->prepare('UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?')
                                ->execute([$name, $email, $role, $status, $user['id']]);
                        }
                    }

                    if (!$errors) {
                        header('Location: ' . adminUrl('users'));
                        exit;
                    }
                }
                $user = array_merge($user, compact('name', 'email', 'role', 'status'));
            }
        }

        render('users_form', ['user' => $user, 'errors' => $errors]);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id !== Auth::id()) {
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            }
        }
        header('Location: ' . adminUrl('users'));
        exit;

    default:
        $stmt = $db->query('SELECT * FROM users ORDER BY created_at DESC');
        render('users_list', ['users' => $stmt->fetchAll()]);
}
