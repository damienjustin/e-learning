<?php

declare(strict_types=1);

define('CMS_INSTALLING', true);
define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/bootstrap.php';

$configPath = CMS_ROOT . '/config/config.php';

$step = (int) ($_GET['step'] ?? 1);

if (is_file($configPath) && !($step === 3 && !empty($_SESSION['_install_just_completed']))) {
    http_response_code(403);
    exit('Le CMS est déjà installé. Supprimez config/config.php pour relancer l\'installation.');
}

unset($_SESSION['_install_just_completed']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $errors[] = 'Jeton de sécurité invalide, veuillez recharger la page.';
    } else {
        $dbHost = trim((string) ($_POST['db_host'] ?? ''));
        $dbPort = trim((string) ($_POST['db_port'] ?? '3306'));
        $dbName = trim((string) ($_POST['db_name'] ?? ''));
        $dbUser = trim((string) ($_POST['db_user'] ?? ''));
        $dbPass = (string) ($_POST['db_pass'] ?? '');
        $siteName = trim((string) ($_POST['site_name'] ?? 'Bloomin LMS'));
        $adminName = trim((string) ($_POST['admin_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            $errors[] = 'Merci de renseigner les informations de connexion à la base de données.';
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            $errors[] = 'Le nom de la base ne doit contenir que des lettres, chiffres et underscores.';
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || $adminName === '') {
            $errors[] = 'Merci de renseigner un nom et un email valides pour le compte administrateur.';
        }
        if (strlen($adminPassword) < 8) {
            $errors[] = 'Le mot de passe administrateur doit contenir au moins 8 caractères.';
        }

        $pdo = null;
        if (!$errors) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort ?: '3306');
                $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");
            } catch (PDOException $e) {
                $errors[] = 'Connexion à la base de données impossible : ' . $e->getMessage();
            }
        }

        if (!$errors && $pdo) {
            try {
                $schema = file_get_contents(CMS_ROOT . '/database/schema.sql');
                foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
                    if ($statement !== '') {
                        $pdo->exec($statement);
                    }
                }

                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$adminEmail]);
                if (!$stmt->fetch()) {
                    $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
                        ->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'admin']);
                }

                // schema.sql already reflects every migration that existed at
                // install time, so mark them applied to avoid re-running deltas later.
                Migrator::markAllAsApplied($pdo);

                $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
                    ->execute(['cms_version', Version::CURRENT]);

                $config = [
                    'db' => [
                        'host' => $dbHost,
                        'port' => $dbPort ?: '3306',
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass,
                        'charset' => 'utf8mb4',
                    ],
                    'app' => [
                        'name' => $siteName,
                        'url' => (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
                        'theme' => 'default',
                        'debug' => false,
                        'key' => bin2hex(random_bytes(32)),
                    ],
                ];

                $php = "<?php\n\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($configPath, $php) === false) {
                    $errors[] = 'Impossible d\'écrire config/config.php. Vérifiez les droits du dossier config/.';
                } else {
                    $_SESSION['_install_just_completed'] = true;
                    header('Location: /install/?step=3');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors de la création des tables : ' . $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation &middot; Bloomin LMS</title>
    <link rel="stylesheet" href="/install/assets/install.css">
</head>
<body>
<div class="install-box">
    <h1>Installation de Bloomin LMS</h1>

    <?php foreach ($errors as $err): ?>
        <div class="alert"><?= Security::e($err) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 3): ?>
        <h2>✅ Installation terminée</h2>
        <p>Votre CMS est prêt. Connectez-vous avec le compte administrateur que vous venez de créer.</p>
        <p><strong>Important :</strong> pour des raisons de sécurité, supprimez ou protégez le dossier <code>install/</code> maintenant.</p>
        <a class="btn" href="/login">Se connecter</a>
    <?php else: ?>
        <form method="post" action="/install/?step=2">
            <?= Security::csrfField() ?>
            <h2>Base de données</h2>
            <label>Hôte <input type="text" name="db_host" value="<?= Security::e($_POST['db_host'] ?? '127.0.0.1') ?>" required></label>
            <label>Port <input type="text" name="db_port" value="<?= Security::e($_POST['db_port'] ?? '3306') ?>"></label>
            <label>Nom de la base <input type="text" name="db_name" value="<?= Security::e($_POST['db_name'] ?? 'elearning_cms') ?>" required></label>
            <label>Utilisateur <input type="text" name="db_user" value="<?= Security::e($_POST['db_user'] ?? '') ?>" required></label>
            <label>Mot de passe <input type="password" name="db_pass"></label>

            <h2>Site</h2>
            <label>Nom du site <input type="text" name="site_name" value="<?= Security::e($_POST['site_name'] ?? 'Bloomin LMS') ?>" required></label>

            <h2>Compte administrateur</h2>
            <label>Nom <input type="text" name="admin_name" value="<?= Security::e($_POST['admin_name'] ?? '') ?>" required></label>
            <label>Email <input type="email" name="admin_email" value="<?= Security::e($_POST['admin_email'] ?? '') ?>" required></label>
            <label>Mot de passe (8 caractères minimum) <input type="password" name="admin_password" minlength="8" required></label>

            <button class="btn" type="submit">Installer</button>
        </form>
    <?php endif; ?>
    <p class="install-credit">&copy; <?= date('Y') ?> <a href="https://bloomin.agency" target="_blank" rel="noopener">Bloomin LMS</a></p>
</div>
</body>
</html>
