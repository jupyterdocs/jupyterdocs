<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120 Safari/537.36';

    private function visit(string $uri = '/browse', array $headers = [], ?User $as = null)
    {
        $request = $as ? $this->actingAs($as) : $this;

        return $request->withHeaders(array_merge(['User-Agent' => self::BROWSER], $headers))->get($uri);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    public function test_guest_page_view_is_recorded_with_country(): void
    {
        $this->visit('/browse', ['CF-IPCountry' => 'ng'])->assertOk();

        $this->assertDatabaseHas('page_visits', [
            'user_id' => null,
            'country_code' => 'NG',
            'path' => '/browse',
        ]);
    }

    public function test_signed_in_visit_is_linked_to_the_user(): void
    {
        $user = User::factory()->create();

        $this->visit('/browse', [], $user)->assertOk();

        $this->assertDatabaseHas('page_visits', ['user_id' => $user->id]);
    }

    public function test_bots_json_and_admin_pages_are_not_recorded(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/browse');
        $this->visit('/search/suggest?q=math');
        $this->visit('/sitemap.xml');
        $this->visit('/browse', ['X-Requested-With' => 'XMLHttpRequest']);
        $this->visit('/admin', [], $this->admin());

        $this->assertSame(0, PageVisit::count());
    }

    public function test_unknown_country_markers_are_stored_as_null(): void
    {
        $this->visit('/browse', ['CF-IPCountry' => 'XX']);

        $this->assertNull(PageVisit::first()->country_code);
    }

    public function test_same_visitor_shares_a_hash_and_no_raw_ip_is_stored(): void
    {
        $this->visit('/browse');
        $this->visit('/terms');

        $this->assertSame(1, PageVisit::distinct()->count('visitor_hash'));
        $this->assertSame(64, strlen(PageVisit::first()->visitor_hash));
    }

    public function test_traffic_page_shows_top_country_and_daily_split(): void
    {
        $member = User::factory()->create();

        foreach ([['NG', 'a'], ['NG', 'a'], ['NG', 'b'], ['GH', 'c']] as [$country, $hash]) {
            PageVisit::create([
                'visit_date' => now()->toDateString(), 'visitor_hash' => $hash,
                'country_code' => $country, 'path' => '/browse',
            ]);
        }
        PageVisit::create([
            'visit_date' => now()->toDateString(), 'visitor_hash' => 'm', 'user_id' => $member->id,
            'country_code' => 'GH', 'path' => '/browse',
        ]);

        $this->actingAs($this->admin())->get(route('admin.traffic.index'))
            ->assertOk()
            ->assertSee('Nigeria')
            ->assertSee('Most page visits come from')
            ->assertViewHas('today', fn ($t) => $t['visitors'] === 4 && $t['members'] === 1 && $t['guests'] === 3 && $t['views'] === 5)
            ->assertViewHas('topCountry', fn ($c) => $c['code'] === 'NG' && $c['views'] === 3);
    }

    public function test_traffic_page_is_admin_only_and_handles_no_data(): void
    {
        $this->get(route('admin.traffic.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.traffic.index'))->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.traffic.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('No visits recorded yet');
    }
}
