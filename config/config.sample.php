<?php
/**
 * Copy this file to config/config.php and fill in your values.
 * config/config.php is git-ignored and created automatically by the installer.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'elearning_cms',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Bloomin LMS',
        'url' => 'http://localhost',
        'theme' => 'default',
        'debug' => false,
        // Generated automatically by the installer, used for CSRF/session signing.
        'key' => '',
    ],
    'updates' => [
        // Optional: a GitHub Personal Access Token (no scopes needed for a
        // public repo) raises the API rate limit from 60 to 5000 req/hour.
        // Generate one at https://github.com/settings/tokens
        'github_token' => '',
    ],
];
