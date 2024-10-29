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

function mask_image_urls($content) {
    // Basis-URL für das Plugin-Verzeichnis
    $plugin_url = plugin_dir_url(__FILE__) . 'proxy.php?id=';

    return preg_replace_callback('/<img.*?src=["\']([^"\']+)["\'].*?wp-image-([0-9]+).*?>/i', function ($matches) use ($plugin_url) {
        $image_id = $matches[2]; // Bild-ID extrahieren
        $masked_url = $plugin_url . $image_id;
        return str_replace($matches[1], $masked_url, $matches[0]);
    }, $content);
}

add_filter('the_content', 'mask_image_urls');
