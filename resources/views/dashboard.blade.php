<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border border-pine/10 overflow-hidden shadow-sm sm:rounded-xl">
                <div class="p-6 text-pine">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
