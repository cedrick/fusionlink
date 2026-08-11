<?php

class CmsImageService
{
    public const PRESETS = [
        'website_logo' => [
            'label' => 'Website logo',
            'hint' => 'Displayed in the page header (max 400×120px). PNG keeps transparency.',
            'width' => 400,
            'height' => 120,
            'mode' => 'fit',
            'format' => 'png',
            'quality' => 90,
        ],
        'website_favicon' => [
            'label' => 'Favicon',
            'hint' => 'Browser tab icon (64×64px square).',
            'width' => 64,
            'height' => 64,
            'mode' => 'cover',
            'format' => 'png',
            'quality' => 90,
        ],
        'hero_image' => [
            'label' => 'Hero image',
            'hint' => 'Homepage hero panel (1400×800px, fills the frame edge to edge).',
            'width' => 1400,
            'height' => 800,
            'mode' => 'cover',
            'format' => 'jpg',
            'quality' => 88,
        ],
        'about_image' => [
            'label' => 'About image',
            'hint' => 'About section image (900×600px, fills the frame edge to edge).',
            'width' => 900,
            'height' => 600,
            'mode' => 'cover',
            'format' => 'jpg',
            'quality' => 88,
        ],
    ];

    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../public/uploads/cms';

        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true) && !is_dir($this->uploadDir)) {
                throw new RuntimeException('Unable to create CMS upload directory.');
            }
        }

        if (!is_writable($this->uploadDir)) {
            @chmod($this->uploadDir, 0777);
        }

        if (!is_writable($this->uploadDir)) {
            throw new RuntimeException(
                'CMS upload folder is not writable by the web server. '
                . 'Run: chmod 777 public/uploads/cms (or chown www-data on that folder).'
            );
        }
    }

    public function preset(string $field): array
    {
        if (!isset(self::PRESETS[$field])) {
            throw new InvalidArgumentException('Unknown CMS media field: ' . $field);
        }

        return self::PRESETS[$field];
    }

    public function processUpload(array $file, string $field): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        $maxBytes = 8 * 1024 * 1024;
        if ((int)($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Image is too large. Maximum upload size is 8 MB.');
        }

        $source = $this->loadImage($tmpName);
        if (!$source) {
            throw new RuntimeException('Unsupported image type. Use JPG, PNG, or WebP.');
        }

        $preset = $this->preset($field);
        $processed = $this->resizeImage(
            $source,
            (int)$preset['width'],
            (int)$preset['height'],
            (string)$preset['mode'],
            (string)$preset['format']
        );
        imagedestroy($source);

        if (!$processed) {
            throw new RuntimeException('Unable to process the uploaded image.');
        }

        $filename = $field . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $preset['format'];
        $targetPath = rtrim($this->uploadDir, '/') . '/' . $filename;

        if (!is_writable($this->uploadDir)) {
            imagedestroy($processed);
            throw new RuntimeException(
                'CMS upload folder is not writable. Ask your server admin to allow www-data to write to public/uploads/cms.'
            );
        }

        if (!$this->saveImage($processed, $targetPath, (string)$preset['format'], (int)$preset['quality'])) {
            imagedestroy($processed);
            $lastError = error_get_last();
            $details = is_array($lastError) ? (string)($lastError['message'] ?? '') : '';
            throw new RuntimeException(
                'Unable to save the processed image.'
                . ($details !== '' ? ' ' . $details : '')
            );
        }

        imagedestroy($processed);

        return '/uploads/cms/' . $filename;
    }

    public function deleteStoredFile(?string $publicPath): void
    {
        $publicPath = trim((string)$publicPath);
        if ($publicPath === '' || !str_starts_with($publicPath, '/uploads/cms/')) {
            return;
        }

        $fullPath = __DIR__ . '/../../public' . $publicPath;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds the allowed size.',
            UPLOAD_ERR_PARTIAL => 'The upload did not complete. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'Image upload failed.',
        };
    }

    private function loadImage(string $path): GdImage|false
    {
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => false,
        };
    }

    private function resizeImage(GdImage $source, int $targetW, int $targetH, string $mode, string $format): GdImage|false
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW <= 0 || $srcH <= 0) {
            return false;
        }

        if ($mode === 'cover') {
            $scale = max($targetW / $srcW, $targetH / $srcH);
            $resizeW = (int)ceil($srcW * $scale);
            $resizeH = (int)ceil($srcH * $scale);
            $canvas = imagecreatetruecolor($targetW, $targetH);
            $this->preserveTransparency($canvas, $format);
            $tmp = imagecreatetruecolor($resizeW, $resizeH);
            $this->preserveTransparency($tmp, $format);
            imagecopyresampled($tmp, $source, 0, 0, 0, 0, $resizeW, $resizeH, $srcW, $srcH);
            $cropX = (int)(($resizeW - $targetW) / 2);
            $cropY = (int)(($resizeH - $targetH) / 2);
            imagecopy($canvas, $tmp, 0, 0, $cropX, $cropY, $targetW, $targetH);
            imagedestroy($tmp);
            return $canvas;
        }

        $scale = min($targetW / $srcW, $targetH / $srcH, 1);
        $newW = max(1, (int)round($srcW * $scale));
        $newH = max(1, (int)round($srcH * $scale));
        $canvas = imagecreatetruecolor($targetW, $targetH);
        $this->preserveTransparency($canvas, $format);
        $offsetX = (int)(($targetW - $newW) / 2);
        $offsetY = (int)(($targetH - $newH) / 2);
        imagecopyresampled($canvas, $source, $offsetX, $offsetY, 0, 0, $newW, $newH, $srcW, $srcH);

        return $canvas;
    }

    private function preserveTransparency(GdImage $image, string $format): void
    {
        if ($format === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
            imagealphablending($image, true);
            return;
        }

        // Match the public page background so letterboxing is invisible.
        $bg = imagecolorallocate($image, 13, 13, 16);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $bg);
    }

    private function saveImage(GdImage $image, string $path, string $format, int $quality): bool
    {
        return match ($format) {
            'png' => imagepng($image, $path, 6),
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            default => false,
        };
    }
}
