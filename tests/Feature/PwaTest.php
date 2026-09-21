<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_declare_manifest_and_ios_home_screen_tags(): void
    {
        foreach (['/', '/login', '/browse'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('rel="manifest"', false)
                ->assertSee('rel="apple-touch-icon"', false)
                ->assertSee('apple-mobile-web-app-capable', false)
                ->assertSee('apple-touch-startup-image', false);
        }

        // Only layouts with the nav (not the bare login/register layout) carry the install guide.
        foreach (['/', '/browse'] as $url) {
            $this->get($url)->assertSee('ios-install-sheet', false);
        }
    }

    public function test_every_manifest_icon_and_launch_image_exists(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
        $this->assertCount(15, glob(public_path('icons/splash/*.png')));
    }

    public function test_install_sheet_is_a_closable_dialog_hidden_until_tapped(): void
    {
        $this->get('/browse')->assertOk()
            ->assertSee('id="ios-install-sheet" hidden style="display:none" role="dialog"', false)
            ->assertSee('data-ios-close', false);

        $files = glob(public_path('build/assets/*.css'));
        $files ?: $this->markTestSkipped('Frontend assets are not built.');

        $css = collect($files)->map(fn ($f) => file_get_contents($f))->implode('');
        $this->assertStringContainsString('[hidden]{display:none!important}', $css);
    }
}
