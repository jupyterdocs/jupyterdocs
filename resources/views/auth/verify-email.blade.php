<x-guest-layout>
    <h1 class="font-display font-bold text-xl text-pine dark:text-mint mb-4">Verify your email</h1>

    <div class="mb-4 text-sm text-jd-ink-muted dark:text-sage">
        {{ __('We just emailed you a verification link — click it to confirm your address. Didn\'t get it? We can send another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-jd-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-jd-ink-muted dark:text-sage hover:text-pine rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
