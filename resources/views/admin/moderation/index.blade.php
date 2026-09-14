<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Pending Resources') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl divide-y divide-pine/10 dark:divide-mint/10">
                @forelse ($resources as $resource)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="shrink-0 w-16 h-20 bg-jd-surface-2 dark:bg-cypress rounded border border-pine/10 dark:border-mint/10 overflow-hidden flex items-center justify-center">
                                @if ($resource->thumbnailUrl())
                                    <img src="{{ $resource->thumbnailUrl() }}" alt="" class="w-full h-full object-cover object-top">
                                @else
                                    <span class="text-[10px] font-mono font-bold text-jd-ink-muted dark:text-sage">{{ strtoupper($resource->format) }}</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">
                                    {{ $resource->resourceType->name }}
                                    @if ($resource->course) &middot; {{ $resource->course->name }} @endif
                                    &middot; {{ __('by') }} {{ $resource->uploaderDisplayName() }}
                                    &middot; {{ $resource->created_at->diffForHumans() }}
                                </div>
                                @if ($resource->description)
                                    <p class="text-sm text-jd-ink-muted dark:text-sage font-serif mt-1">{{ $resource->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a href="{{ route('resources.preview', $resource) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center px-3 py-1.5 bg-transparent border border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage rounded-lg text-xs font-display font-semibold hover:bg-jd-surface-2">
                                {{ __('Preview') }}
                            </a>

                            <form method="POST" action="{{ route('admin.moderation.approve', $resource) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-moss dark:bg-sage text-jd-bg dark:text-pine rounded-lg text-xs font-display font-semibold hover:bg-cypress dark:hover:bg-mint">
                                    {{ __('Approve') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.moderation.reject', $resource) }}" class="flex items-center gap-2" onsubmit="return this.querySelector('[name=rejected_reason]').value.trim() !== ''">
                                @csrf
                                <input type="text" name="rejected_reason" placeholder="{{ __('Reason for rejection') }}" class="text-xs rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-jd-danger focus:ring-jd-danger font-display" required>
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-transparent border border-jd-danger text-jd-danger rounded-lg text-xs font-display font-semibold hover:bg-jd-danger hover:text-white">
                                    {{ __('Reject') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('Nothing pending review.') }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
