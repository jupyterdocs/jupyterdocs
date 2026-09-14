<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Browse Resources') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('home') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Title, description, course, tag..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                    <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($resourceTypes as $type)
                            <option value="{{ $type->id }}" @selected($selectedType == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                    {{ __('Search') }}
                </button>
            </form>

            @auth
                @if (! Auth::user()->canDownload())
                    <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                        {{ __('Upload :n more approved document(s) to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                        <a href="{{ route('resources.create') }}" class="font-semibold underline">{{ __('Upload now') }}</a>
                    </div>
                @endif
            @endauth

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @forelse ($resources as $resource)
                    <a href="{{ route('resources.show', $resource) }}" class="block bg-white shadow-sm rounded-xl overflow-hidden hover:shadow-md transition border border-gray-100">
                        <div class="relative bg-gray-50 h-48 flex items-center justify-center overflow-hidden">
                            <span class="absolute top-3 left-3 z-10 bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded">
                                {{ strtoupper($resource->format) }}
                            </span>
                            @if ($resource->thumbnailUrl())
                                <img src="{{ $resource->thumbnailUrl() }}" alt="" class="w-full h-full object-cover object-top">
                            @else
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 line-clamp-2 leading-snug">{{ $resource->title }}</h3>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Added by') }} {{ $resource->uploaderDisplayName() }}</p>
                            <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                                <span>{{ $resource->pagesLabel() ?? $resource->resourceType->name }}</span>
                                <span class="text-xs text-gray-400">{{ $resource->downloads_count }} {{ __('downloads') }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500 col-span-full">{{ __('No resources found yet.') }}</p>
                @endforelse
            </div>

            {{ $resources->links() }}
        </div>
    </div>
</x-app-layout>
