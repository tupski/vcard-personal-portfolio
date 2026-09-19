<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media library table.
     *
     * Field rationale (nothing here "because CMSs have it"):
     * - disk + path: storage abstraction — the app never assumes
     *   public/…; the disk decides how a path becomes a URL. Together
     *   they are unique so a path can never collide across disks.
     * - original_name: what the admin uploaded; shown in the library
     *   and kept for audit — the stored name is a UUID, not this.
     * - mime_type + size: surfaced in the admin library and required to
     *   emit correct headers when serving.
     * - width + height: lets Blade emit width/height attributes
     *   (prevents layout shift) and documents variant dimensions.
     * - alt_text: accessibility — every public <img> needs it.
     * - title: how the admin identifies an image in the picker.
     * - timestamps: the admin sorts the library by upload date.
     *
     * There is no polymorphic pivot: content models keep their existing
     * `*_path` columns, which either hold a media path (uploads) or a
     * static asset path — no destructive migration.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
