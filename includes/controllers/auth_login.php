<?php

declare(strict_types=1);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'Jeton de sécurité invalide, veuillez réessayer.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Veuillez renseigner email et mot de passe.';
        } else {
            $result = Auth::attempt($email, $password);
            if ($result['ok']) {
                header('Location: /dashboard');
                exit;
            }
            $error = $result['error'];
        }
    }
}

View::render('auth_login', ['error' => $error]);
