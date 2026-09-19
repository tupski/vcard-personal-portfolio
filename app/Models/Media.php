<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded media file and its generated variants.
 *
 * Variants (thumb/medium webp) live next to the original under
 * media/{yyyy}/{mm}/{uuid}-*.ext on the same disk. The original is never
 * modified; deleting the record removes exactly its own files.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
        'title',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Public URL for this file on its disk (Storage::url is disk-aware:
     * local public disk -> /storage/..., S3 -> bucket URL).
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * WebP thumbnail (240w) generated at upload time.
     */
    public function thumbPath(): string
    {
        return $this->variantPath('thumb');
    }

    /**
     * WebP medium (640w) generated at upload time.
     */
    public function mediumPath(): string
    {
        return $this->variantPath('medium');
    }

    private function variantPath(string $variant): string
    {
        $info = pathinfo($this->path);

        return ($info['dirname'] ?? '').'/'.$info['filename'].'-'.$variant.'.webp';
    }
}
