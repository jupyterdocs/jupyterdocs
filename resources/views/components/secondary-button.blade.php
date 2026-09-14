<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-transparent border border-moss dark:border-sage rounded-lg font-display font-semibold text-sm text-moss dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress focus:outline-none focus:ring-2 focus:ring-moss dark:focus:ring-sage focus:ring-offset-2 dark:focus:ring-offset-abyss disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
