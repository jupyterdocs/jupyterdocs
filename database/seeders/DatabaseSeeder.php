<?php

namespace Database\Seeders;

use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
{
    foreach ([
        'Past Paper',
        'Notes',
        'Course Outline',
        'Tutorial',
        'Lab Manual',
        'Study Guide',
        'Assignment',
        'Novel',
        'Journal',
        'Report',
        'Letter',
        'Newspaper',
        'Blog',
    ] as $name) {
        ResourceType::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }

    User::firstOrCreate(
        ['email' => 'admin@jupyterdocs.test'],
        [
            'name' => 'JupyterDocs Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'approved_uploads_count' => 999,
            'email_verified_at' => now(),
        ]
    );

    User::firstOrCreate(
        ['email' => 'admin2@jupyterdocs.test'],
        [
            'name' => 'JupyterDocs Admin 2',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'approved_uploads_count' => 999,
            'email_verified_at' => now(),
        ]
    );

    User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
}
}
