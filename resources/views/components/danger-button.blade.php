<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-transparent border border-jd-danger rounded-lg font-display font-semibold text-sm text-jd-danger hover:bg-jd-danger hover:text-white focus:outline-none focus:ring-2 focus:ring-jd-danger focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
