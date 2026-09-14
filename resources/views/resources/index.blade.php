<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine leading-tight">
            {{ __('Browse Resources') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('home') }}" class="bg-white border border-pine/10 shadow-sm rounded-xl p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-display font-medium text-pine">{{ __('Search') }}</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Title, description, course, tag..."
                           class="mt-1 block w-full rounded-lg border-pine/15 focus:border-moss focus:ring-moss text-sm font-display">
                </div>
                <div>
                    <label class="block text-sm font-display font-medium text-pine">{{ __('Type') }}</label>
                    <select name="type" class="mt-1 block w-full rounded-lg border-pine/15 focus:border-moss focus:ring-moss text-sm font-display">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($resourceTypes as $type)
                            <option value="{{ $type->id }}" @selected($selectedType == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss text-jd-bg rounded-lg text-sm font-display font-semibold hover:bg-cypress transition">
                    {{ __('Search') }}
                </button>
            </form>

            @auth
                @if (! Auth::user()->canDownload())
                    <div class="bg-white border border-pine/10 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-2">
                                @for ($i = 1; $i <= 3; $i++)
                                    <span class="w-3.5 h-3.5 rounded-full border-2 border-moss {{ $i <= Auth::user()->approved_uploads_count ? 'bg-moss' : '' }}"></span>
                                @endfor
                            </div>
                            <p class="text-sm text-jd-ink-muted">
                                {{ __('Upload :n more approved document(s) to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                            </p>
                        </div>
                        <a href="{{ route('resources.create') }}" class="text-sm font-display font-semibold text-moss hover:text-cypress shrink-0">{{ __('Upload now →') }}</a>
                    </div>
                @endif
            @endauth

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @forelse ($resources as $resource)
                    <a href="{{ route('resources.show', $resource) }}" class="block bg-white shadow-sm rounded-xl overflow-hidden hover:shadow-md transition border border-pine/10">
                        <div class="relative bg-jd-surface-2 h-48 flex items-center justify-center overflow-hidden">
                            <span class="absolute top-3 left-3 z-10 bg-pine text-jd-bg text-xs font-mono font-bold px-2 py-1 rounded">
                                {{ strtoupper($resource->format) }}
                            </span>
                            @if ($resource->thumbnailUrl())
                                <img src="{{ $resource->thumbnailUrl() }}" alt="" class="w-full h-full object-cover object-top">
                            @else
                                <svg class="w-16 h-16 text-sage/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-display font-bold text-pine line-clamp-2 leading-snug">{{ $resource->title }}</h3>
                            <p class="mt-2 text-sm text-jd-ink-muted font-serif">{{ __('Added by') }} {{ $resource->uploaderDisplayName() }}</p>
                            <div class="mt-3 flex items-center justify-between text-sm text-jd-ink-muted font-mono">
                                <span>{{ $resource->pagesLabel() ?? $resource->resourceType->name }}</span>
                                <span class="text-xs">{{ $resource->downloads_count }} {{ __('downloads') }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-jd-ink-muted col-span-full">{{ __('No resources found yet.') }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
