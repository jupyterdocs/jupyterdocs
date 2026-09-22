<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Uploads made before formats were lowercased at the source (e.g. a
     * file named "Report.PDF") left mixed-case values in the format
     * column. That broke every strict format check in the app — a PDF
     * stored as "PDF" was never recognised as one, and a "DOCX" upload
     * never got queued for conversion. This is a one-time cleanup; new
     * uploads are already normalized going forward.
     */
    public function up(): void
    {
        DB::table('resources')->update(['format' => DB::raw('LOWER(format)')]);
    }

    public function down(): void
    {
        // Lowercasing is not reversible (the original casing is gone),
        // and every part of the app expects lowercase anyway.
    }
};
