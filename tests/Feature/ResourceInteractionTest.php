<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceReport;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

// Uses transactions rather than RefreshDatabase so running it never wipes the dev database.
class ResourceInteractionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeResource(string $status = 'approved'): Resource
    {
        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        return Resource::create([
            'resource_type_id' => $type->id,
            'title' => 'Test doc '.Str::random(6),
            'file_path' => 'resources/missing.pdf',
            'file_size' => 1024,
            'format' => 'pdf',
            'status' => $status,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $resource = $this->makeResource();

        $this->post(route('resources.save', $resource))->assertRedirect(route('login'));
        $this->postJson(route('resources.vote', $resource), ['vote' => 'like'])->assertUnauthorized();
        $this->get(route('resources.saved'))->assertRedirect(route('login'));
    }

    public function test_save_toggles_without_downloading(): void
    {
        $user = $this->user();
        $resource = $this->makeResource();

        $this->actingAs($user)->postJson(route('resources.save', $resource))
            ->assertOk()->assertJson(['saved' => true]);
        $this->assertTrue($user->savedResources()->whereKey($resource->id)->exists());
        $this->assertSame(0, $resource->fresh()->downloads_count);

        $this->actingAs($user)->get(route('resources.saved'))->assertOk()->assertSee($resource->title);
        $this->actingAs($user)->get(route('resources.index'))->assertOk();

        $this->actingAs($user)->postJson(route('resources.save', $resource))
            ->assertOk()->assertJson(['saved' => false]);
        $this->assertFalse($user->savedResources()->whereKey($resource->id)->exists());
    }

    public function test_like_dislike_switch_and_undo(): void
    {
        $resource = $this->makeResource();
        [$a, $b, $c, $d] = [$this->user(), $this->user(), $this->user(), $this->user()];

        $this->actingAs($a)->postJson(route('resources.vote', $resource), ['vote' => 'like'])->assertOk();
        $this->actingAs($b)->postJson(route('resources.vote', $resource), ['vote' => 'like'])->assertOk();
        $this->actingAs($c)->postJson(route('resources.vote', $resource), ['vote' => 'like'])->assertOk();
        $this->actingAs($d)->postJson(route('resources.vote', $resource), ['vote' => 'dislike'])
            ->assertJson(['vote' => 'dislike', 'likes' => 3, 'dislikes' => 1, 'like_percent' => 75, 'dislike_percent' => 25]);

        // Switching replaces the vote rather than adding a second one.
        $this->actingAs($d)->postJson(route('resources.vote', $resource), ['vote' => 'like'])
            ->assertJson(['vote' => 'like', 'likes' => 4, 'dislikes' => 0, 'like_percent' => 100]);

        // Clicking the same vote again removes it.
        $this->actingAs($d)->postJson(route('resources.vote', $resource), ['vote' => 'like'])
            ->assertJson(['vote' => null, 'likes' => 3, 'dislikes' => 0]);

        $this->actingAs($a)->postJson(route('resources.vote', $resource), ['vote' => 'meh'])->assertUnprocessable();

        $this->actingAs($a)->get(route('resources.show', $resource))->assertOk()->assertSee('100%');
    }

    public function test_report_is_recorded_and_admin_can_resolve_it(): void
    {
        $user = $this->user();
        $resource = $this->makeResource();

        $this->actingAs($user)->postJson(route('resources.report', $resource), ['reason' => 'other'])
            ->assertUnprocessable()->assertJsonValidationErrors('details');

        $this->actingAs($user)->postJson(route('resources.report', $resource), ['reason' => 'spam', 'details' => 'Just ads'])
            ->assertOk()->assertJson(['reported' => true]);

        $report = ResourceReport::where('user_id', $user->id)->where('resource_id', $resource->id)->firstOrFail();
        $this->assertSame('open', $report->status);

        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee($resource->title)->assertSee('Just ads');
        $this->actingAs($admin)->patch(route('admin.reports.update', $report), ['status' => 'resolved'])->assertRedirect();

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->reviewed_by);
    }

    public function test_unapproved_documents_cannot_be_interacted_with(): void
    {
        $user = $this->user();
        $resource = $this->makeResource('pending');

        $this->actingAs($user)->postJson(route('resources.save', $resource))->assertNotFound();
        $this->actingAs($user)->postJson(route('resources.vote', $resource), ['vote' => 'like'])->assertNotFound();
        $this->actingAs($user)->postJson(route('resources.report', $resource), ['reason' => 'spam'])->assertNotFound();
    }
}
