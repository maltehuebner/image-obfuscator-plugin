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
    $pluginUrl = plugin_dir_url(__FILE__) . 'proxy.php?id=';

    return preg_replace_callback('/<img.*?src=["\']([^"\']+)["\'].*?wp-image-([0-9]+).*?>/i', function ($matches) use ($pluginUrl) {
        $imageId = $matches[2];
        $maskedUrl = $pluginUrl . $imageId;
        return str_replace($matches[1], $maskedUrl, $matches[0]);
    }, $content);
}

add_filter('the_content', 'mask_image_urls');
