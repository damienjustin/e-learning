<?php

/**
 * Block-based content builder (paragraph/heading/image/video/quote/list/
 * code/divider/button), used by both course descriptions and lesson
 * content. Blocks are stored as JSON and only ever rendered through
 * render(), so user input never reaches the page as raw HTML.
 */
final class Blocks
{
    private const TYPES = ['paragraph', 'heading', 'image', 'video', 'quote', 'list', 'code', 'divider', 'button'];

    public static function decode(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Sanitizes a raw decoded blocks array coming from the builder's
     * hidden input before it's stored.
     */
    public static function sanitize(array $blocks): array
    {
        $clean = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? '', self::TYPES, true)) {
                continue;
            }
            $type = $block['type'];
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $clean[] = match ($type) {
                'paragraph', 'quote' => ['type' => $type, 'data' => [
                    'text' => trim((string) ($data['text'] ?? '')),
                ]],
                'heading' => ['type' => $type, 'data' => [
                    'text' => trim((string) ($data['text'] ?? '')),
                    'level' => in_array((int) ($data['level'] ?? 2), [2, 3, 4], true) ? (int) $data['level'] : 2,
                ]],
                'image' => ['type' => $type, 'data' => [
                    'url' => trim((string) ($data['url'] ?? '')),
                    'alt' => trim((string) ($data['alt'] ?? '')),
                    'caption' => trim((string) ($data['caption'] ?? '')),
                ]],
                'video' => ['type' => $type, 'data' => [
                    'url' => trim((string) ($data['url'] ?? '')),
                ]],
                'list' => ['type' => $type, 'data' => [
                    'ordered' => !empty($data['ordered']),
                    'items' => array_values(array_filter(array_map(
                        fn ($i) => trim((string) $i),
                        is_array($data['items'] ?? null) ? $data['items'] : []
                    ), fn ($i) => $i !== '')),
                ]],
                'code' => ['type' => $type, 'data' => [
                    'code' => (string) ($data['code'] ?? ''),
                    'language' => trim((string) ($data['language'] ?? '')),
                ]],
                'button' => ['type' => $type, 'data' => [
                    'label' => trim((string) ($data['label'] ?? '')),
                    'url' => trim((string) ($data['url'] ?? '')),
                ]],
                'divider' => ['type' => $type, 'data' => []],
                default => null,
            };
        }
        return array_values(array_filter($clean));
    }

    public static function render(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];
            $html .= match ($type) {
                'paragraph' => '<p>' . nl2br(Security::e($data['text'] ?? '')) . '</p>',
                'quote' => '<blockquote>' . nl2br(Security::e($data['text'] ?? '')) . '</blockquote>',
                'heading' => self::renderHeading($data),
                'image' => self::renderImage($data),
                'video' => self::renderVideo($data),
                'list' => self::renderList($data),
                'code' => '<pre><code>' . Security::e($data['code'] ?? '') . '</code></pre>',
                'button' => self::renderButton($data),
                'divider' => '<hr>',
                default => '',
            };
        }
        return $html;
    }

    private static function renderHeading(array $data): string
    {
        $level = in_array((int) ($data['level'] ?? 2), [2, 3, 4], true) ? (int) $data['level'] : 2;
        return "<h{$level}>" . Security::e($data['text'] ?? '') . "</h{$level}>";
    }

    private static function renderImage(array $data): string
    {
        if (empty($data['url'])) {
            return '';
        }
        $caption = trim((string) ($data['caption'] ?? ''));
        $html = '<figure><img src="' . Security::e($data['url']) . '" alt="' . Security::e($data['alt'] ?? '') . '" loading="lazy">';
        if ($caption !== '') {
            $html .= '<figcaption>' . Security::e($caption) . '</figcaption>';
        }
        return $html . '</figure>';
    }

    private static function renderVideo(array $data): string
    {
        $url = trim((string) ($data['url'] ?? ''));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        return '<div class="block-video"><iframe src="' . Security::e($url) . '" loading="lazy" allowfullscreen frameborder="0"></iframe></div>';
    }

    private static function renderList(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if (!$items) {
            return '';
        }
        $tag = !empty($data['ordered']) ? 'ol' : 'ul';
        $html = "<{$tag}>";
        foreach ($items as $item) {
            $html .= '<li>' . Security::e($item) . '</li>';
        }
        return $html . "</{$tag}>";
    }

    private static function renderButton(array $data): string
    {
        $label = trim((string) ($data['label'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        if ($label === '' || $url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        return '<p><a class="block-button" href="' . Security::e($url) . '">' . Security::e($label) . '</a></p>';
    }
}
