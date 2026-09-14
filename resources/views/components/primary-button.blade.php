<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-moss dark:bg-sage border border-transparent rounded-lg font-display font-semibold text-sm text-jd-bg dark:text-pine hover:bg-cypress dark:hover:bg-mint focus:bg-cypress dark:focus:bg-mint active:bg-pine dark:active:bg-cypress focus:outline-none focus:ring-2 focus:ring-moss dark:focus:ring-sage focus:ring-offset-2 dark:focus:ring-offset-abyss transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
