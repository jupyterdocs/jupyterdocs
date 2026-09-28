<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Edit Document Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-6">

                <div class="mb-5 pb-5 border-b border-pine/10 dark:border-mint/10 text-sm text-jd-ink-muted dark:text-sage font-mono">
                    <span class="uppercase">{{ $resource->format }}</span>
                    &middot; {{ __('uploaded by') }} {{ $resource->uploaderDisplayName() }}
                    &middot; {{ $resource->created_at->format('M j, Y') }}
                    &middot; {{ ucfirst($resource->status) }}
                </div>

                <form method="POST" action="{{ route('admin.resources.update', $resource) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="return_to" value="{{ old('return_to', $returnTo) }}">

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $resource->title)" required maxlength="255" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <x-input-label for="description" :value="__('Description')" />
                            <span id="description_count" class="text-xs font-mono text-jd-ink-muted dark:text-sage">0 / 150</span>
                        </div>
                        <textarea id="description" name="description" rows="5" minlength="2" maxlength="150" required class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display">{{ old('description', $resource->description) }}</textarea>
                        <p class="mt-1 text-xs text-jd-ink-muted dark:text-sage">{{ __('Up to 150 characters. Tags are regenerated from the title and description when you save.') }}</p>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="resource_type_id" :value="__('Resource type')" />
                        <select id="resource_type_id" name="resource_type_id" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display" required>
                            @foreach ($resourceTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('resource_type_id', $resource->resource_type_id) == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('resource_type_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright active:scale-95 transition">
                            {{ __('Save changes') }}
                        </button>
                        <a href="{{ route('resources.show', $resource) }}" class="px-4 py-2.5 rounded-lg border border-pine/20 dark:border-mint/20 text-sm font-display font-semibold text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const descriptionField = document.getElementById('description');
        const descriptionCount = document.getElementById('description_count');

        function updateDescriptionCount() {
            descriptionCount.textContent = `${descriptionField.value.length} / 150`;
        }

        descriptionField?.addEventListener('input', updateDescriptionCount);
        updateDescriptionCount();
    </script>
</x-app-layout>
