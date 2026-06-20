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

    /**
     * MySQL/MariaDB DDL statements (ALTER/CREATE TABLE) auto-commit even
     * inside a transaction, so a migration that fails partway through can
     * leave some of its statements already applied. Re-running it would
     * then hit "already exists" errors forever, so those specific error
     * codes are tolerated to let a migration finish applying its
     * remaining (not-yet-applied) statements.
     */
    private const IGNORABLE_ERROR_CODES = ['42S21', '42S01'];

    private static function runFile(PDO $db, string $filename): void
    {
        $sql = file_get_contents(CMS_ROOT . '/database/migrations/' . $filename);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                if (!in_array($e->getCode(), self::IGNORABLE_ERROR_CODES, true)) {
                    throw $e;
                }
            }
        }
        $db->prepare('INSERT IGNORE INTO migrations (filename) VALUES (?)')->execute([$filename]);
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
