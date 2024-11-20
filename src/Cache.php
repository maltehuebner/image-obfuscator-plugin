<?php declare(strict_types=1);

namespace MalteHuebner\ImageObfuscator;

class Cache
{
    private string $cacheDir = ABSPATH . 'wp-content/cache/images/';

    public function create(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function hasFile(string $filename): bool
    {
        return file_exists($this->generateFilename($filename));
    }

    public function generateFilename(string $filename): string
    {
        return $this->cacheDir . $filename;
    }
}
