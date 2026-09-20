<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceUploadTest extends TestCase
{
    use RefreshDatabase;

    private function type(): ResourceType
    {
        return ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);
    }

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.resource_disk'));

        $this->validPayload = [
            'title' => 'Mathematics for IT Professionals',
            'description' => str_repeat('This textbook covers discrete mathematics and linear algebra for computing students. ', 4),
            'resource_type_id' => $this->type()->id,
            'confirm_ownership' => '1',
            'file' => UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf'),
        ];
    }

    public function test_description_shorter_than_125_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'description' => 'Too short.',
        ])->assertSessionHasErrors('description');

        $this->assertSame(0, Resource::count());
    }

    public function test_upload_auto_generates_tags_and_unlocks_downloads_without_waiting_for_admin_approval(): void
    {
        $user = User::factory()->create()->refresh();
        $this->assertSame(0, $user->uploads_count);
        $this->assertFalse($user->canDownload());

        $this->actingAs($user)->post(route('resources.store'), $this->validPayload)
            ->assertRedirect(route('resources.mine'));

        $resource = Resource::firstOrFail();
        $this->assertSame('pending', $resource->status);
        $this->assertNotEmpty($resource->tags);
        $this->assertLessThanOrEqual(8, $resource->tags->count());

        $user->refresh();
        $this->assertSame(1, $user->uploads_count);
        $this->assertSame(0, $user->approved_uploads_count);
        // Still pending admin approval, yet already counts toward the unlock.
        $this->assertFalse($user->canDownload());

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'title' => 'Second upload',
        ])->assertRedirect(route('resources.mine'));

        $user->refresh();
        $this->assertSame(2, $user->uploads_count);
        $this->assertTrue($user->canDownload());
        $this->assertTrue($resource->fresh()->isDownloadableBy($user));
    }
}
