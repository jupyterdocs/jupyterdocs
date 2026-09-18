<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('User Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.users.index') }}" class="text-sm font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">
                &larr; {{ __('Back to Users') }}
            </a>

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Profile header --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="font-display font-bold text-xl text-pine dark:text-mint">{{ $user->name }}</h3>
                        <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage">{{ ucfirst($user->role) }}</span>
                        @if ($user->isDeactivated())
                            <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-danger/10 text-jd-danger">{{ __('Deactivated') }}</span>
                        @elseif ($user->isOnline())
                            <span class="inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-success/10 text-jd-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-jd-success"></span>
                                {{ __('Online') }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 text-sm text-jd-ink-muted dark:text-sage font-mono">{{ $user->email }}</div>
                    <div class="mt-2 text-xs text-jd-ink-muted dark:text-sage font-mono space-x-3">
                        <span>{{ __('Joined') }} {{ $user->created_at->format('M j, Y') }}</span>
                        <span>&middot;</span>
                        <span>
                            @if ($user->last_seen_at)
                                {{ __('Last seen') }} {{ $user->last_seen_at->diffForHumans() }}
                            @else
                                {{ __('Never logged in') }}
                            @endif
                        </span>
                        <span>&middot;</span>
                        <span>{{ $user->email_verified_at ? __('Email verified') : __('Email not verified') }}</span>
                    </div>
                </div>

                @if ($user->id !== auth()->id())
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.users.role', $user) }}">
                            @csrf
                            @method('patch')
                            <select name="role" onchange="this.form.submit()" class="text-sm rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss focus:ring-moss font-display">
                                <option value="student" @selected($user->role === 'student')>{{ __('Student') }}</option>
                                <option value="admin" @selected($user->role === 'admin')>{{ __('Admin') }}</option>
                            </select>
                        </form>

                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('{{ __('Generate a new temporary password for this user? Their current password will stop working immediately.') }}')">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-lg text-sm font-display font-semibold border border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress whitespace-nowrap">
                                {{ __('Reset Password') }}
                            </button>
                        </form>

                        @unless ($user->isAdmin())
                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" onsubmit="return confirm('{{ $user->isDeactivated() ? __('Reactivate this account?') : __('Deactivate this account? They will be signed out immediately and cannot log back in until reactivated.') }}')">
                            @csrf
                            @method('patch')
                            <button type="submit" @class([
                                'px-3 py-2 rounded-lg text-sm font-display font-semibold border whitespace-nowrap',
                                'border-jd-success/30 text-jd-success hover:bg-jd-success/10' => $user->isDeactivated(),
                                'border-jd-danger/30 text-jd-danger hover:bg-jd-danger/10' => ! $user->isDeactivated(),
                            ])>{{ $user->isDeactivated() ? __('Reactivate Account') : __('Deactivate Account') }}</button>
                        </form>
                        @endunless
                    </div>
                @endif
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <div class="text-xs font-display font-medium text-jd-ink-muted dark:text-sage uppercase tracking-wide">{{ __('Time on System') }}</div>
                    <div class="mt-1 text-2xl font-display font-bold text-pine dark:text-mint">{{ $user->formattedTotalActiveTime() }}</div>
                </div>
                <x-admin.stat-card label="Uploads" :value="$user->resources_count" />
                <x-admin.stat-card label="Approved" :value="$user->approved_uploads_count" accent="jd-success" />
                <x-admin.stat-card label="Downloads" :value="$user->downloads_count" />
            </div>

            {{-- 30-day activity chart --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                <h3 class="font-display font-semibold text-pine dark:text-mint mb-4">{{ __('Activity, Last 30 Days') }}</h3>
                @if ($activityByDay->isEmpty())
                    <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('No recorded activity yet.') }}</p>
                @else
                    @php $maxSeconds = max(1, $activityByDay->max('seconds_active')); @endphp
                    <div class="flex items-end gap-1 h-32 overflow-x-auto">
                        @foreach ($activityByDay as $day)
                            <div class="flex flex-col items-center justify-end h-full shrink-0" style="width: 18px" title="{{ $day->activity_date->format('M j') }}: {{ round($day->seconds_active / 60, 1) }} min">
                                <div class="w-2.5 bg-moss dark:bg-sage rounded-t-sm" style="height: {{ max(3, round($day->seconds_active / $maxSeconds * 100)) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Uploads --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Uploads') }}</h3>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($uploads as $resource)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $resource->resourceType->name }} &middot; {{ $resource->created_at->diffForHumans() }}</div>
                                </div>
                                <span @class([
                                    'text-xs font-display font-semibold px-2.5 py-1 rounded-full shrink-0',
                                    'bg-jd-warning/10 text-jd-warning' => $resource->status === 'pending',
                                    'bg-jd-success/10 text-jd-success' => $resource->status === 'approved',
                                    'bg-jd-danger/10 text-jd-danger' => $resource->status === 'rejected',
                                ])>{{ ucfirst($resource->status) }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No uploads yet.') }}</p>
                        @endforelse
                    </div>
                    <div class="p-4">{{ $uploads->links() }}</div>
                </div>

                {{-- Downloads --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Downloads') }}</h3>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($downloads as $download)
                            <div class="p-4 flex items-center justify-between gap-4">
                                @if ($download->resource)
                                    <a href="{{ route('resources.show', $download->resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $download->resource->title }}</a>
                                @else
                                    <span class="text-jd-ink-muted dark:text-sage italic">{{ __('Deleted document') }}</span>
                                @endif
                                <span class="text-xs text-jd-ink-muted dark:text-sage font-mono shrink-0">{{ $download->downloaded_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No downloads yet.') }}</p>
                        @endforelse
                    </div>
                    <div class="p-4">{{ $downloads->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
