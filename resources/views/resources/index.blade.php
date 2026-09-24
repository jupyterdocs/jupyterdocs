@php
    $seoTitle = $q
        ? 'Search results for "'.$q.'"'
        : ($selectedType && isset($resourceTypes) && $resourceTypes->firstWhere('id', $selectedType)
            ? $resourceTypes->firstWhere('id', $selectedType)->name.' — Browse'
            : 'Browse Lecture Notes, Past Papers & Study Guides');
    $seoDescription = $q
        ? 'Search results for "'.$q.'" — free lecture notes, past papers, and study guides on JupyterDocs.'
        : 'Browse thousands of student-uploaded lecture notes, past exam papers, and study guides. Full-text search across every course and university.';
@endphp

<x-app-layout
    :title="$seoTitle"
    :description="$seoDescription"
    :canonical="url()->full()"
>
    <x-slot name="structuredData">
        @if ($resources->previousPageUrl())
            <link rel="prev" href="{{ $resources->previousPageUrl() }}">
        @endif
        @if ($resources->nextPageUrl())
            <link rel="next" href="{{ $resources->nextPageUrl() }}">
        @endif
    </x-slot>

    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ $q ? __('Search results for ":q"', ['q' => $q]) : __('Browse Resources') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success dark:text-sage px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('resources.index') }}" class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4 flex flex-wrap gap-3 items-end">
                <x-search-box :value="$q" />
                <div>
                    <label class="block text-sm font-display font-medium text-pine dark:text-mint">{{ __('Type') }}</label>
                    <select name="type" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm font-display">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($resourceTypes as $type)
                            <option value="{{ $type->id }}" @selected($selectedType == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright active:scale-95 transition">
                    {{ __('Search') }}
                </button>
            </form>

            @if ($search)
                <div class="space-y-1 text-sm">
                    @if ($search->wasCorrected())
                        <p class="text-pine dark:text-mint">
                            {{ __('Showing results for') }}
                            <a href="{{ route('resources.index', array_filter(['q' => $search->correctedQuery, 'type' => $selectedType])) }}" class="font-display font-semibold italic text-moss dark:text-sage hover:underline">{{ $search->correctedQuery }}</a>
                        </p>
                        <p class="text-jd-ink-muted dark:text-sage">
                            {{ __('Search instead for') }}
                            <a href="{{ route('resources.index', array_filter(['q' => $q, 'type' => $selectedType, 'exact' => 1])) }}" class="hover:underline">{{ $q }}</a>
                        </p>
                    @endif
                    <p class="text-jd-ink-muted dark:text-sage font-mono text-xs">
                        {{ trans_choice('{0} No results|{1} 1 result|[2,*] :count results', $search->total(), ['count' => number_format($search->total())]) }}
                    </p>
                </div>
            @endif

            @auth
                @if (Auth::user()->canDownload())
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress border border-moss/20 dark:border-sage/20 text-pine dark:text-mint px-4 py-3 text-sm">
                        @if (Auth::user()->hasFreeDownload())
                            {{ __('You have 1 free download available. Every :n documents you upload earns you another.', ['n' => \App\Models\Resource::UPLOADS_PER_DOWNLOAD]) }}
                        @else
                            {{ trans_choice('You have :count download available.|You have :count downloads available.', Auth::user()->downloadsRemaining()) }}
                        @endif
                    </div>
                @else
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-2">
                                @php $progress = Auth::user()->uploads_count - Auth::user()->downloads_used * \App\Models\Resource::UPLOADS_PER_DOWNLOAD; @endphp
                                @for ($i = 1; $i <= \App\Models\Resource::UPLOADS_PER_DOWNLOAD; $i++)
                                    <span class="w-3.5 h-3.5 rounded-full border-2 border-moss dark:border-sage {{ $i <= $progress ? 'bg-moss dark:bg-sage' : '' }}"></span>
                                @endfor
                            </div>
                            <p class="text-sm text-jd-ink-muted dark:text-sage">
                                {{ __('You have no downloads left. Upload :n more document(s) to earn another — no admin approval needed.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                            </p>
                        </div>
                        <a href="{{ route('resources.create') }}" class="text-sm font-display font-semibold text-moss dark:text-sage hover:text-cypress dark:hover:text-mint shrink-0">{{ __('Upload now →') }}</a>
                    </div>
                @endif
            @endauth

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @forelse ($resources as $resource)
                    <div class="flex flex-col bg-white dark:bg-pine shadow-sm rounded-xl overflow-hidden hover:shadow-md transition border border-pine/10 dark:border-mint/10">
                        <a href="{{ route('resources.show', $resource) }}" class="block">
                        <div class="relative bg-jd-surface-2 dark:bg-cypress h-48 flex items-center justify-center overflow-hidden">
                            <span class="absolute top-3 left-3 z-10 bg-pine dark:bg-abyss text-jd-bg dark:text-mint text-xs font-mono font-bold px-2 py-1 rounded">
                                {{ strtoupper($resource->format) }}
                            </span>
                            @if ($resource->thumbnailUrl())
                                <img src="{{ $resource->thumbnailUrl() }}" alt="{{ $resource->title }} thumbnail" class="w-full h-full object-cover object-top" loading="lazy">
                            @else
                                <svg class="w-16 h-16 text-sage/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="px-4 pt-4">
                            <h3 class="font-display font-bold text-pine dark:text-mint line-clamp-2 leading-snug">{!! $highlighter ? $highlighter->highlight($resource->title) : e($resource->title) !!}</h3>
                            @if ($highlighter && $resource->description)
                                <p class="mt-1 text-xs text-jd-ink-muted dark:text-sage line-clamp-3">{!! $highlighter->snippet($resource->description) !!}</p>
                            @endif
                            <p class="mt-2 text-sm text-jd-ink-muted dark:text-sage font-serif">{{ __('Added by') }} {{ $resource->uploaderDisplayName() }}</p>
                        </div>
                        </a>
                        <div class="mt-auto px-4 pb-3 pt-2 flex items-center justify-between gap-2 text-sm text-jd-ink-muted dark:text-sage font-mono">
                            <span class="min-w-0 truncate">{{ $resource->pagesLabel() ?? $resource->resourceType->name }} &middot; <span class="text-xs">{{ $resource->downloads_count }} {{ __('downloads') }}</span></span>
                            <x-save-button :resource="$resource" :saved="in_array($resource->id, $savedIds, true)" class="shrink-0 -me-2" />
                        </div>
                    </div>
                @empty
                    @if ($q)
                        <div class="col-span-full rounded-xl border border-pine/10 dark:border-mint/10 bg-white dark:bg-pine p-6 text-sm text-jd-ink-muted dark:text-sage space-y-2">
                            <p class="font-display font-semibold text-pine dark:text-mint">{{ __('No documents match “:q”.', ['q' => $q]) }}</p>
                            <ul class="list-disc ps-5 space-y-1">
                                <li>{{ __('Check the spelling, or try a different word for the same thing.') }}</li>
                                <li>{{ __('Use fewer or more general words — "calculus" instead of "calculus 2 final exam solutions".') }}</li>
                                <li>{{ __('Remove quotation marks or minus signs; they make the search stricter.') }}</li>
                                <li>{{ __('Set the type back to "All types".') }}</li>
                            </ul>
                        </div>
                    @else
                        <p class="text-jd-ink-muted dark:text-sage col-span-full">{{ __('No resources found yet.') }}</p>
                    @endif
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
