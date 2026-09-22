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

        $this->actingAs($this->admin())
            ->post(route('admin.conversion.start-local'))
            ->assertOk()
            ->assertJson(['queued' => 2, 'command' => 'php artisan conversion:work-local']);

        Queue::assertPushedOn(config('conversion.local.queue'), ConvertResourceToPdfLocally::class);
        Queue::assertPushed(ConvertResourceToPdfLocally::class, 2);

        $this->assertSame('pending', $needsConversion->fresh()->conversion_status);
        $this->assertSame('pending', $failed->fresh()->conversion_status);
        $this->assertSame('done', $alreadyDone->fresh()->conversion_status);
        $this->assertSame('none', $alreadyPdf->fresh()->conversion_status);
    }

    public function test_status_endpoint_reports_worker_heartbeat_and_backlog_size(): void
    {
        $this->resource('none');
        $this->resource('failed');

        $this->actingAs($this->admin())
            ->getJson(route('admin.conversion.status'))
            ->assertOk()
            ->assertJson(['worker_connected' => false, 'needs_conversion' => 2]);

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

    public function test_dashboard_shows_the_local_conversion_prompt_when_backlog_exists(): void
    {
        $this->resource('none');

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="local-conversion-prompt"', false)
            ->assertSee('Use this device');
    }

    public function test_dashboard_hides_the_prompt_when_nothing_needs_conversion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('id="local-conversion-prompt"', false);
    }
}
