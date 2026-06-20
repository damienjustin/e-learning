<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('UTC');

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

spl_autoload_register(function (string $class): void {
    $path = CMS_ROOT . '/core/' . $class . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require_once CMS_ROOT . '/core/Config.php';

Security::sendHeaders();
Security::startSession();

if (!defined('CMS_INSTALLING')) {
    $appConfig = Config::get('app', []);
    if (!empty($appConfig['debug'])) {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }
}
