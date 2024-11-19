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

use MalteHuebner\ImageObfuscator\ImageProxyProcessor;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('the_content', 'replaceImageUrlsInContent');

function replaceImageUrlsInContent($content) {
    $imageProxyProcessor = new ImageProxyProcessor();

    return $imageProxyProcessor->processContent($content);
}
