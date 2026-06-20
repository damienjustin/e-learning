<?php

declare(strict_types=1);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'Jeton de sécurité invalide, veuillez réessayer.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Merci de renseigner un nom, un email valide et un mot de passe.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'student']);

                Auth::attempt($email, $password);
                header('Location: /dashboard');
                exit;
            }
        }
    }
}

View::render('auth_register', ['error' => $error]);
