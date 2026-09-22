<?php

namespace Tests\Feature;

use App\Jobs\ConvertResourceToPdfLocally;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\TestCase;

class LocalConversionTest extends TestCase
{
    use RefreshDatabase;

    private function resource(string $conversionStatus = 'none', string $format = 'docx', string $title = 'Local conversion test doc'): Resource
    {
        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        // conversion_status isn't mass-assignable, so it has to be set
        // after create() rather than passed in the create() array.
        $resource = Resource::create([
            'resource_type_id' => $type->id,
            'title' => $title,
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
        return User::factory()->create(['role' => 'admin', 'name' => 'Admin Ama'])->refresh();
    }

    private function jobResourceId(ConvertResourceToPdfLocally $job): int
    {
        $property = new ReflectionProperty($job, 'resourceId');
        $property->setAccessible(true);

        return $property->getValue($job);
    }

    // -- Queuing & selection ------------------------------------------------

    public function test_only_admins_can_start_local_conversion(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)
            ->post(route('admin.conversion.start-local'))
            ->assertForbidden();
    }

    public function test_starting_local_conversion_with_no_selection_queues_the_whole_backlog(): void
    {
        Queue::fake();

        $needsConversion = $this->resource('none');
        $failed = $this->resource('failed');
        $alreadyDone = $this->resource('done');
        $alreadyPdf = $this->resource('none', 'pdf');

        $admin = $this->admin();
        $response = $this->actingAs($admin)
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
        $this->assertSame($admin->id, $needsConversion->fresh()->queued_by);

        $batch = $response->json('batch');
        $this->assertCount(2, $batch);
        $this->assertEqualsCanonicalizing(
            [$needsConversion->id, $failed->id],
            array_column($batch, 'id')
        );
    }

    public function test_admin_can_select_specific_documents_and_only_those_are_queued(): void
    {
        Queue::fake();

        $wanted = $this->resource('none');
        $this->resource('failed');

        $this->actingAs($this->admin())
            ->postJson(route('admin.conversion.start-local'), ['resource_ids' => [$wanted->id]])
            ->assertOk()
            ->assertJson(['queued' => 1]);

        Queue::assertPushed(ConvertResourceToPdfLocally::class, 1);
        $this->assertTrue($wanted->fresh()->queued_for_local_conversion);
    }

    public function test_selection_order_is_the_order_documents_are_dispatched_in(): void
    {
        Queue::fake();

        $first = $this->resource('none', title: 'Uploaded first');
        $second = $this->resource('failed', title: 'Uploaded second');

        // Deliberately pick the second document before the first, to prove
        // the admin's click order — not upload/id order — wins.
        $this->actingAs($this->admin())
            ->postJson(route('admin.conversion.start-local'), ['resource_ids' => [$second->id, $first->id]])
            ->assertOk();

        $dispatchedIds = collect(Queue::pushedJobs()[ConvertResourceToPdfLocally::class])
            ->pluck('job')
            ->map(fn (ConvertResourceToPdfLocally $job) => $this->jobResourceId($job));

        $this->assertSame([$second->id, $first->id], $dispatchedIds->all());
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

    // -- Dashboard summary ----------------------------------------------------

    public function test_dashboard_links_to_the_conversion_module_when_something_needs_attention(): void
    {
        $this->resource('none');

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.conversion.index'), false)
            ->assertSee('Manage conversions');
    }

    public function test_dashboard_has_no_conversion_callout_when_nothing_needs_attention(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Manage conversions');
    }

    // -- Conversion module ----------------------------------------------------

    public function test_only_admins_can_view_the_conversion_module(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)
            ->get(route('admin.conversion.index'))
            ->assertForbidden();
    }

    public function test_module_shows_who_is_converting_each_in_progress_document(): void
    {
        $admin = $this->admin();

        $local = $this->resource('processing', title: 'Converting locally');
        $local->queued_for_local_conversion = true;
        $local->queued_by = $admin->id;
        $local->save();

        $remote = $this->resource('processing', title: 'Converting remotely');

        $this->actingAs($admin)
            ->get(route('admin.conversion.index'))
            ->assertOk()
            ->assertSee('Converting locally')
            ->assertSee('This device')
            ->assertSee('Admin Ama')
            ->assertSee('Converting remotely')
            ->assertSee('Remote pipeline (CloudConvert/Gotenberg)');
    }

    public function test_module_lists_the_backlog_with_checkboxes_to_select_next(): void
    {
        $waiting = $this->resource('none', title: 'Waiting doc');

        $this->actingAs($this->admin())
            ->get(route('admin.conversion.index'))
            ->assertOk()
            ->assertSee('Waiting doc')
            ->assertSee('value="'.$waiting->id.'"', false)
            ->assertSee('conversion-checkbox', false);
    }

    public function test_module_shows_recently_converted_documents_with_their_driver(): void
    {
        $admin = $this->admin();

        $viaCloudConvert = $this->resource('done', title: 'Converted via CloudConvert');
        $viaCloudConvert->conversion_driver = 'cloudconvert';
        $viaCloudConvert->converted_at = now();
        $viaCloudConvert->save();

        $viaLocal = $this->resource('done', title: 'Converted on this device');
        $viaLocal->conversion_driver = 'local';
        $viaLocal->converted_at = now();
        $viaLocal->queued_by = $admin->id;
        $viaLocal->save();

        $this->actingAs($admin)
            ->get(route('admin.conversion.index'))
            ->assertOk()
            ->assertSee('Converted via CloudConvert')
            ->assertSee('CloudConvert (API)')
            ->assertSee('Converted on this device')
            ->assertSee('queued by')
            ->assertSee('Admin Ama');
    }
}
