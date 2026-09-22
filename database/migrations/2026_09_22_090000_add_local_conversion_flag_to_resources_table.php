<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            // True while a resource is part of the admin's current
            // "convert on this device" batch, so the dashboard can show a
            // live per-document list that survives reloads/tab switches
            // instead of relying on the browser to remember anything.
            $table->boolean('queued_for_local_conversion')->default(false)->after('conversion_error');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('queued_for_local_conversion');
        });
    }
};
