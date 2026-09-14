<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-transparent border border-moss rounded-lg font-display font-semibold text-sm text-moss hover:bg-jd-surface-2 focus:outline-none focus:ring-2 focus:ring-moss focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
