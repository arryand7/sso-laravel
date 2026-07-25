<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class UserPhotoService
{
    /**
     * Canvas dimensions (4:3 ratio).
     */
    protected const CANVAS_RESOLUTIONS = [
        [800, 600],
        [640, 480],
    ];

    protected const MAX_FILE_SIZE = 307200; // ~300 KB

    protected const QUALITY_START = 82;

    protected const QUALITY_MIN = 45;

    protected const QUALITY_STEP = 8;

    protected const MAX_SOURCE_DIMENSION = 8000; // decompression bomb guard

    protected const MAX_UPLOAD_BYTES = 10485760; // 10 MB

    protected const BACKGROUND_COLOR = '#ffffff';

    protected const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new GdDriver);
    }

    /**
     * Process and store a profile photo for a user.
     *
     * Returns the storage path on success, null on failure.
     */
    public function store(User $user, UploadedFile $file): string
    {
        $this->validateUpload($file);

        $image = $this->readImage($file);
        $image = $this->fixOrientation($image);
        $this->guardDecompressionBomb($image);

        // Remove EXIF by re-encoding
        $image = $this->stripMetadata($image);

        // Create 4:3 canvas with contain method
        $result = $this->containOnCanvas($image);

        // Adaptive compression
        $encoded = $this->adaptiveCompress($result);

        // Store to disk
        $path = $this->storeToDisk($user, $encoded);

        // Update database
        $oldPath = $user->photo_path;
        $user->update(['photo_path' => $path]);

        // Clean up old file after DB success
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    /**
     * Remove a user's profile photo.
     */
    public function destroy(User $user): void
    {
        $oldPath = $user->photo_path;

        $user->update(['photo_path' => null]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    /**
     * Validate the uploaded file before processing.
     */
    protected function validateUpload(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new \InvalidArgumentException('Ukuran file melebihi batas 10 MB.');
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP.');
        }

        // Verify it's actually a readable image
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('File rusak atau bukan gambar yang valid.');
        }
    }

    /**
     * Read the uploaded file into an Intervention Image instance.
     */
    protected function readImage(UploadedFile $file): ImageInterface
    {
        try {
            return $this->manager->read($file->getPathname());
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Gagal membaca file gambar: '.$e->getMessage());
        }
    }

    /**
     * Auto-orient the image based on EXIF data.
     */
    protected function fixOrientation(ImageInterface $image): ImageInterface
    {
        return $image->orient();
    }

    /**
     * Guard against decompression bomb attacks.
     */
    protected function guardDecompressionBomb(ImageInterface $image): void
    {
        $width = $image->width();
        $height = $image->height();

        if ($width > self::MAX_SOURCE_DIMENSION || $height > self::MAX_SOURCE_DIMENSION) {
            throw new \InvalidArgumentException(
                "Resolusi gambar terlalu besar ({$width}×{$height}). Maksimal ".self::MAX_SOURCE_DIMENSION.' piksel per sisi.'
            );
        }

        // Guard pixel count (max ~64 megapixels)
        if ($width * $height > 64_000_000) {
            throw new \InvalidArgumentException('Total piksel gambar terlalu besar. Gunakan gambar dengan resolusi lebih kecil.');
        }
    }

    /**
     * Strip EXIF and metadata by re-encoding through GD.
     */
    protected function stripMetadata(ImageInterface $image): ImageInterface
    {
        // Re-encode as PNG (lossless) to strip EXIF, then re-read
        $encoded = $image->toPng();

        return $this->manager->read($encoded->toString());
    }

    /**
     * Place the image on a 4:3 canvas using the contain method.
     *
     * The image is scaled to fit entirely within the canvas without
     * cropping or stretching, then centered. Empty areas are filled
     * with a solid background color.
     */
    protected function containOnCanvas(ImageInterface $image): ImageInterface
    {
        $sourceWidth = $image->width();
        $sourceHeight = $image->height();

        [$canvasWidth, $canvasHeight] = self::CANVAS_RESOLUTIONS[0]; // 800×600

        // Scale factor using min() — contain, not cover
        $scale = min(
            $canvasWidth / $sourceWidth,
            $canvasHeight / $sourceHeight
        );

        // For small images, don't upscale aggressively
        if ($scale > 1.5) {
            $scale = min($scale, 1.5);
        }

        $targetWidth = (int) round($sourceWidth * $scale);
        $targetHeight = (int) round($sourceHeight * $scale);

        // Resize the source image
        $image = $image->resize($targetWidth, $targetHeight);

        // Create canvas with background
        $canvas = $this->manager->create($canvasWidth, $canvasHeight)
            ->fill(self::BACKGROUND_COLOR);

        // Calculate centered position
        $x = (int) round(($canvasWidth - $targetWidth) / 2);
        $y = (int) round(($canvasHeight - $targetHeight) / 2);

        // Place the image on canvas
        $canvas = $canvas->place($image, 'top-left', $x, $y);

        return $canvas;
    }

    /**
     * Adaptively compress the image to stay within the file size limit.
     *
     * Tries WebP first (better compression), falls back to JPEG.
     * Reduces quality iteratively, then resolution if needed.
     */
    protected function adaptiveCompress(ImageInterface $image): string
    {
        // Try WebP first
        $encoded = $this->tryCompressWebP($image);
        if ($encoded !== null) {
            return $encoded;
        }

        // Fallback: try lower resolutions
        foreach (array_slice(self::CANVAS_RESOLUTIONS, 1) as [$width, $height]) {
            $resized = $image->resize($width, $height);
            $encoded = $this->tryCompressWebP($resized);
            if ($encoded !== null) {
                return $encoded;
            }
        }

        // Last resort: JPEG at lowest quality at smallest resolution
        $smallest = self::CANVAS_RESOLUTIONS[count(self::CANVAS_RESOLUTIONS) - 1];
        $resized = $image->resize($smallest[0], $smallest[1]);

        return $resized->toJpeg(self::QUALITY_MIN)->toString();
    }

    /**
     * Try compressing as WebP at various quality levels.
     */
    protected function tryCompressWebP(ImageInterface $image): ?string
    {
        $quality = self::QUALITY_START;

        while ($quality >= self::QUALITY_MIN) {
            $encoded = $image->toWebp($quality)->toString();

            if (strlen($encoded) <= self::MAX_FILE_SIZE) {
                return $encoded;
            }

            $quality -= self::QUALITY_STEP;
        }

        return null;
    }

    /**
     * Store the processed image to disk.
     */
    protected function storeToDisk(User $user, string $encodedData): string
    {
        $extension = $this->detectExtension($encodedData);
        $directory = 'users/'.$user->id;
        $filename = 'profile.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk('public')->put($path, $encodedData);

        return $path;
    }

    /**
     * Detect the file extension from encoded image data.
     */
    protected function detectExtension(string $data): string
    {
        // Check WebP signature: "RIFF....WEBP"
        if (str_starts_with($data, 'RIFF') && substr($data, 8, 4) === 'WEBP') {
            return 'webp';
        }

        // Check JPEG signature
        if (str_starts_with($data, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        return 'webp'; // default
    }
}
