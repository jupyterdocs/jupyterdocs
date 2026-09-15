<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('converted_pdf_path')->nullable()->after('thumbnail_path');
            $table->string('conversion_status', 20)->default('none')->after('converted_pdf_path');
            $table->string('conversion_driver', 20)->nullable()->after('conversion_status');
            $table->timestamp('converted_at')->nullable()->after('conversion_driver');
            $table->text('conversion_error')->nullable()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn([
                'converted_pdf_path',
                'conversion_status',
                'conversion_driver',
                'converted_at',
                'conversion_error',
            ]);
        });
    }
};
