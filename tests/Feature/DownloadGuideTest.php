<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadGuideTest extends TestCase
{
    use RefreshDatabase;

    private function resource(string $format = 'pdf'): Resource
    {
        $type = ResourceType::firstOrCreate(['slug' => 'notes'], ['name' => 'Notes']);

        return Resource::create([
            'resource_type_id' => $type->id,
            'title' => 'Guide test doc',
            'file_path' => 'resources/x.'.$format,
            'file_size' => 10,
            'format' => $format,
            'status' => 'approved',
        ]);
    }

    public function test_download_guide_is_on_the_page_for_someone_who_can_download(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->get(route('resources.show', $this->resource()))
            ->assertOk()
            ->assertSee('id="download-guide"', false)
            ->assertSee('Save to Files')
            ->assertSee('window.jdSkipReload', false);
    }

    public function test_office_files_get_the_extra_viewer_note(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->get(route('resources.show', $this->resource('docx')))
            ->assertSee('Office files open in a quick viewer first');

        $this->actingAs($user)->get(route('resources.show', $this->resource('pdf')))
            ->assertDontSee('Office files open in a quick viewer first');
    }

    public function test_guests_do_not_see_the_guide(): void
    {
        $this->get(route('resources.show', $this->resource()))
            ->assertOk()
            ->assertDontSee('id="download-guide"', false);
    }
}
