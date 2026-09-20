<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember border border-transparent rounded-lg font-display font-semibold text-sm text-jd-bg dark:text-pine hover:bg-cypress dark:hover:bg-ember-bright focus:bg-cypress dark:focus:bg-ember-bright active:bg-pine dark:active:bg-ember focus:outline-none focus:ring-2 focus:ring-moss dark:focus:ring-ember focus:ring-offset-2 dark:focus:ring-offset-abyss active:scale-95 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
