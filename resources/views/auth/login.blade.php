<x-guest-layout>
    <div class="mb-5">
        <h1 class="font-display font-bold text-xl text-pine dark:text-mint">Welcome back</h1>
        <p class="mt-1.5 text-sm text-jd-ink-muted dark:text-sage font-serif italic">Notes orbit. Knowledge compounds.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <x-google-auth-button :label="__('Continue with Google')" />

    <div class="flex items-center gap-3 my-5">
        <div class="h-px flex-1 bg-pine/10 dark:bg-mint/10"></div>
        <span class="text-xs text-jd-ink-muted dark:text-sage">{{ __('or') }}</span>
        <div class="h-px flex-1 bg-pine/10 dark:bg-mint/10"></div>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-pine/20 dark:border-mint/20 text-moss dark:text-sage shadow-sm focus:ring-moss" name="remember">
                <span class="ms-2 text-sm text-jd-ink-muted dark:text-sage">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="text-sm text-jd-ink-muted dark:text-sage hover:text-pine rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
