<?php

/**
 * Applies versioned, additive-only SQL deltas from database/migrations/
 * so existing sites can be upgraded without touching their content.
 * Fresh installs run database/schema.sql instead and mark every
 * migration that already existed at install time as applied.
 */
final class Migrator
{
    public static function pending(PDO $db): array
    {
        $applied = self::appliedFilenames($db);
        $files = self::migrationFiles();
        return array_values(array_diff($files, $applied));
    }

    public static function runPending(PDO $db): array
    {
        $ran = [];
        foreach (self::pending($db) as $filename) {
            self::runFile($db, $filename);
            $ran[] = $filename;
        }
        return $ran;
    }

    public static function markAllAsApplied(PDO $db): void
    {
        $stmt = $db->prepare('INSERT IGNORE INTO migrations (filename) VALUES (?)');
        foreach (self::migrationFiles() as $filename) {
            $stmt->execute([$filename]);
        }
    }

    private static function runFile(PDO $db, string $filename): void
    {
        $sql = file_get_contents(CMS_ROOT . '/database/migrations/' . $filename);
        $db->beginTransaction();
        try {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $db->exec($statement);
            }
            $db->prepare('INSERT INTO migrations (filename) VALUES (?)')->execute([$filename]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function appliedFilenames(PDO $db): array
    {
        return array_column($db->query('SELECT filename FROM migrations')->fetchAll(), 'filename');
    }

    private static function migrationFiles(): array
    {
        $dir = CMS_ROOT . '/database/migrations';
        if (!is_dir($dir)) {
            return [];
        }
        $files = array_filter(scandir($dir) ?: [], fn ($f) => str_ends_with($f, '.sql'));
        sort($files);
        return array_values($files);
    }
}
