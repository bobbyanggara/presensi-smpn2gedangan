<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 brand-bg border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest brand-bg-hover focus:outline-none transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
