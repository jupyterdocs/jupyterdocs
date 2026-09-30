<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_request_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resource_request_id')->constrained('resource_requests')->cascadeOnDelete();
            $table->timestamps();

            // One upvote per user per request.
            $table->unique(['user_id', 'resource_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_request_votes');
    }
};
