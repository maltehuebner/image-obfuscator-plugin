<?php declare(strict_types=1);

namespace MalteHuebner\ImageObfuscator;

class Editor
{
    public function processAttachment(int $attachmentId, string $size, string $cacheFilename): bool
    {
        $filePath = get_attached_file($attachmentId);

        if (!file_exists($filePath)) {
            return false;
        }

        $imageEditor = wp_get_image_editor($filePath);

        if (is_wp_error($imageEditor)) {
            return false;
        }

        $imageEditor->remove_exif();

        if ($size !== 'full') {
            $imageEditor->resize($size[0], $size[1], true);
        }

        $imageEditor->save($this->cache->generateFilename($cacheFilename));

        return true;
    }
}