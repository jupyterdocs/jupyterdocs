<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Uploads') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4 text-sm text-gray-600">
                {{ __('Approved uploads:') }} <span class="font-semibold text-gray-900">{{ Auth::user()->approved_uploads_count }}</span>
                @unless (Auth::user()->canDownload())
                    &mdash; {{ __(':n more to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                @else
                    &mdash; <span class="text-green-700 font-medium">{{ __('Downloads unlocked!') }}</span>
                @endunless
            </div>

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($resources as $resource)
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('resources.show', $resource) }}" class="font-medium text-gray-900 hover:underline">{{ $resource->title }}</a>
                            <div class="text-xs text-gray-500">{{ $resource->resourceType->name }} &middot; {{ $resource->created_at->diffForHumans() }}</div>
                            @if ($resource->status === 'rejected' && $resource->rejected_reason)
                                <div class="text-xs text-red-600 mt-1">{{ __('Rejected:') }} {{ $resource->rejected_reason }}</div>
                            @endif
                        </div>
                        <span @class([
                            'text-xs font-semibold px-2 py-1 rounded-full',
                            'bg-yellow-100 text-yellow-800' => $resource->status === 'pending',
                            'bg-green-100 text-green-800' => $resource->status === 'approved',
                            'bg-red-100 text-red-800' => $resource->status === 'rejected',
                        ])>{{ ucfirst($resource->status) }}</span>
                    </div>
                @empty
                    <p class="p-4 text-gray-500">{{ __("You haven't uploaded anything yet.") }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
