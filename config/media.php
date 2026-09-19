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
];
