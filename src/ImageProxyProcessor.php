<?php declare(strict_types=1);

namespace MalteHuebner\ImageObfuscator;

use Symfony\Component\DomCrawler\Crawler;

class ImageProxyProcessor {

    private readonly string $cacheUrl;
    private readonly Cache $cache;
    private readonly Editor $editor;

    public function __construct() {
        $this->cacheUrl = site_url('/wp-content/cache/images/');

        $this->cache = new Cache();
        $this->cache->create();

        $this->editor = new Editor();
    }

    public function processContent(string $content) {
        $crawler = new Crawler($content);

        $crawler->filter('img')->each(function (Crawler $node) {
            $this->processImage($node);
        });

        return $crawler->html();
    }

    private function processImage(Crawler $node): void
    {
        $attachmentId = $this->getAttachmentId($node);

        if ($attachmentId) {
            $src = $node->attr('src');

            $size = $this->detectSize($src);

            $cacheFilename = $this->generateFilename($attachmentId, $size);
            $cacheFileUrl = $this->cacheUrl . $cacheFilename;

            if (!$this->cache->hasFile($cacheFilename)) {
                $this->editor->processAttachment($attachmentId, $size, $cacheFilename);
            }

            $node->getNode(0)->setAttribute('src', $cacheFileUrl);

            $this->updateSrcset($node, $attachmentId);
        }
    }

    private function getAttachmentId(Crawler $node): int
    {
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

    private function updateSrcset(Crawler $node, $attachmentId)
    {
        $srcset = $node->attr('srcset');

        if ($srcset) {
            $srcsetEntries = explode(',', $srcset);
            $updatedSrcset = [];

            foreach ($srcsetEntries as $entry) {
                $entry = trim($entry);
                if (preg_match('/^(.*?)-(\d+x\d+)\.(jpg|jpeg|png|gif)\s+(\d+w)$/', $entry, $entryMatch)) {
                    $entrySize = $entryMatch[2];
                    $entryWidth = $entryMatch[4];

                    $entryCacheFilename = $this->generateFilename($attachmentId, $entrySize);
                    $entryCachePath = $this->cache->generateFilename($entryCacheFilename);
                    $entryCacheUrl = $this->cacheUrl . $entryCacheFilename;

                    if (!file_exists($entryCachePath)) {
                        $this->editor->processAttachment($attachmentId, $entrySize, $entryCachePath);
                    }

                    $updatedSrcset[] = $entryCacheUrl . ' ' . $entryWidth;
                } else {
                    $updatedSrcset[] = $entry;
                }
            }

            $node->getNode(0)->setAttribute('srcset', implode(', ', $updatedSrcset));
        }
    }

    public function detectSize(string $src): string
    {
        $size = 'full';

        if (preg_match('/-(\d+x\d+)\.(jpg|jpeg|png|gif)$/', $src, $sizeMatch)) {
            $size = $sizeMatch[1];
        }

        return $size;
    }

    private function generateFilename(int $attachmentId, string $size): string
    {
        return sprintf('%d-%s.jpg', $attachmentId, $size);
    }
}
