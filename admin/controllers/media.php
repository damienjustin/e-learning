<?php

declare(strict_types=1);

$userId = Auth::id();
$uploadDir = dirname(__DIR__, 2) . '/uploads';

$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
    'application/pdf' => 'pdf',
];

switch ($action) {
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null) && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $mime = mime_content_type($file['tmp_name']) ?: '';
            if (isset($allowedMime[$mime]) && $file['size'] <= 8 * 1024 * 1024) {
                $ext = $allowedMime[$mime];
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                    $db->prepare('INSERT INTO media (filename, original_name, mime, size, uploaded_by) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$filename, basename($file['name']), $mime, $file['size'], $userId]);
                }
            }
        }
        header('Location: ' . adminUrl('media'));
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM media WHERE id = ?');
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if ($item) {
                $path = $uploadDir . '/' . $item['filename'];
                if (is_file($path)) {
                    unlink($path);
                }
                $db->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
            }
        }
        header('Location: ' . adminUrl('media'));
        exit;

    default:
        $items = $db->query('SELECT * FROM media ORDER BY created_at DESC')->fetchAll();
        render('media', ['items' => $items]);
}
