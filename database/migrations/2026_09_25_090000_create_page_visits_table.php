<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per page view. No raw IP address is stored: `visitor_hash` is
     * a one-way, per-day fingerprint, enough to count unique visitors per
     * day but not to identify or follow anyone.
     */
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('visitor_hash', 64);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country_code', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('path', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['visit_date', 'visitor_hash']);
            $table->index(['visit_date', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
