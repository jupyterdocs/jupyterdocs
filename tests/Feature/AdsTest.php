<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdsTest extends TestCase
{
    private function configure(array $overrides = []): void
    {
        config([
            'ads.enabled' => true,
            'ads.client' => 'ca-pub-1234567890123456',
            'ads.slots.document' => '1111111111',
            'ads.slots.sidebar' => null,
        ] + $overrides);
    }

    public function test_nothing_renders_while_ads_are_disabled(): void
    {
        $this->configure(['ads.enabled' => false]);

        $html = Blade::render('<x-ad placement="document" />');

        $this->assertStringNotContainsString('adsbygoogle', $html);
    }

    public function test_nothing_renders_without_a_publisher_id(): void
    {
        $this->configure(['ads.client' => null]);

        $this->assertStringNotContainsString('adsbygoogle', Blade::render('<x-ad placement="document" />'));
    }

    public function test_a_placement_without_an_ad_unit_renders_nothing(): void
    {
        $this->configure();

        $this->assertStringNotContainsString('adsbygoogle', Blade::render('<x-ad placement="sidebar" />'));
    }

    public function test_a_configured_placement_renders_a_labelled_ad(): void
    {
        $this->configure();

        $html = Blade::render('<x-ad placement="document" />');

        $this->assertStringContainsString('data-ad-client="ca-pub-1234567890123456"', $html);
        $this->assertStringContainsString('data-ad-slot="1111111111"', $html);
        $this->assertStringContainsString('Advertisement', $html);
    }

    public function test_ads_txt_lists_the_publisher_when_configured(): void
    {
        $this->configure();

        $this->get('/ads.txt')
            ->assertOk()
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', false);
    }

    public function test_ads_txt_is_not_found_without_a_publisher_id(): void
    {
        $this->configure(['ads.client' => null]);

        $this->get('/ads.txt')->assertNotFound();
    }
}
