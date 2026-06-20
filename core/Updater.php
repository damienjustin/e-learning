<?php

/**
 * Self-update mechanism, mirroring how WordPress core updates work:
 * only the CMS code is replaced (core/admin/includes/install/themes/default),
 * while config/, uploads/ and any custom theme the site added are left
 * untouched. Database changes only ever add columns/tables (see Migrator).
 */
final class Updater
{
    // Paths relative to CMS_ROOT that are safe to overwrite from a release.
    private const CORE_PATHS = [
        'core',
        'admin',
        'includes',
        'install',
        'database/schema.sql',
        'database/migrations',
        'themes/default',
        'index.php',
        '.htaccess',
    ];

    public static function checkLatestRelease(): array
    {
        $repo = Version::repo();
        $url = "https://api.github.com/repos/{$repo}/releases/latest";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['User-Agent: e-learning-cms-updater', 'Accept: application/vnd.github+json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new RuntimeException('Impossible de contacter GitHub : ' . ($error ?: "HTTP {$httpCode}"));
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['tag_name'])) {
            throw new RuntimeException('Réponse GitHub invalide.');
        }

        return [
            'version' => ltrim($data['tag_name'], 'v'),
            'changelog' => $data['body'] ?? '',
            'zip_url' => $data['zipball_url'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ];
    }

    public static function isNewer(string $remoteVersion): bool
    {
        return version_compare($remoteVersion, Version::CURRENT, '>');
    }

    /**
     * Downloads the release zip, overwrites only whitelisted core paths,
     * runs pending DB migrations, and records the new version. Returns
     * a short report of what happened.
     */
    public static function applyUpdate(string $zipUrl, string $newVersion, PDO $db): array
    {
        if (!is_writable(CMS_ROOT)) {
            throw new RuntimeException('Le dossier du CMS n\'est pas accessible en écriture.');
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'cms_update_') . '.zip';
        self::download($zipUrl, $tmpZip);

        $extractDir = sys_get_temp_dir() . '/cms_update_' . bin2hex(random_bytes(6));
        mkdir($extractDir, 0700, true);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            unlink($tmpZip);
            throw new RuntimeException('Impossible d\'ouvrir l\'archive téléchargée.');
        }
        $zip->extractTo($extractDir);
        $zip->close();
        unlink($tmpZip);

        // GitHub zipball archives wrap everything in a single top-level folder.
        $entries = array_values(array_diff(scandir($extractDir) ?: [], ['.', '..']));
        $sourceRoot = (count($entries) === 1 && is_dir($extractDir . '/' . $entries[0]))
            ? $extractDir . '/' . $entries[0]
            : $extractDir;

        $copied = [];
        foreach (self::CORE_PATHS as $relativePath) {
            $from = $sourceRoot . '/' . $relativePath;
            $to = CMS_ROOT . '/' . $relativePath;
            if (!file_exists($from)) {
                continue;
            }
            self::removePath($to);
            self::copyPath($from, $to);
            $copied[] = $relativePath;
        }

        self::deleteDirectory($extractDir);

        $migrationsRan = Migrator::runPending($db);

        $db->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
            ->execute(['cms_version', $newVersion]);

        return ['copied' => $copied, 'migrations' => $migrationsRan];
    }

    private static function download(string $url, string $destination): void
    {
        $fp = fopen($destination, 'w');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['User-Agent: e-learning-cms-updater'],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $httpCode >= 400) {
            unlink($destination);
            throw new RuntimeException('Téléchargement de la mise à jour impossible : ' . ($error ?: "HTTP {$httpCode}"));
        }
    }

    private static function copyPath(string $from, string $to): void
    {
        if (is_dir($from)) {
            mkdir($to, 0755, true);
            foreach (array_diff(scandir($from) ?: [], ['.', '..']) as $item) {
                self::copyPath($from . '/' . $item, $to . '/' . $item);
            }
            return;
        }
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0755, true);
        }
        copy($from, $to);
    }

    private static function removePath(string $path): void
    {
        if (is_dir($path)) {
            self::deleteDirectory($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? self::deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
