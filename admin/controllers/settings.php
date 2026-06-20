<?php

declare(strict_types=1);

$configPath = CMS_ROOT . '/config/config.php';
$config = require $configPath;

$themes = array_values(array_filter(scandir(CMS_ROOT . '/themes') ?: [], function ($dir) {
    return $dir !== '.' && $dir !== '..' && is_dir(CMS_ROOT . '/themes/' . $dir);
}));

$errors = [];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $errors[] = 'Jeton de sécurité invalide.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $theme = (string) ($_POST['theme'] ?? 'default');

        if ($name === '') {
            $errors[] = 'Le nom du site est obligatoire.';
        }
        if (!in_array($theme, $themes, true)) {
            $errors[] = 'Thème invalide.';
        }

        if (!$errors) {
            $config['app']['name'] = $name;
            $config['app']['theme'] = $theme;

            $php = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($configPath, $php);
            $saved = true;
        }
    }
}

render('settings', ['config' => $config, 'themes' => $themes, 'errors' => $errors, 'saved' => $saved]);
