<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalController extends Controller
{
    /**
     * Slugs allowed to be rendered, mapped to the page title used in the
     * header and <title> tag. Keeping this as a whitelist (rather than
     * resolving resource_path() straight from the route parameter) means a
     * route can never be coaxed into reading an arbitrary file.
     */
    private const PAGES = [
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'copyright' => 'Copyright Policy',
        'contact' => 'Contact Us',
    ];

    public function terms(): View
    {
        return $this->show('terms');
    }

    public function privacy(): View
    {
        return $this->show('privacy');
    }

    public function copyright(): View
    {
        return $this->show('copyright');
    }

    public function contact(): View
    {
        return $this->show('contact');
    }

    private function show(string $slug): View
    {
        $markdown = file_get_contents(resource_path("markdown/{$slug}.md"));

        return view('legal.show', [
            'title' => self::PAGES[$slug],
            'content' => Str::markdown($markdown, ['html_input' => 'strip']),
        ]);
    }
}
