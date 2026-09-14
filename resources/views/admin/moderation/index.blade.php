<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pending Resources') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($resources as $resource)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="shrink-0 w-16 h-20 bg-gray-50 rounded border border-gray-100 overflow-hidden flex items-center justify-center">
                                @if ($resource->thumbnailUrl())
                                    <img src="{{ $resource->thumbnailUrl() }}" alt="" class="w-full h-full object-cover object-top">
                                @else
                                    <span class="text-[10px] font-bold text-gray-400">{{ strtoupper($resource->format) }}</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <a href="{{ route('resources.show', $resource) }}" class="font-medium text-gray-900 hover:underline">{{ $resource->title }}</a>
                                <div class="text-xs text-gray-500">
                                    {{ $resource->resourceType->name }}
                                    @if ($resource->course) &middot; {{ $resource->course->name }} @endif
                                    &middot; {{ __('by') }} {{ $resource->uploaderDisplayName() }}
                                    &middot; {{ $resource->created_at->diffForHumans() }}
                                </div>
                                @if ($resource->description)
                                    <p class="text-sm text-gray-600 mt-1">{{ $resource->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a href="{{ route('resources.preview', $resource) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center px-3 py-1.5 bg-gray-700 text-white rounded-md text-xs font-semibold hover:bg-gray-800">
                                {{ __('Preview') }}
                            </a>

                            <form method="POST" action="{{ route('admin.moderation.approve', $resource) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700">
                                    {{ __('Approve') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.moderation.reject', $resource) }}" class="flex items-center gap-2" onsubmit="return this.querySelector('[name=rejected_reason]').value.trim() !== ''">
                                @csrf
                                <input type="text" name="rejected_reason" placeholder="{{ __('Reason for rejection') }}" class="text-xs rounded-md border-gray-300 shadow-sm" required>
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-semibold hover:bg-red-700">
                                    {{ __('Reject') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-gray-500">{{ __('Nothing pending review.') }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
