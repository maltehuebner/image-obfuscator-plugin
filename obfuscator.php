<?php declare(strict_types=1);

/**
 * Image Obfuscator
 *
 * @wordpress-plugin
 * Plugin Name:       Image Obfuscator
 * Version:           1.0
 * Requires at least: 6.3
 * Requires PHP:      8.2
 * Author:            Malte Hübner
 * Author URI:        https://www.maltehuebner.de/
 * Text Domain:       image-obfuscator
 */

add_filter('the_content', 'replace_image_urls_with_cache');

function replace_image_urls_with_cache($content) {
    // Cache-Verzeichnis für modifizierte Bilder
    $cache_dir = WP_CONTENT_DIR . '/cache/imageproxy/';
    $cache_url = content_url('cache/imageproxy/');

    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // Bilder im Content finden
    return preg_replace_callback(
        '/<img\s+[^>]*>/i',
        function ($matches) use ($cache_dir, $cache_url) {
            $img_tag = $matches[0]; // Gesamter <img>-Tag

            // Attribute des <img>-Tags extrahieren
            preg_match_all('/(\w+)=["\']([^"\']+)["\']/', $img_tag, $attr_matches, PREG_SET_ORDER);

            $attributes = [];
            foreach ($attr_matches as $attr) {
                $attributes[strtolower($attr[1])] = $attr[2];
            }

            // `class`-Attribut auslesen und prüfen, ob `wp-image-XX` vorhanden ist
            if (isset($attributes['class']) && preg_match('/wp-image-(\d+)/', $attributes['class'], $id_match)) {
                $attachment_id = intval($id_match[1]);
            } else {
                return $img_tag; // Kein `wp-image-XX`, unverändert lassen
            }

            // `src`-Attribut auslesen
            $original_url = $attributes['src'] ?? null;
            if (!$original_url) {
                return $img_tag; // Kein `src`, unverändert lassen
            }

            // Bildgröße ermitteln (falls angegeben)
            $parsed_url = parse_url($original_url);
            $size = 'full'; // Standardgröße
            if (preg_match('/-(\d+x\d+)\.(jpg|jpeg|png|gif)$/', $parsed_url['path'], $size_match)) {
                $size = $size_match[1];
            }

            $cache_filename = $attachment_id . '-' . $size . '.jpg';
            $cache_path = $cache_dir . $cache_filename;
            $cache_file_url = $cache_url . $cache_filename;

            // Bild im Cache vorhanden?
            if (!file_exists($cache_path)) {
                create_cached_image($attachment_id, $size, $cache_path);
            }

            // Original-URL durch die Cache-URL ersetzen
            $attributes['src'] = $cache_file_url;

            // Neuen <img>-Tag generieren
            $new_img_tag = '<img';
            foreach ($attributes as $key => $value) {
                $new_img_tag .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }
            $new_img_tag .= '>';

            return $new_img_tag;
        },
        $content
    );




}

function create_cached_image($attachment_id, $size, $cache_path) {
    $image_src = wp_get_attachment_image_src($attachment_id, $size);

    if (!$image_src) {
        return; // Bild konnte nicht gefunden werden
    }

    $image_path = ABSPATH . parse_url($image_src[0], PHP_URL_PATH);

    if (!file_exists($image_path)) {
        return; // Originalbild existiert nicht
    }

    $image_info = getimagesize($image_path);
    switch ($image_info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($image_path);
            imagejpeg($image, $cache_path, 90); // Bild ohne EXIF-Daten speichern
            break;
        case 'image/png':
            $image = imagecreatefrompng($image_path);
            imagepng($image, $cache_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($image_path);
            imagegif($image, $cache_path);
            break;
        default:
            return; // Nicht unterstütztes Format
    }

    // Speicher freigeben
    imagedestroy($image);
}


