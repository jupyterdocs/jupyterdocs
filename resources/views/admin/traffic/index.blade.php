<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Traffic') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-jd-ink-muted dark:text-sage">
                    {{ __('Everyone who opens a page, signed in or not. Bots and admin pages are left out.') }}
                </p>
                <div class="flex gap-1">
                    @foreach ($ranges as $range)
                        <a href="{{ route('admin.traffic.index', ['days' => $range]) }}"
                           @class([
                               'px-3 py-1 rounded-lg text-xs font-display font-semibold transition',
                               'bg-moss dark:bg-sage text-jd-bg dark:text-pine' => $days === $range,
                               'text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => $days !== $range,
                           ])>{{ $range }} {{ __('days') }}</a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-admin.stat-card label="Visitors today" :value="$today['visitors']" />
                <x-admin.stat-card label="Signed-in today" :value="$today['members']" accent="jd-success" />
                <x-admin.stat-card label="Guests today" :value="$today['guests']" accent="jd-warning" />
                <x-admin.stat-card label="Page views today" :value="$today['views']" />
            </div>

            {{-- The answer to "where do most visits come from?" --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-5">
                <div class="text-xs font-display font-medium text-jd-ink-muted dark:text-sage uppercase tracking-wide">
                    {{ __('Most page visits come from') }} · {{ __('last :n days', ['n' => $days]) }}
                </div>
                @if ($topCountry)
                    <div class="mt-1 flex flex-wrap items-baseline gap-x-3">
                        <span class="text-3xl font-display font-bold text-pine dark:text-mint">{{ $topCountry['flag'] }} {{ $topCountry['name'] }}</span>
                        <span class="text-sm text-jd-ink-muted dark:text-sage">
                            {{ number_format($topCountry['views']) }} {{ __('page views') }}
                            ({{ round($topCountry['views'] / $viewsTotal * 100) }}%) ·
                            {{ number_format($topCountry['visitors']) }} {{ __('visitors') }}
                        </span>
                    </div>
                @else
                    <p class="mt-1 text-sm text-jd-ink-muted dark:text-sage">
                        @if ($hasData)
                            {{ __('Visits are being counted, but the hosting network is not sending visitor locations, so the country is unknown.') }}
                        @else
                            {{ __('No visits recorded yet. Counting starts as soon as the next page is opened.') }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <div class="flex items-baseline justify-between mb-4">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Daily visitors') }}</h3>
                        <span class="text-xs text-jd-ink-muted dark:text-sage">
                            {{ number_format($totals['members']) }} {{ __('signed in') }} · {{ number_format($totals['guests']) }} {{ __('guests') }}
                        </span>
                    </div>
                    <x-admin.line-chart
                        :labels="$daily->pluck('label')->all()"
                        :values="$daily->pluck('visitors')->all()"
                        :height="180"
                    />
                </div>

                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('Top countries') }}</h3>
                    <div class="space-y-3">
                        @forelse ($countries as $country)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-pine dark:text-mint truncate">
                                        {{ $country['flag'] }} {{ $country['code'] ? $country['name'] : __('Unknown') }}
                                    </span>
                                    <span class="text-jd-ink-muted dark:text-sage tabular-nums">{{ number_format($country['views']) }}</span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full bg-jd-surface-2 dark:bg-cypress">
                                    <div class="h-1.5 rounded-full bg-moss dark:bg-sage" style="width: {{ max(2, round($country['views'] / $viewsTotal * 100)) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('Nothing recorded yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('Day by day') }}</h3>
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs uppercase text-jd-ink-muted dark:text-sage">
                                <tr>
                                    <th class="py-1 font-medium">{{ __('Date') }}</th>
                                    <th class="py-1 font-medium text-right">{{ __('Visitors') }}</th>
                                    <th class="py-1 font-medium text-right">{{ __('Signed in') }}</th>
                                    <th class="py-1 font-medium text-right">{{ __('Guests') }}</th>
                                    <th class="py-1 font-medium text-right">{{ __('Views') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-pine dark:text-mint tabular-nums">
                                @foreach ($daily->reverse() as $row)
                                    <tr class="border-t border-pine/5 dark:border-mint/5">
                                        <td class="py-1.5">{{ $row['date']->format('D, M j') }}</td>
                                        <td class="py-1.5 text-right">{{ number_format($row['visitors']) }}</td>
                                        <td class="py-1.5 text-right">{{ number_format($row['members']) }}</td>
                                        <td class="py-1.5 text-right">{{ number_format($row['guests']) }}</td>
                                        <td class="py-1.5 text-right">{{ number_format($row['views']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                        <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('Top pages') }}</h3>
                        <div class="space-y-1.5 text-sm">
                            @forelse ($topPages as $page)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate font-mono text-xs text-pine dark:text-mint">{{ $page->path }}</span>
                                    <span class="text-jd-ink-muted dark:text-sage tabular-nums">{{ number_format($page->views) }}</span>
                                </div>
                            @empty
                                <p class="text-jd-ink-muted dark:text-sage">{{ __('Nothing recorded yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    @if ($cities->isNotEmpty())
                        <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                            <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('Top cities') }}</h3>
                            <div class="space-y-1.5 text-sm">
                                @foreach ($cities as $city)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-pine dark:text-mint">{{ $city->city }}@if ($city->country_code), {{ \App\Support\Traffic\CountryName::for($city->country_code) }}@endif</span>
                                        <span class="text-jd-ink-muted dark:text-sage tabular-nums">{{ number_format($city->views) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <p class="text-xs text-jd-ink-muted dark:text-sage">
                {{ __('Visitors are counted once per day. Location is taken from the network in front of the site; no IP addresses are stored.') }}
            </p>
        </div>
    </div>
</x-app-layout>
