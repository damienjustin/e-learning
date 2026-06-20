<?php
/**
 * Minimal WordPress-style hook system so themes/plugins can extend
 * the CMS without modifying core files.
 */
final class Hooks
{
    private static array $actions = [];
    private static array $filters = [];

    public static function addAction(string $name, callable $callback, int $priority = 10): void
    {
        self::$actions[$name][$priority][] = $callback;
    }

    public static function doAction(string $name, ...$args): void
    {
        if (empty(self::$actions[$name])) {
            return;
        }
        ksort(self::$actions[$name]);
        foreach (self::$actions[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public static function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        self::$filters[$name][$priority][] = $callback;
    }

    public static function applyFilters(string $name, $value, ...$args)
    {
        if (empty(self::$filters[$name])) {
            return $value;
        }
        ksort(self::$filters[$name]);
        foreach (self::$filters[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }
        return $value;
    }
}
