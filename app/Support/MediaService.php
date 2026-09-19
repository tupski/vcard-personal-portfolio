<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Media pipeline: validated upload -> original + thumb + medium.
 *
 * Storage abstraction: everything goes through the Storage facade on the
 * media disk (default `public`), so switching to S3/R2 later is a config
 * change — no application code assumes public/….
 *
 * Variants are derived from the Phase 2 design's actual render sizes
 * (avatars ~80-150px, testimonials ~60-80px, project/blog cards ~200-230px
 * tall), at 2x for retina: thumb 240w (small squares), medium 640w (cards).
 * The original is stored untouched and never overwritten or deleted by
 * variant generation.
 */
class MediaService
{
    /**
     * Dimension targets for the derived variants (max width in px).
     */
    public const THUMB_WIDTH = 240;

    public const MEDIUM_WIDTH = 640;

    public function __construct(private readonly ImageManager $images) {}

    /**
     * Validate + store an upload with its variants.
     *
     * @throws ValidationException
     */
    public function upload(UploadedFile $file, ?string $altText = null, ?string $title = null): Media
    {
        $validated = $this->validate($file);

        $disk = config('media.disk', 'public');
        $directory = 'media/'.now()->format('Y').'/'.now()->format('m');
        $name = Str::uuid()->toString();

        // The original keeps its (sanitized) extension but gets a server
        // generated name — client filenames never touch the filesystem.
        $originalPath = $directory.'/'.$name.'.'.$validated['extension'];

        Storage::disk($disk)->put($originalPath, $file->getContent());

        $dimensions = $this->measure(Storage::disk($disk)->path($originalPath));

        $media = Media::query()->create([
            'disk' => $disk,
            'path' => $originalPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $validated['mime'],
            'size' => (int) $file->getSize(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'alt_text' => $altText,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);

        $this->generateVariants($media);

        return $media;
    }

    /**
     * Delete a media record and exactly its own files (original + variants).
     * Nothing outside the record's directory entry is ever touched.
     */
    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);

        foreach (['thumbPath', 'mediumPath'] as $method) {
            $variantPath = $media->{$method}();

            if ($disk->exists($variantPath)) {
                $disk->delete($variantPath);
            }
        }

        if ($disk->exists($media->path)) {
            $disk->delete($media->path);
        }

        $media->delete();
    }

    /**
     * Resolve a content path (media path or legacy static asset) to a URL.
     *
     * Static template assets (assets/images/...) stay on public/ and render
     * exactly as in Phase 2; anything under media/ resolves through the
     * storage abstraction.
     */
    public function url(string $path): string
    {
        if ($path === '' || str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        $media = Media::query()->where('path', $path)->first();

        return $media?->url() ?? asset($path);
    }

    /**
     * Thumb variant URL for a content path (falls back to the plain URL for
     * static assets or SVGs, which need no raster variant).
     */
    public function thumbUrl(string $path): string
    {
        return $this->variantUrl($path, 'thumbPath');
    }

    /**
     * Medium variant URL for a content path.
     */
    public function mediumUrl(string $path): string
    {
        return $this->variantUrl($path, 'mediumPath');
    }

    /**
     * Server-side upload validation. MIME is verified from file content, not
     * the client header.
     *
     * @return array{mime: string, extension: string}
     */
    private function validate(UploadedFile $file): array
    {
        validator(
            ['file' => $file],
            [
                'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            ],
            [
                'file.image' => __('The file must be an image.'),
                'file.mimes' => __('Only JPG, PNG and WebP images are allowed.'),
                'file.max' => __('The image must be smaller than 4 MB.'),
                'file.dimensions' => __('The image must be at most 6000x6000 pixels.'),
            ],
        )->validate();

        // Trust the decoded content, not the client: UploadedFile::extension()
        // derives from the client name, so derive from the detected MIME.
        $mime = $file->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return ['mime' => $mime, 'extension' => $extension];
    }

    /**
     * Generate thumb + medium WebP variants alongside the original.
     */
    private function generateVariants(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $absolute = $disk->path($media->path);

        if (! is_readable($absolute)) {
            return;
        }

        $image = $this->images->decodePath($absolute);

        // Animated sources (rare here) keep only the original.
        if ($image->isAnimated()) {
            return;
        }

        foreach ([['thumb', self::THUMB_WIDTH, 80], ['medium', self::MEDIUM_WIDTH, 82]] as [$variant, $width, $quality]) {
            $encoded = (string) $image->scaleDown(width: $width)->encode(new WebpEncoder(quality: $quality));
            $disk->put($media->{"{$variant}Path"}(), $encoded);
        }
    }

    /**
     * Dimensions from decoded content (EXIF orientation applied), so the
     * stored width/height describe the real image.
     *
     * @return array{width: int|null, height: int|null}
     */
    private function measure(string $absolutePath): array
    {
        try {
            $image = $this->images->decodePath($absolutePath);

            return ['width' => $image->width(), 'height' => $image->height()];
        } catch (\Throwable) {
            return ['width' => null, 'height' => null];
        }
    }

    /**
     * Variant URL for a content path; static assets and missing media fall
     * back to the plain URL.
     */
    private function variantUrl(string $path, string $method): string
    {
        if ($path === '' || str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        $media = Media::query()->where('path', $path)->first();

        if (! $media) {
            return asset($path);
        }

        $variantPath = $media->{$method}();

        if (Storage::disk($media->disk)->exists($variantPath)) {
            return Storage::disk($media->disk)->url($variantPath);
        }

        return $media->url();
    }
}
