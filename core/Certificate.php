<?php

declare(strict_types=1);

final class Certificate
{
    private const WIDTH = 1200;
    private const HEIGHT = 850;
    private const FONT_REGULAR = CMS_ROOT . '/assets/fonts/CrimsonPro-Regular.ttf';
    private const FONT_BOLD = CMS_ROOT . '/assets/fonts/CrimsonPro-Bold.ttf';

    public static function defaultConfig(): array
    {
        return [
            'background_color' => '#fdfaf3',
            'border_color' => '#b08d57',
            'title' => 'Certificat de réussite',
            'title_color' => '#1a1a2e',
            'body' => "Ce certificat est décerné à\n{{student_name}}\npour avoir complété avec succès le cours\n{{course_title}}\nle {{date}}",
            'body_color' => '#333333',
            'footer' => 'Formateur : {{instructor_name}}',
            'footer_color' => '#555555',
            'logo_url' => '',
        ];
    }

    public static function applyPlaceholders(string $text, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }

        return strtr($text, $replacements);
    }

    /**
     * Renders the certificate as a PNG and returns the raw binary data.
     */
    public static function render(array $config, array $vars): string
    {
        $config = array_merge(self::defaultConfig(), $config);

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $bg = self::hexColor($image, $config['background_color']);
        imagefill($image, 0, 0, $bg);

        $border = self::hexColor($image, $config['border_color']);
        for ($i = 0; $i < 6; $i++) {
            imagerectangle($image, 30 + $i, 30 + $i, self::WIDTH - 31 - $i, self::HEIGHT - 31 - $i, $border);
        }

        if ($config['logo_url'] !== '') {
            $logoPath = CMS_ROOT . '/' . ltrim((string) parse_url($config['logo_url'], PHP_URL_PATH), '/');
            if (is_file($logoPath)) {
                $logo = @imagecreatefromstring((string) file_get_contents($logoPath));
                if ($logo !== false) {
                    $logoW = 140;
                    $logoH = (int) ($logoW * imagesy($logo) / imagesx($logo));
                    imagecopyresampled($image, $logo, (int) (self::WIDTH / 2 - $logoW / 2), 60, 0, 0, $logoW, $logoH, imagesx($logo), imagesy($logo));
                    imagedestroy($logo);
                }
            }
        }

        $titleColor = self::hexColor($image, $config['title_color']);
        self::drawCenteredText($image, self::applyPlaceholders($config['title'], $vars), 230, 44, self::FONT_BOLD, $titleColor);

        $bodyColor = self::hexColor($image, $config['body_color']);
        $bodyLines = explode("\n", self::applyPlaceholders($config['body'], $vars));
        $y = 340;
        foreach ($bodyLines as $line) {
            self::drawCenteredText($image, $line, $y, 28, self::FONT_REGULAR, $bodyColor);
            $y += 50;
        }

        $footerColor = self::hexColor($image, $config['footer_color']);
        self::drawCenteredText($image, self::applyPlaceholders($config['footer'], $vars), self::HEIGHT - 80, 20, self::FONT_REGULAR, $footerColor);

        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    private static function hexColor($image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            $hex = '000000';
        }
        [$r, $g, $b] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        return imagecolorallocate($image, $r, $g, $b);
    }

    private static function drawCenteredText($image, string $text, int $y, int $size, string $font, int $color): void
    {
        if ($text === '') {
            return;
        }
        $box = imagettfbbox($size, 0, $font, $text);
        $textWidth = $box[2] - $box[0];
        $x = (int) (self::WIDTH / 2 - $textWidth / 2);
        imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
    }
}
