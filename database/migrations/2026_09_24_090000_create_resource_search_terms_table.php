<?php

use App\Support\Search\ResourceSearchIndexer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An inverted index: one row per (document, word, where the word
     * appeared). Searching becomes an indexed lookup on `term` instead of
     * a LIKE scan over every title and description.
     */
    public function up(): void
    {
        Schema::create('resource_search_terms', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->string('term', 40);
            $table->unsignedTinyInteger('field');
            $table->unsignedSmallInteger('hits')->default(1);

            $table->primary(['resource_id', 'term', 'field']);
            $table->index(['term', 'resource_id']);
        });

        // Make every document that already exists searchable straight away.
        // A hiccup here must not block the deploy: it is reported instead,
        // and `php artisan search:reindex` completes the job.
        try {
            app(ResourceSearchIndexer::class)->reindexAll();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_search_terms');
    }
};
