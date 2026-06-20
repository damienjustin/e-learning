<?php

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $theme = Config::get('app')['theme'] ?? 'default';
        extract($data, EXTR_SKIP);
        $themePath = CMS_ROOT . '/themes/' . $theme . '/' . $template . '.php';
        $content = function () use ($themePath, $data) {
            extract($data, EXTR_SKIP);
            require $themePath;
        };

        require CMS_ROOT . '/themes/' . $theme . '/layout.php';
    }

    public static function partial(string $template, array $data = []): void
    {
        $theme = Config::get('app')['theme'] ?? 'default';
        extract($data, EXTR_SKIP);
        require CMS_ROOT . '/themes/' . $theme . '/' . $template . '.php';
    }
}
