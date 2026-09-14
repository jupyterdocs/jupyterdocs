<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine leading-tight">
            {{ __('My Uploads') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white border border-pine/10 shadow-sm rounded-xl p-4 text-sm text-jd-ink-muted">
                {{ __('Approved uploads:') }} <span class="font-display font-semibold text-pine">{{ Auth::user()->approved_uploads_count }}</span>
                @unless (Auth::user()->canDownload())
                    &mdash; {{ __(':n more to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                @else
                    &mdash; <span class="text-jd-success font-medium">{{ __('Downloads unlocked!') }}</span>
                @endunless
            </div>

            <div class="bg-white border border-pine/10 shadow-sm rounded-xl divide-y divide-pine/10">
                @forelse ($resources as $resource)
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine hover:underline">{{ $resource->title }}</a>
                            <div class="text-xs text-jd-ink-muted font-mono">{{ $resource->resourceType->name }} &middot; {{ $resource->created_at->diffForHumans() }}</div>
                            @if ($resource->status === 'rejected' && $resource->rejected_reason)
                                <div class="text-xs text-jd-danger mt-1">{{ __('Rejected:') }} {{ $resource->rejected_reason }}</div>
                            @endif
                        </div>
                        <span @class([
                            'inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                            'bg-jd-warning/10 text-jd-warning' => $resource->status === 'pending',
                            'bg-jd-success/10 text-jd-success' => $resource->status === 'approved',
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
                    </div>
                @empty
                    <p class="p-4 text-jd-ink-muted">{{ __("You haven't uploaded anything yet.") }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
