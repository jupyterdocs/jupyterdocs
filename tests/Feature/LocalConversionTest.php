<?php

namespace Tests\Feature;

use App\Jobs\ConvertResourceToPdfLocally;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LocalConversionTest extends TestCase
{
    use RefreshDatabase;

    private function resource(string $conversionStatus = 'none', string $format = 'docx'): Resource
    {
        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        // conversion_status isn't mass-assignable, so it has to be set
        // after create() rather than passed in the create() array.
        $resource = Resource::create([
            'resource_type_id' => $type->id,
            'title' => 'Local conversion test doc',
            'file_path' => 'resources/x.'.$format,
            'file_size' => 10,
            'format' => $format,
            'status' => 'approved',
        ]);

        $resource->conversion_status = $conversionStatus;
        $resource->save();

        return $resource;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin'])->refresh();
    }

    public function test_only_admins_can_start_local_conversion(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)
            ->post(route('admin.conversion.start-local'))
            ->assertForbidden();
    }

    public function test_starting_local_conversion_queues_the_backlog_onto_the_local_queue(): void
    {
        Queue::fake();

        $needsConversion = $this->resource('none');
        $failed = $this->resource('failed');
        $alreadyDone = $this->resource('done');
        $alreadyPdf = $this->resource('none', 'pdf');

        $response = $this->actingAs($this->admin())
            ->post(route('admin.conversion.start-local'))
            ->assertOk()
            ->assertJson(['queued' => 2, 'command' => 'php artisan conversion:work-local']);

        Queue::assertPushedOn(config('conversion.local.queue'), ConvertResourceToPdfLocally::class);
        Queue::assertPushed(ConvertResourceToPdfLocally::class, 2);

        $this->assertSame('pending', $needsConversion->fresh()->conversion_status);
        $this->assertSame('pending', $failed->fresh()->conversion_status);
        $this->assertSame('done', $alreadyDone->fresh()->conversion_status);
        $this->assertSame('none', $alreadyPdf->fresh()->conversion_status);

        $this->assertTrue($needsConversion->fresh()->queued_for_local_conversion);
        $this->assertTrue($failed->fresh()->queued_for_local_conversion);
        $this->assertFalse($alreadyDone->fresh()->queued_for_local_conversion);

        $batch = $response->json('batch');
        $this->assertCount(2, $batch);
        $this->assertEqualsCanonicalizing(
            [$needsConversion->id, $failed->id],
            array_column($batch, 'id')
        );
    }

    public function test_starting_a_new_batch_drops_previously_finished_documents_from_the_list(): void
    {
        Queue::fake();

        $finishedEarlier = $this->resource('done');
        $finishedEarlier->queued_for_local_conversion = true;
        $finishedEarlier->save();

        $newBacklog = $this->resource('none');

        $response = $this->actingAs($this->admin())
            ->post(route('admin.conversion.start-local'))
            ->assertOk();

        $this->assertFalse($finishedEarlier->fresh()->queued_for_local_conversion);
        $this->assertSame([$newBacklog->id], array_column($response->json('batch'), 'id'));
    }

    public function test_status_endpoint_reports_worker_heartbeat_backlog_size_and_batch(): void
    {
        $this->resource('none');
        $inBatch = $this->resource('failed');
        $inBatch->queued_for_local_conversion = true;
        $inBatch->save();

        $this->actingAs($this->admin())
            ->getJson(route('admin.conversion.status'))
            ->assertOk()
            ->assertJson(['worker_connected' => false, 'needs_conversion' => 2])
            ->assertJsonCount(1, 'batch')
            ->assertJsonPath('batch.0.id', $inBatch->id)
            ->assertJsonPath('batch.0.status', 'failed');

        Cache::put('conversion:local-worker:heartbeat', now()->toIso8601String(), now()->addSeconds(20));

        $this->actingAs($this->admin())
            ->getJson(route('admin.conversion.status'))
            ->assertOk()
            ->assertJson(['worker_connected' => true]);
    }

    public function test_job_marks_resource_failed_when_local_driver_reports_failure(): void
    {
        $resource = $this->resource('pending');

        // No LibreOffice is installed on the test runner, so the real
        // driver's own "not found" failure path is exactly what runs here.
        (new ConvertResourceToPdfLocally($resource->id))->handle(app(\App\Support\Conversion\Drivers\LocalCliDriver::class));

        $resource->refresh();
        $this->assertSame('failed', $resource->conversion_status);
        $this->assertNotEmpty($resource->conversion_error);
    }

    public function test_dashboard_shows_the_ask_prompt_when_backlog_exists_and_nothing_is_running(): void
    {
        $this->resource('none');

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="local-conversion-panel"', false)
            ->assertSee('Use this device')
            ->assertSee('id="local-conversion-ask" ', false); // no "hidden" attribute right after it
    }

    public function test_dashboard_hides_the_panel_when_nothing_needs_conversion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('id="local-conversion-panel"', false);
    }

    /**
     * This is the actual bug report: switching tabs (i.e. a fresh page
     * load, with no client-side state at all) used to lose the running
     * batch and the command to copy. The panel is now driven entirely by
     * the database, so a plain GET has to show it without any prior fetch.
     */
    public function test_dashboard_shows_a_running_batch_and_its_command_on_a_fresh_page_load(): void
    {
        $inProgress = $this->resource('processing');
        $inProgress->queued_for_local_conversion = true;
        $inProgress->save();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="local-conversion-progress"', false)
            ->assertDontSee('id="local-conversion-progress" hidden', false)
            ->assertSee('php artisan conversion:work-local')
            ->assertSee($inProgress->title)
            ->assertSee('Converting…')
            ->assertSee('Run this automatically from now on');
    }

    public function test_dashboard_shows_worker_connected_and_hides_the_command_when_a_worker_is_listening(): void
    {
        $inProgress = $this->resource('processing');
        $inProgress->queued_for_local_conversion = true;
        $inProgress->save();

        Cache::put('conversion:local-worker:heartbeat', now()->toIso8601String(), now()->addSeconds(20));

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Connected — converting on this device')
            ->assertSee('id="local-conversion-command-box"  hidden', false);
    }
}
