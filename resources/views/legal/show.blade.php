<x-app-layout :title="$title" :canonical="url()->current()">
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ $title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-6 sm:p-10">
                <div class="legal-content">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
