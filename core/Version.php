<?php

final class Version
{
    public const CURRENT = '1.0.0';

    // Used to fetch release info/zips. Override in config/config.php under
    // ['updates']['repo'] if you fork this CMS under a different repo.
    public const DEFAULT_REPO = 'damienjustin/e-learning';

    public static function repo(): string
    {
        $updates = Config::get('updates', []);
        return $updates['repo'] ?? self::DEFAULT_REPO;
    }
}
