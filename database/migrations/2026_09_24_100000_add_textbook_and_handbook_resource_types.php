<?php

use App\Models\ResourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TYPES = ['Textbook', 'Handbook'];

    public function up(): void
    {
        foreach (self::TYPES as $name) {
            ResourceType::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }

    public function down(): void
    {
        ResourceType::whereIn('slug', array_map(fn ($n) => Str::slug($n), self::TYPES))
            ->whereDoesntHave('resources')
            ->delete();
    }
};
