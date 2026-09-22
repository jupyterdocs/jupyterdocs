<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            // Which admin queued this document for local conversion — shown
            // in the conversion module so "who is converting what" has a
            // real name attached, not just "this device".
            $table->foreignId('queued_by')->nullable()->after('queued_for_local_conversion')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('queued_by');
        });
    }
};
