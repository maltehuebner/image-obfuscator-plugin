<?php declare(strict_types=1);

require_once('../../../wp-load.php');

$imageId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$imageId) {
    header('HTTP/s 400 Bad Request');
    echo 'Invalid request.';
    exit;
}

$imageUrl = wp_get_attachment_url($imageId);

if (!$imageUrl) {
    header('HTTP/s 404 Not Found');
    echo 'Image not found.';
    exit;
}

$imagePath = ABSPATH . parse_url($imageUrl, PHP_URL_PATH);
$cacheDir = WP_CONTENT_DIR . '/cache/imageproxy/';
$cachePath = $cacheDir . $imageId . '.jpg';

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

if (file_exists($cachePath)) {
    // Gecachtes Bild ausliefern
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    exit;
}

$imageInfo = getimagesize($imagePath);

switch ($imageInfo['mime']) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($imagePath);
        imagejpeg($image, $cachePath, 100);
        break;
    case 'image/png':
        $image = imagecreatefrompng($imagePath);
        imagepng($image, $cachePath);
        break;
    case 'image/gif':
        $image = imagecreatefromgif($imagePath);
        imagegif($image, $cachePath);
        break;
    default:
        header('HTTP/2 415 Unsupported Media Type');
        echo 'Unsupported image format.';
        exit;
}

imagedestroy($image);

header('Content-Type: ' . $imageInfo['mime']);
header('Content-Length: ' . filesize($cachePath));
readfile($cachePath);
exit;