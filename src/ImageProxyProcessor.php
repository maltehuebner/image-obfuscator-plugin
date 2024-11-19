<?php declare(strict_types=1);

namespace MalteHuebner\ImageObfuscator;

use Symfony\Component\DomCrawler\Crawler;

class ImageProxyProcessor {
    private readonly string $cacheDir;
    private readonly string $cacheUrl;

    public function __construct() {
        $this->cacheDir = ABSPATH . 'wp-content/cache/images/';
        $this->cacheUrl = site_url('/wp-content/cache/images/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function processContent($content) {
        $crawler = new Crawler($content);

        $crawler->filter('img')->each(function (Crawler $node) {
            $attachmentId = $this->getAttachmentId($node);

            if ($attachmentId) {
                $src = $node->attr('src');
                $size = 'full';
                if (preg_match('/-(\d+x\d+)\.(jpg|jpeg|png|gif)$/', $src, $sizeMatch)) {
                    $size = $sizeMatch[1];
                }

                $cacheFilename = $attachmentId . '-' . $size . '.jpg';
                $cachePath = $this->cacheDir . $cacheFilename;
                $cacheFileUrl = $this->cacheUrl . $cacheFilename;

                if (!file_exists($cachePath)) {
                    $this->createCachedImage($attachmentId, $size, $cachePath);
                }

                $node->getNode(0)->setAttribute('src', $cacheFileUrl);

                $this->updateSrcset($node, $attachmentId);
            }
        });

        return $crawler->html();
    }

    private function getAttachmentId(Crawler $node) {
        $dataId = $node->attr('data-id');
        $attachmentId = null;

        if ($dataId && is_numeric($dataId)) {
            $attachmentId = intval($dataId);
        } else {
            $class = $node->attr('class');
            if ($class && preg_match('/wp-image-(\d+)/', $class, $matches)) {
                $attachmentId = intval($matches[1]);
            }
        }

        return $attachmentId;
    }

    private function updateSrcset(Crawler $node, $attachmentId) {
        $srcset = $node->attr('srcset');
        if ($srcset) {
            $srcsetEntries = explode(',', $srcset);
            $updatedSrcset = [];

            foreach ($srcsetEntries as $entry) {
                $entry = trim($entry);
                if (preg_match('/^(.*?)-(\d+x\d+)\.(jpg|jpeg|png|gif)\s+(\d+w)$/', $entry, $entryMatch)) {
                    $entrySize = $entryMatch[2];
                    $entryWidth = $entryMatch[4];

                    $entryCacheFilename = $attachmentId . '-' . $entrySize . '.jpg';
                    $entryCachePath = $this->cacheDir . $entryCacheFilename;
                    $entryCacheUrl = $this->cacheUrl . $entryCacheFilename;

                    if (!file_exists($entryCachePath)) {
                        $this->createCachedImage($attachmentId, $entrySize, $entryCachePath);
                    }

                    $updatedSrcset[] = $entryCacheUrl . ' ' . $entryWidth;
                } else {
                    $updatedSrcset[] = $entry;
                }
            }

            $node->getNode(0)->setAttribute('srcset', implode(', ', $updatedSrcset));
        }
    }

    private function createCachedImage($attachmentId, $size, $cachePath) {
        $filePath = get_attached_file($attachmentId);  // Originalbild aus der Mediathek holen

        if (!file_exists($filePath)) {
            return false;  // Das Bild existiert nicht
        }

        $imageEditor = wp_get_image_editor($filePath);
        if (is_wp_error($imageEditor)) {
            return false;  // Fehler beim Laden des Bildeditors
        }

        // EXIF-Daten entfernen
        $imageEditor->remove_exif();

        // Optional: Bildgröße auf den gewünschten Wert setzen
        if ($size !== 'full') {
            $imageEditor->resize($size[0], $size[1], true);
        }

        // Bild im Cache-Verzeichnis speichern
        $imageEditor->save($cachePath);

        return true;
    }
}
