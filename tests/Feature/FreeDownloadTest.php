<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FreeDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.resource_disk'));
    }

    private function makeResource(): Resource
    {
        $path = 'resources/'.Str::random(8).'.pdf';
        Storage::disk(config('filesystems.resource_disk'))->put($path, 'pdf');

        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        return Resource::create([
            'resource_type_id' => $type->id,
            'title' => 'Doc '.Str::random(6),
            'file_path' => $path,
            'file_size' => 3,
            'format' => 'pdf',
            'status' => 'approved',
        ]);
    }

    public function test_registration_announces_the_free_download(): void
    {
        $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('resources.index'))
            ->assertSessionHas('status', User::FREE_DOWNLOAD_WELCOME);

        $this->assertTrue(User::firstWhere('email', 'new@example.com')->hasFreeDownload());
    }

    public function test_free_download_then_one_more_per_two_uploads(): void
    {
        $user = User::factory()->create()->refresh();
        [$a, $b, $c] = [$this->makeResource(), $this->makeResource(), $this->makeResource()];

        $this->actingAs($user)->post(route('resources.download', $a))->assertOk();
        $this->assertFalse($user->fresh()->canDownload());

        // Re-downloading something already fetched is free; anything new is blocked.
        $user->refresh();
        $this->actingAs($user)->post(route('resources.download', $a))->assertOk();
        $user->refresh();
        $this->actingAs($user)->post(route('resources.download', $b))->assertForbidden();
        $this->actingAs($user)->get(route('resources.show', $b))->assertSee('You have no downloads left');

        $user->update(['uploads_count' => 1]);
        $user->refresh();
        $this->actingAs($user)->post(route('resources.download', $b))->assertForbidden();

        $user->update(['uploads_count' => 2]);
        $this->actingAs($user)->post(route('resources.download', $b))->assertOk();
        $this->assertSame(0, $user->fresh()->downloadsRemaining());
        $user->refresh();
        $this->actingAs($user)->post(route('resources.download', $c))->assertForbidden();
    }

    public function test_uploading_two_documents_first_gives_two_downloads(): void
    {
        $user = User::factory()->create(['uploads_count' => 2])->refresh();
        [$a, $b, $c] = [$this->makeResource(), $this->makeResource(), $this->makeResource()];

        $this->assertSame(2, $user->downloadsRemaining());
        $this->actingAs($user)->post(route('resources.download', $a))->assertOk();
        $this->actingAs($user)->post(route('resources.download', $b))->assertOk();
        $user->refresh();
        $this->actingAs($user)->post(route('resources.download', $c))->assertForbidden();
    }

    public function test_admins_and_uploaders_do_not_burn_their_free_download(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('resources.download', $this->makeResource()))->assertOk();

        $this->assertSame(0, $admin->fresh()->downloads_used);
    }
}
