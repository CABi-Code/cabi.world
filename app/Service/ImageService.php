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

    public function uploadAvatar(array $file, int $userId, ?array $crop = null): ?array
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

        // Load and optionally crop image
        $img = $this->loadImage($file['tmp_name']);
        if (!$img) {
            return null;
        }
        
        // Apply crop if provided
        if ($crop && isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
            $cropped = imagecrop($img, [
                'x' => max(0, (int) $crop['x']),
                'y' => max(0, (int) $crop['y']),
                'width' => max(1, (int) $crop['width']),
                'height' => max(1, (int) $crop['height'])
            ]);
            if ($cropped) {
                imagedestroy($img);
                $img = $cropped;
            }
        }

        // Create different sizes
        foreach ($cfg['sizes'] as $sizeName => $size) {
            $path = "$baseDir/{$filename}_{$sizeName}.webp";
            
            if ($size === null) {
                // Original - just save as webp
                imagewebp($img, $path, 90);
            } else {
                // Resize to square
                $resized = imagescale($img, $size, $size, IMG_BICUBIC);
                imagewebp($resized, $path, 90);
                imagedestroy($resized);
            }
            
            $paths[$sizeName] = "/uploads/avatars/$userId/{$filename}_{$sizeName}.webp";
        }

        imagedestroy($img);
        return $paths;
    }

	/**
	 * Загружает файл изображения в указанный контекст (например, 'chat')
	 * Возвращает относительный путь к файлу (или null при ошибке)
	 *
	 * @param string $tmpPath Временный путь загруженного файла ($_FILES['tmp_name'])
	 * @param string $context Контекст загрузки ('chat', 'post', 'profile' и т.д.)
	 * @return string|null Относительный путь, например: /uploads/chat/2025/01/abc123.webp
	 */
	public function uploadFile(string $tmpPath, string $context = 'file'): ?string
	{
		$cfg = $this->config['uploads']['images'] ?? $this->config['uploads']['default'] ?? [
			'max_size' => 5 * 1024 * 1024,      // 5 МБ по умолчанию
			'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
			'quality' => 85,
			'path_prefix' => '/uploads/',
			'storage_dir' => UPLOADS_PATH . '/' . $context,
		];

		// 1. Проверка ошибок загрузки и размера
		if (!file_exists($tmpPath) || !is_uploaded_file($tmpPath)) {
			json(['error' => 'Bad Request'], 400);
			return null;
		}

		$fileSize = filesize($tmpPath);
		if ($fileSize > $cfg['max_size']) {
			json(['error' => 'Payload Too Large'], 413);
			return null;
		}

		// 2. Проверка расширения (на всякий случай, хотя mime уже проверен)
		$ext = $this->getExtension($tmpPath);
		if (!in_array($ext, $cfg['allowed_ext'])) {
			json(['error' => 'Unsupported Media Type'], 415);
			return null;
		}

		// 3. Определяем поддиректорию по контексту и дате (для лучшей организации)
		$datePath = date('Y/m/d');
		$baseDir = $cfg['storage_dir'] . '/' . $datePath;

		if (!is_dir($baseDir)) {
			mkdir($baseDir, 0755, true);
		}

		// 4. Генерируем уникальное имя
		$filename = bin2hex(random_bytes(10)) . '.webp'; // 20 символов hex + .webp

		$fullPath = $baseDir . '/' . $filename;
		$relativePath = $cfg['path_prefix'] . $context . '/' . $datePath . '/' . $filename;

		// 5. Загружаем и конвертируем в webp
		$img = $this->loadImage($tmpPath);
		if (!$img) {
			json(['error' => 'Internal Server Error'], 500);
			return null;
		}

		// Опционально: можно добавить ресайз, если нужно (например, max 1920×1080)
		// $img = $this->resizeIfNeeded($img, 1920, 1080);

		// Сохраняем в webp
		$success = imagewebp($img, $fullPath, $cfg['quality'] ?? 85);

		imagedestroy($img);

		if (!$success) {
			return null;
		}

		return $relativePath;
	}

    public function uploadBanner(array $file, int $userId, ?array $crop = null): ?string
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

        // Load image
        $img = $this->loadImage($file['tmp_name']);
        if (!$img) {
            return null;
        }
        
        // Apply crop if provided
        if ($crop && isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
            $cropped = imagecrop($img, [
                'x' => max(0, (int) $crop['x']),
                'y' => max(0, (int) $crop['y']),
                'width' => max(1, (int) $crop['width']),
                'height' => max(1, (int) $crop['height'])
            ]);
            if ($cropped) {
                imagedestroy($img);
                $img = $cropped;
            }
        }
        
        // Resize to banner dimensions maintaining aspect ratio from crop
        $this->resizeAndSaveBanner($img, $path, $cfg['width'], $cfg['height']);
        imagedestroy($img);

        return "/uploads/banners/$userId/$filename";
    }

    private function resizeAndSaveBanner($img, string $dest, int $width, int $height): void
    {
        $srcW = imagesx($img);
        $srcH = imagesy($img);
        
        // Calculate new dimensions while maintaining aspect ratio
        $ratio = $srcW / $srcH;
        $targetRatio = $width / $height;
        
        if ($ratio > $targetRatio) {
            // Image is wider - scale by height
            $newH = $height;
            $newW = (int) ($height * $ratio);
        } else {
            // Image is taller - scale by width
            $newW = $width;
            $newH = (int) ($width / $ratio);
        }
        
        // Scale image
        $scaled = imagescale($img, $newW, $newH, IMG_BICUBIC);
        
        // Create final canvas and center the scaled image
        $final = imagecreatetruecolor($width, $height);
        
        // Calculate position to center
        $x = (int) (($width - $newW) / 2);
        $y = (int) (($height - $newH) / 2);
        
        // Fill with black background
        $black = imagecolorallocate($final, 0, 0, 0);
        imagefill($final, 0, 0, $black);
        
        // Copy scaled image onto final canvas
        imagecopy($final, $scaled, $x, $y, 0, 0, $newW, $newH);
        
        imagewebp($final, $dest, 90);
        
        imagedestroy($scaled);
        imagedestroy($final);
    }

    public function uploadApplicationImage(array $file, int $applicationId): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $ext = $this->getExtension($file['tmp_name']);
        if (!in_array($ext, ['jpg', 'png', 'gif', 'webp'])) {
            return null;
        }

        $baseDir = UPLOADS_PATH . '/applications/' . $applicationId;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(8)) . '.webp';
        $path = "$baseDir/$filename";

        $img = $this->loadImage($file['tmp_name']);
        if (!$img) {
            return null;
        }

        // Resize if too large
        $maxDim = 1200;
        $srcW = imagesx($img);
        $srcH = imagesy($img);
        
        if ($srcW > $maxDim || $srcH > $maxDim) {
            $ratio = min($maxDim / $srcW, $maxDim / $srcH);
            $newW = (int) ($srcW * $ratio);
            $newH = (int) ($srcH * $ratio);
            $resized = imagescale($img, $newW, $newH, IMG_BICUBIC);
            imagedestroy($img);
            $img = $resized;
        }

        imagewebp($img, $path, 90);
        imagedestroy($img);

        return "/uploads/applications/$applicationId/$filename";
    }

    private function loadImage(string $path)
    {
        $info = getimagesize($path);
        if (!$info) return null;
        
        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null
        };
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
