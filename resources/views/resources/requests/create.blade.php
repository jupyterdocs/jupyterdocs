<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Request a Document') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-6">

                <form method="POST" action="{{ route('requests.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('What are you looking for?')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required placeholder="e.g. Mathematics for IT Professionals — 2nd Edition" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('More detail (optional)')" />
                        <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display" placeholder="Edition, year, chapters, lecturer — anything that helps someone recognize it.">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="resource_type_id" :value="__('Resource type (optional)')" />
                        <select id="resource_type_id" name="resource_type_id" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display">
                            <option value="">{{ __('Not sure') }}</option>
                            @foreach ($resourceTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('resource_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('resource_type_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="course" :value="__('Course (optional)')" />
                            <x-text-input id="course" name="course" type="text" class="mt-1 block w-full" :value="old('course')" placeholder="e.g. Database Management Systems II" />
                        </div>
                        <div>
                            <x-input-label for="university" :value="__('University (optional)')" />
                            <x-text-input id="university" name="university" type="text" class="mt-1 block w-full" :value="old('university')" placeholder="e.g. MUST" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright active:scale-95 transition">
                            {{ __('Submit request') }}
                        </button>
                        <a href="{{ route('requests.index') }}" class="text-sm font-display text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
