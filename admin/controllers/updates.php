<?php

declare(strict_types=1);

$stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = 'cms_version'");
$stmt->execute();
$installedVersion = $stmt->fetchColumn() ?: Version::CURRENT;

$release = null;
$checkError = null;
$updateResult = null;
$updateError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        $updateError = 'Jeton de sécurité invalide.';
    } elseif (($_POST['do'] ?? '') === 'update') {
        try {
            $release = Updater::checkLatestRelease();
            if (!Updater::isNewer($release['version']) || empty($release['zip_url'])) {
                $updateError = 'Aucune mise à jour à appliquer.';
            } else {
                $updateResult = Updater::applyUpdate($release['zip_url'], $release['version'], $db);
                $installedVersion = $release['version'];
            }
        } catch (Throwable $e) {
            $updateError = $e->getMessage();
        }
    }
}

if (!$release) {
    try {
        $release = Updater::checkLatestRelease();
    } catch (Throwable $e) {
        $checkError = $e->getMessage();
    }
}

render('updates', [
    'installedVersion' => $installedVersion,
    'release' => $release,
    'checkError' => $checkError,
    'updateResult' => $updateResult,
    'updateError' => $updateError,
]);
