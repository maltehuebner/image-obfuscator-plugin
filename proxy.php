<?php

require_once("../../../wp-load.php");

// ID aus der URL holen und validieren
$image_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$image_id) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid request.";
    exit;
}

// Bild-URL anhand der ID abrufen
$image_url = wp_get_attachment_url($image_id);

if (!$image_url) {
    header("HTTP/1.1 404 Not Found");
    echo "Image not found.";
    exit;
}

// Vollständigen Pfad des Bildes auf dem Server ermitteln
$image_path = ABSPATH . parse_url($image_url, PHP_URL_PATH);

if (!file_exists($image_path)) {
    header("HTTP/1.1 404 Not Found");
    echo "Image file not found.";
    exit;
}

// Header für Bildausgabe setzen
$image_info = getimagesize($image_path);
header("Content-Type: " . $image_info['mime']);
header("Content-Length: " . filesize($image_path));

// Bild ausgeben
readfile($image_path);
exit;
