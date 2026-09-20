<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every upload a user makes, regardless of moderation status —
     * unlike approved_uploads_count, this unlocks downloads immediately on
     * upload instead of making the uploader wait for admin approval.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('uploads_count')->default(0)->after('approved_uploads_count');
        });

        DB::table('resources')
            ->whereNotNull('uploader_id')
            ->whereNull('deleted_at')
            ->select('uploader_id', DB::raw('count(*) as total'))
            ->groupBy('uploader_id')
            ->get()
            ->each(function ($row) {
                DB::table('users')->where('id', $row->uploader_id)->update(['uploads_count' => $row->total]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uploads_count');
        });
    }
};
