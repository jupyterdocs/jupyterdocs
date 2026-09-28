<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeResource(): Resource
    {
        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        return Resource::create([
            'resource_type_id' => $type->id,
            'title' => 'Original title',
            'description' => 'Original description',
            'file_path' => 'resources/doc.pdf',
            'file_size' => 3,
            'format' => 'pdf',
            'status' => 'pending',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_non_admins_cannot_edit_resources(): void
    {
        $resource = $this->makeResource();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.resources.edit', $resource))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.resources.update', $resource), [
            'title' => 'Hijacked',
            'description' => 'Hijacked description',
            'resource_type_id' => $resource->resource_type_id,
        ])->assertForbidden();

        $this->assertSame('Original title', $resource->fresh()->title);
    }

    public function test_admin_can_update_title_description_and_type(): void
    {
        $resource = $this->makeResource();
        $pastPaper = ResourceType::firstOrCreate(['slug' => 'past-paper'], ['name' => 'Past Paper']);

        $this->actingAs($this->admin())->get(route('admin.resources.edit', $resource))
            ->assertOk()
            ->assertSee('Original title');

        $this->actingAs($this->admin())->patch(route('admin.resources.update', $resource), [
            'title' => 'Operating Systems Exam 2024',
            'description' => 'Final exam covering scheduling and memory management',
            'resource_type_id' => $pastPaper->id,
        ])->assertRedirect(route('resources.show', $resource));

        $resource->refresh();
        $this->assertSame('Operating Systems Exam 2024', $resource->title);
        $this->assertSame('Final exam covering scheduling and memory management', $resource->description);
        $this->assertSame($pastPaper->id, $resource->resource_type_id);
        // Tags are regenerated from the new text.
        $tags = $resource->tags->pluck('name')->map(fn ($tag) => strtolower($tag));
        $this->assertTrue($tags->contains(fn ($tag) => str_contains($tag, 'operating')));
        $this->assertFalse($tags->contains(fn ($tag) => str_contains($tag, 'original')));
    }

    public function test_admin_is_returned_to_the_page_they_came_from(): void
    {
        $resource = $this->makeResource();

        $this->actingAs($this->admin())->patch(route('admin.resources.update', $resource), [
            'title' => 'New title',
            'description' => 'New description',
            'resource_type_id' => $resource->resource_type_id,
            'return_to' => route('admin.moderation.index'),
        ])->assertRedirect(route('admin.moderation.index'));

        $this->actingAs($this->admin())->patch(route('admin.resources.update', $resource), [
            'title' => 'New title',
            'description' => 'New description',
            'resource_type_id' => $resource->resource_type_id,
            'return_to' => 'https://evil.example.com/phish',
        ])->assertRedirect(route('resources.show', $resource));
    }

    public function test_update_is_validated(): void
    {
        $resource = $this->makeResource();

        $this->actingAs($this->admin())->patch(route('admin.resources.update', $resource), [
            'title' => '',
            'description' => str_repeat('word ', 501),
            'resource_type_id' => 999999,
        ])->assertSessionHasErrors(['title', 'description', 'resource_type_id']);

        $this->assertSame('Original title', $resource->fresh()->title);
    }
}
