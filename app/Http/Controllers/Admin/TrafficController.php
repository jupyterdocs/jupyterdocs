<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Support\Traffic\CountryName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrafficController extends Controller
{
    private const RANGES = [7, 30, 90];

    public function index(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, self::RANGES, true) ? $days : 30;

        $from = now()->subDays($days - 1)->toDateString();
        $today = now()->toDateString();

        $window = fn () => PageVisit::where('visit_date', '>=', $from);

        // Unique visitors per day, split into signed-in members and guests.
        $perDay = PageVisit::where('visit_date', '>=', $from)
            ->select(
                'visit_date',
                DB::raw('count(*) as views'),
                DB::raw('count(distinct visitor_hash) as visitors'),
                DB::raw('count(distinct case when user_id is not null then visitor_hash end) as members'),
            )
            ->groupBy('visit_date')
            ->get()
            ->keyBy(fn ($row) => $row->visit_date->toDateString());

        $daily = collect(range(0, $days - 1))->map(function ($i) use ($days, $perDay) {
            $date = now()->subDays($days - 1 - $i);
            $row = $perDay->get($date->toDateString());
            $visitors = (int) ($row->visitors ?? 0);
            $members = (int) ($row->members ?? 0);

            return [
                'date' => $date,
                'label' => $date->format('M j'),
                'views' => (int) ($row->views ?? 0),
                'visitors' => $visitors,
                'members' => $members,
                'guests' => max(0, $visitors - $members),
            ];
        });

        $todayRow = $daily->last();

        // Every visitor is counted once per day, so "total visitors" is the
        // sum of daily uniques (the same person on two days counts twice).
        $totals = [
            'views' => $daily->sum('views'),
            'visitors' => $daily->sum('visitors'),
            'members' => $daily->sum('members'),
            'guests' => $daily->sum('guests'),
        ];

        $countries = $window()
            ->select('country_code', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_hash) as visitors'))
            ->groupBy('country_code')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->country_code,
                'name' => CountryName::for($row->country_code),
                'flag' => CountryName::flag($row->country_code),
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
            ]);

        $known = $countries->filter(fn ($c) => $c['code'] !== null)->values();
        $viewsTotal = max(1, $countries->sum('views'));

        $cities = $window()
            ->whereNotNull('city')
            ->select('city', 'country_code', DB::raw('count(*) as views'))
            ->groupBy('city', 'country_code')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $topPages = $window()
            ->select('path', DB::raw('count(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return view('admin.traffic.index', [
            'days' => $days,
            'ranges' => self::RANGES,
            'daily' => $daily,
            'today' => $todayRow,
            'totals' => $totals,
            'countries' => $countries->take(15)->values(),
            'topCountry' => $known->first(),
            'unknownViews' => (int) optional($countries->firstWhere('code', null))['views'],
            'viewsTotal' => $viewsTotal,
            'cities' => $cities,
            'topPages' => $topPages,
            'hasData' => $totals['views'] > 0,
        ]);
    }
}
