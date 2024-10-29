<?php declare(strict_types=1);

require_once('../../../wp-load.php');

$imageId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$imageId) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid request.';
    exit;
}

$imageUrl = wp_get_attachment_url($imageId);

if (!$imageUrl) {
    header('HTTP/1.1 404 Not Found');
    echo 'Image not found.';
    exit;
}

$imagePath = ABSPATH . parse_url($imageUrl, PHP_URL_PATH);

if (!file_exists($imagePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'Image file not found.';
    exit;
}

$imageInfo = getimagesize($imagePath);
header('Cache-Control: public, max-age=604800, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
header('Content-Type: ' . $imageInfo['mime']);
header('Content-Length: ' . filesize($imagePath));

readfile($imagePath);
exit;
