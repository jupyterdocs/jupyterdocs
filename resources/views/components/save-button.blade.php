@props(['resource', 'saved' => false])

{{-- Bookmark toggle. Saving keeps the document in "Saved" for later; it never downloads it. --}}
<form method="POST" action="{{ route('resources.save', $resource) }}" data-save-form data-resource="{{ $resource->id }}" {{ $attributes->only('class') }}>
    @csrf
    <button type="submit"
            aria-pressed="{{ $saved ? 'true' : 'false' }}"
            title="{{ $saved ? __('Remove from saved') : __('Save for later') }}"
            class="group inline-flex items-center justify-center w-10 h-10 rounded-full text-pine dark:text-mint hover:bg-jd-surface-2 dark:hover:bg-cypress aria-pressed:text-moss dark:aria-pressed:text-sage transition">
        <svg class="w-6 h-6 fill-none group-aria-pressed:fill-current" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h12a.75.75 0 0 1 .75.75v16.19a.375.375 0 0 1-.6.3L12 16.5l-6.15 4.49a.375.375 0 0 1-.6-.3V4.5A.75.75 0 0 1 6 3.75z" />
        </svg>
        <span class="sr-only" data-save-label>{{ $saved ? __('Saved') : __('Save') }}</span>
    </button>
</form>
