<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-editable fields required by Phase 4 that the Phase 3 schema did
     * not carry. All are nullable/defaulted so the public frontend output is
     * unchanged until an admin fills them in.
     */
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table): void {
            $table->string('company')->nullable()->after('title');
        });

        Schema::table('educations', function (Blueprint $table): void {
            $table->string('institution')->nullable()->after('title');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('logo_path');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('image_alt');
            $table->string('client')->nullable()->after('description');
            $table->string('technologies')->nullable()->after('client');
            $table->string('display_date')->nullable()->after('technologies');
            $table->boolean('featured')->default(false)->after('display_date');
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->text('content')->nullable()->after('excerpt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn('content');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['featured', 'display_date', 'technologies', 'client', 'description']);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('educations', function (Blueprint $table): void {
            $table->dropColumn('institution');
        });

        Schema::table('experiences', function (Blueprint $table): void {
            $table->dropColumn('company');
        });
    }
};
