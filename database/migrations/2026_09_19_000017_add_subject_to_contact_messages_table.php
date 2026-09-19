<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the contact form's subject line.
     *
     * Phase 3 shipped the table with name/email/message only, which matched
     * the Phase 2 template (the original form had no subject field). Phase 7
     * makes the form production-ready and the required flow includes a
     * subject, so the column is genuinely missing rather than redundant.
     *
     * Defaults to an empty string so every existing row stays valid and
     * readable — no backfill, no nullable branch in the UI.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('subject')->default('')->after('email');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }
};
