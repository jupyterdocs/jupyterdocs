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
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-display font-medium text-pine dark:text-mint">{{ __('Search') }}</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Title, description, course, tag..."
                           class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint placeholder:text-jd-ink-muted/60 dark:placeholder-sage/60 focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm font-display">
                </div>
                <div>
                    <label class="block text-sm font-display font-medium text-pine dark:text-mint">{{ __('Type') }}</label>
                    <select name="type" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm font-display">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($resourceTypes as $type)
                            <option value="{{ $type->id }}" @selected($selectedType == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright transition">
                    {{ __('Search') }}
                </button>
            </form>

            @auth
                @if (! Auth::user()->canDownload())
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-2">
                                @for ($i = 1; $i <= 3; $i++)
                                    <span class="w-3.5 h-3.5 rounded-full border-2 border-moss dark:border-sage {{ $i <= Auth::user()->approved_uploads_count ? 'bg-moss dark:bg-sage' : '' }}"></span>
                                @endfor
                            </div>
                            <p class="text-sm text-jd-ink-muted dark:text-sage">
                                {{ __('Upload :n more approved document(s) to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
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
                            <h3 class="font-display font-bold text-pine dark:text-mint line-clamp-2 leading-snug">{{ $resource->title }}</h3>
                            <p class="mt-2 text-sm text-jd-ink-muted dark:text-sage font-serif">{{ __('Added by') }} {{ $resource->uploaderDisplayName() }}</p>
                        </div>
                        </a>
                        <div class="mt-auto px-4 pb-3 pt-2 flex items-center justify-between gap-2 text-sm text-jd-ink-muted dark:text-sage font-mono">
                            <span class="min-w-0 truncate">{{ $resource->pagesLabel() ?? $resource->resourceType->name }} &middot; <span class="text-xs">{{ $resource->downloads_count }} {{ __('downloads') }}</span></span>
                            <x-save-button :resource="$resource" :saved="in_array($resource->id, $savedIds, true)" class="shrink-0 -me-2" />
                        </div>
                    </div>
                @empty
                    <p class="text-jd-ink-muted dark:text-sage col-span-full">{{ __('No resources found yet.') }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
