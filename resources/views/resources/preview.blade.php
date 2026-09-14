<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-mono font-semibold text-moss dark:text-sage uppercase tracking-wide">{{ $resource->resourceType->name }}</p>
                <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">{{ $resource->title }}</h2>
            </div>
            <a href="{{ auth()->user()->isAdmin() ? route('admin.moderation.index') : route('resources.mine') }}"
               class="shrink-0 text-sm font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">
                &larr; {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success dark:text-sage px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <span @class([
                    'inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                    'bg-jd-warning/10 text-jd-warning' => $resource->status === 'pending',
                    'bg-jd-success/10 text-jd-success dark:text-sage' => $resource->status === 'approved',
                    'bg-jd-danger/10 text-jd-danger' => $resource->status === 'rejected',
                ])>
                    <span @class([
                        'w-1.5 h-1.5 rounded-full',
                        'bg-jd-warning' => $resource->status === 'pending',
                        'bg-jd-success' => $resource->status === 'approved',
                        'bg-jd-danger' => $resource->status === 'rejected',
                    ])></span>
                    {{ ucfirst($resource->status) }}
                </span>
                <span class="text-jd-ink-muted dark:text-sage font-mono">{{ __('Uploaded by') }} {{ $resource->uploaderDisplayName() }}</span>
                @if ($resource->pagesLabel())
                    <span class="text-jd-ink-muted dark:text-sage font-mono">{{ $resource->pagesLabel() }}</span>
                @endif
                <span class="text-jd-ink-muted dark:text-sage font-mono uppercase">{{ $resource->format }}</span>
            </div>

            @if ($resource->description)
                <p class="text-sm text-jd-ink-muted dark:text-sage font-serif">{{ $resource->description }}</p>
            @endif

            <x-document-reader :resource="$resource" />

            @if (auth()->user()->isAdmin() && $resource->status === 'pending')
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm p-4 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('admin.moderation.approve', $resource) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-moss dark:bg-sage text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-mint transition">
                            {{ __('Approve') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.moderation.reject', $resource) }}" class="flex items-center gap-2 flex-1 min-w-[240px]" onsubmit="return this.querySelector('[name=rejected_reason]').value.trim() !== ''">
                        @csrf
                        <input type="text" name="rejected_reason" placeholder="{{ __('Reason for rejection') }}" class="flex-1 text-sm rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-jd-danger focus:ring-jd-danger font-display" required>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-transparent border border-jd-danger text-jd-danger rounded-lg text-sm font-display font-semibold hover:bg-jd-danger hover:text-white transition">
                            {{ __('Reject') }}
                        </button>
                    </form>
                </div>
            @elseif ($resource->status === 'rejected' && $resource->rejected_reason)
                <div class="rounded-lg bg-jd-danger/10 border border-jd-danger/20 text-jd-danger px-4 py-3 text-sm">
                    {{ __('Rejected:') }} {{ $resource->rejected_reason }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
