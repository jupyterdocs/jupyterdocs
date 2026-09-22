<?php

namespace Tests\Feature;

use App\Jobs\ConvertResourceToPdf;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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
            'description' => 'A short poem about what changed after the break up',
            'resource_type_id' => $this->type()->id,
            'confirm_ownership' => '1',
            'file' => UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf'),
        ];
    }

    public function test_description_outside_2_to_150_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'description' => 'X',
        ])->assertSessionHasErrors('description');

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'description' => str_repeat('a', 151),
        ])->assertSessionHasErrors('description');

        $this->assertSame(0, Resource::count());
    }

    public function test_upload_auto_generates_tags_and_unlocks_downloads_without_waiting_for_admin_approval(): void
    {
        $user = User::factory()->create()->refresh();
        $this->assertSame(0, $user->uploads_count);
        $this->assertSame(1, $user->downloadsRemaining());

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
        $this->assertSame(1, $user->downloadsRemaining());

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'title' => 'Second upload',
        ])->assertRedirect(route('resources.mine'));

        $user->refresh();
        $this->assertSame(2, $user->uploads_count);
        $this->assertSame(2, $user->downloadsRemaining());
        $this->assertTrue($resource->fresh()->isDownloadableBy($user));
    }

    /**
     * A filename like "Report.DOCX" left the format column as "DOCX" —
     * every format check in the app compares against lowercase, so an
     * uppercase extension silently skipped auto-conversion (and, for a
     * "PDF" upload, wrongly showed up in the conversion backlog).
     */
    public function test_uploaded_file_extension_is_normalized_to_lowercase(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        // A minimal valid (empty) ZIP: docx/pptx/xlsx uploads are inspected
        // with ZipArchive before storing, which needs real zip bytes to
        // open cleanly rather than arbitrary garbage.
        $emptyZip = "PK\x05\x06".str_repeat("\x00", 18);

        $this->actingAs($user)->post(route('resources.store'), [
            ...$this->validPayload,
            'file' => UploadedFile::fake()->createWithContent('Report.DOCX', $emptyZip),
        ])->assertRedirect(route('resources.mine'));

        $resource = Resource::firstOrFail();
        $this->assertSame('docx', $resource->format);

        // Lowercased correctly, it's now recognised as convertible and
        // gets dispatched immediately — not silently skipped.
        Queue::assertPushed(ConvertResourceToPdf::class);
    }
}
