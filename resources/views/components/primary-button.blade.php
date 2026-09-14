<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-moss border border-transparent rounded-lg font-display font-semibold text-sm text-jd-bg hover:bg-cypress focus:bg-cypress active:bg-pine focus:outline-none focus:ring-2 focus:ring-moss focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
