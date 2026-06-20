<?php

final class Config
{
    private static ?array $data = null;

    private static function load(): array
    {
        if (self::$data === null) {
            $path = __DIR__ . '/../config/config.php';
            if (!is_file($path)) {
                self::redirectToInstaller();
            }
            self::$data = require $path;
        }

        return self::$data;
    }

    private static function redirectToInstaller(): void
    {
        if (!defined('CMS_INSTALLING')) {
            header('Location: /install/');
            exit;
        }
        self::$data = [];
    }

    public static function get(string $section, $default = null)
    {
        $data = self::load();
        return $data[$section] ?? $default;
    }
}
