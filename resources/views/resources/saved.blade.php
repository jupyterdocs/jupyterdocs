<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Saved Documents') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success dark:text-sage px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @forelse ($resources as $resource)
                    <div class="flex flex-col bg-white dark:bg-pine shadow-sm rounded-xl overflow-hidden hover:shadow-md transition border border-pine/10 dark:border-mint/10">
                        <a href="{{ route('resources.show', $resource) }}" class="block">
                            <div class="relative bg-jd-surface-2 dark:bg-cypress h-48 flex items-center justify-center overflow-hidden">
                                <span class="absolute top-3 left-3 z-10 bg-pine dark:bg-abyss text-jd-bg dark:text-mint text-xs font-mono font-bold px-2 py-1 rounded">
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
                            <div class="px-4 pt-4">
                                <h3 class="font-display font-bold text-pine dark:text-mint line-clamp-2 leading-snug">{{ $resource->title }}</h3>
                                <p class="mt-2 text-sm text-jd-ink-muted dark:text-sage font-serif">{{ __('Added by') }} {{ $resource->uploaderDisplayName() }}</p>
                            </div>
                        </a>
                        <div class="mt-auto px-4 pb-3 pt-2 flex items-center justify-between gap-2 text-sm text-jd-ink-muted dark:text-sage font-mono">
                            <span class="min-w-0 truncate text-xs">{{ __('Saved') }} {{ $resource->pivot->created_at->diffForHumans() }}</span>
                            <x-save-button :resource="$resource" :saved="true" class="shrink-0 -me-2" />
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl p-6 text-center">
                        <p class="text-jd-ink-muted dark:text-sage">{{ __("You haven't saved any documents yet. Tap the bookmark on a document to keep it here for later.") }}</p>
                        <a href="{{ route('resources.index') }}" class="mt-3 inline-block text-sm font-display font-semibold text-moss dark:text-sage hover:text-cypress dark:hover:text-mint">{{ __('Browse documents →') }}</a>
                    </div>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
