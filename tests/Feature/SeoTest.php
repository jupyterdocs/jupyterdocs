<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_the_first_heading_on_the_homepage_is_the_h1(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<h([1-6])\b/', $html, $first);

        $this->assertSame('1', $first[1], 'The first heading in the page source must be the H1.');
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
    }

    public function test_the_homepage_declares_the_organization_and_faq_schema(): void
    {
        $this->get('/')
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_the_homepage_has_enough_text_to_not_be_thin_content(): void
    {
        $text = strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', '', $this->get('/')->getContent()));

        $this->assertGreaterThan(600, str_word_count($text));
    }

    public function test_the_font_stylesheet_does_not_block_rendering(): void
    {
        $this->get('/')->assertSee('media="print" onload="this.media=\'all\'"', false);
    }

    public function test_llms_txt_describes_the_site(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Browse and search the archive');
    }

    public function test_analytics_only_loads_when_configured(): void
    {
        config(['site.analytics_id' => null]);
        $this->get('/')->assertDontSee('googletagmanager.com', false);

        config(['site.analytics_id' => 'G-ABC123XYZ']);
        $this->get('/')->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);
    }
}
