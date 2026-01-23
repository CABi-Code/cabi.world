<?php

declare(strict_types=1);

namespace App\Service;

class ImageService
{
    private array $config;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/app.php';
    }

    public function uploadAvatar(array $file, int $userId): ?array
    {
        $cfg = $this->config['uploads']['avatars'];
        
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $cfg['max_size']) {
            return null;
        }

        $ext = $this->getExtension($file['tmp_name']);
        if (!in_array($ext, ['jpg', 'png', 'gif', 'webp'])) {
            return null;
        }

        $baseDir = UPLOADS_PATH . '/avatars/' . $userId;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // Clear old avatars
        array_map('unlink', glob("$baseDir/*"));

        $filename = bin2hex(random_bytes(8));
        $paths = [];

        // Create different sizes
        foreach ($cfg['sizes'] as $sizeName => $size) {
            $path = "$baseDir/{$filename}_{$sizeName}.webp";
            
            if ($size === null) {
                // Original - just convert to webp
                $img = $this->loadImage($file['tmp_name']);
                imagewebp($img, $path, 90);
                imagedestroy($img);
            } else {
                $this->resizeAndSave($file['tmp_name'], $path, $size, $size);
            }
            
            $paths[$sizeName] = "/uploads/avatars/$userId/{$filename}_{$sizeName}.webp";
        }

        return $paths;
    }

    public function uploadBanner(array $file, int $userId): ?string
    {
        $cfg = $this->config['uploads']['banners'];
        
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $cfg['max_size']) {
            return null;
        }

        $ext = $this->getExtension($file['tmp_name']);
        if (!in_array($ext, ['jpg', 'png', 'gif', 'webp'])) {
            return null;
        }

        $baseDir = UPLOADS_PATH . '/banners/' . $userId;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // Clear old banners
        array_map('unlink', glob("$baseDir/*"));

        $filename = bin2hex(random_bytes(8)) . '.webp';
        $path = "$baseDir/$filename";

        $this->resizeAndSave($file['tmp_name'], $path, $cfg['width'], $cfg['height'], true);

        return "/uploads/banners/$userId/$filename";
    }

    public function cropAvatar(string $sourcePath, array $crop, int $userId): ?array
    {
        $cfg = $this->config['uploads']['avatars'];
        $fullPath = PUBLIC_PATH . $sourcePath;
        
        if (!file_exists($fullPath)) {
            return null;
        }

        $img = $this->loadImage($fullPath);
        
        // Crop
        $cropped = imagecrop($img, [
            'x' => (int) $crop['x'],
            'y' => (int) $crop['y'],
            'width' => (int) $crop['width'],
            'height' => (int) $crop['height']
        ]);
        imagedestroy($img);

        if (!$cropped) {
            return null;
        }

        $baseDir = UPLOADS_PATH . '/avatars/' . $userId;
        $filename = bin2hex(random_bytes(8));
        $paths = [];

        foreach ($cfg['sizes'] as $sizeName => $size) {
            $path = "$baseDir/{$filename}_{$sizeName}.webp";
            
            if ($size === null) {
                imagewebp($cropped, $path, 90);
            } else {
                $resized = imagescale($cropped, $size, $size, IMG_BICUBIC);
                imagewebp($resized, $path, 90);
                imagedestroy($resized);
            }
            
            $paths[$sizeName] = "/uploads/avatars/$userId/{$filename}_{$sizeName}.webp";
        }

        imagedestroy($cropped);
        return $paths;
    }

    private function loadImage(string $path)
    {
        $info = getimagesize($path);
        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null
        };
    }

    private function resizeAndSave(string $source, string $dest, int $width, int $height, bool $cover = false): void
    {
        $img = $this->loadImage($source);
        $srcW = imagesx($img);
        $srcH = imagesy($img);

        if ($cover) {
            // Cover - crop to fit exactly
            $ratio = max($width / $srcW, $height / $srcH);
            $newW = (int) ($srcW * $ratio);
            $newH = (int) ($srcH * $ratio);
            
            $resized = imagecreatetruecolor($width, $height);
            $scaled = imagescale($img, $newW, $newH, IMG_BICUBIC);
            
            $x = (int) (($newW - $width) / 2);
            $y = (int) (($newH - $height) / 2);
            
            imagecopy($resized, $scaled, 0, 0, $x, $y, $width, $height);
            imagedestroy($scaled);
        } else {
            // Contain - fit within bounds
            $ratio = min($width / $srcW, $height / $srcH);
            $newW = (int) ($srcW * $ratio);
            $newH = (int) ($srcH * $ratio);
            
            $resized = imagescale($img, $newW, $newH, IMG_BICUBIC);
        }

        imagewebp($resized, $dest, 90);
        imagedestroy($img);
        imagedestroy($resized);
    }

    private function getExtension(string $path): string
    {
        $info = getimagesize($path);
        return match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => ''
        };
    }
}
