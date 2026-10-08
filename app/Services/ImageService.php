<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Secure image handling: re-encodes every upload (strips payloads / EXIF) and generates thumbnails. */
class ImageService
{
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private string $disk = 'public';

    /** @return array{path:string, thumb_path:string} */
    public function store(UploadedFile $file, string $directory, int $maxSize = 1400, int $thumbSize = 480): array
    {
        $info = @getimagesize($file->getRealPath());

        if (! $info || ! in_array($info['mime'], self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('ملف الصورة غير صالح.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source) {
            throw new RuntimeException('تعذر قراءة الصورة.');
        }

        $source = $this->applyOrientation($source, $file->getRealPath(), $info['mime']);

        try {
            return $this->storeGd($source, $directory, $maxSize, $thumbSize);
        } finally {
            imagedestroy($source);
        }
    }

    /** @return array{path:string, thumb_path:string} */
    public function storeGd(GdImage $source, string $directory, int $maxSize = 1400, int $thumbSize = 480): array
    {
        $directory = trim($directory, '/');
        $name = Str::random(32);
        $ext = function_exists('imagewebp') ? 'webp' : 'jpg';

        $path = "{$directory}/{$name}.{$ext}";
        $thumb = "{$directory}/thumbs/{$name}.{$ext}";

        $this->write($this->resize($source, $maxSize), $path, $ext, true);
        $this->write($this->resize($source, $thumbSize), $thumb, $ext, true);

        return ['path' => $path, 'thumb_path' => $thumb];
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_filter($paths);

        if ($paths) {
            Storage::disk($this->disk)->delete($paths);
        }
    }

    private function resize(GdImage $source, int $max): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min($max / $width, $max / $height, 1);

        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($newW, $newH);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

        return $target;
    }

    private function write(GdImage $image, string $path, string $ext, bool $destroy): void
    {
        ob_start();

        if ($ext === 'webp') {
            imagewebp($image, null, 82);
        } else {
            // JPEG has no alpha: flatten on white
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagejpeg($flat, null, 85);
            imagedestroy($flat);
        }

        $binary = ob_get_clean();

        if ($destroy) {
            imagedestroy($image);
        }

        Storage::disk($this->disk)->put($path, $binary);
    }

    private function applyOrientation(GdImage $image, string $file, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
