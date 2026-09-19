<?php

return [
    /*
    |----------------------------------------------------------------------
    | Media disk
    |----------------------------------------------------------------------
    |
    | The Storage disk uploads and variants are written to. `public` keeps
    | everything VPS/shared-hosting friendly via storage:link; switching to
    | an S3-compatible disk (AWS S3, Cloudflare R2) later is a config change
    | only — application code goes through the Storage facade.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |----------------------------------------------------------------------
    | Static asset dimensions
    |----------------------------------------------------------------------
    |
    | Intrinsic width/height of the committed template assets, generated from
    | the real files by `php artisan images:manifest` and committed so that
    | rendering never reads the filesystem.
    |
    | Emitting these on every <img> lets the browser reserve the correct space
    | before the image arrives, which is what keeps cumulative layout shift at
    | zero. Uploaded images get their dimensions from the media table instead.
    |
    */

    'dimensions' => (static function (): array {
        $manifest = resource_path('image-dimensions.php');

        return is_file($manifest) ? (array) require $manifest : [];
    })(),
];
