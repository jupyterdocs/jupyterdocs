<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <x-admin.stat-card label="Total Users" :value="$users->total()" />
                <x-admin.stat-card label="Online Now" :value="$onlineCount" accent="jd-success" />
                <x-admin.stat-card label="Avg Daily Active (30d)" :value="$avgDailyActiveUsers" />
                <x-admin.stat-card label="Admins" :value="$adminCount" />
                <div class="block bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4 opacity-60">
                    <div class="text-xs font-display font-medium text-jd-ink-muted dark:text-sage uppercase tracking-wide">{{ __('Paying Users') }}</div>
                    <div class="mt-1 text-2xl font-display font-bold text-pine dark:text-mint">0</div>
                    <div class="mt-0.5 text-[11px] text-jd-ink-muted dark:text-sage">{{ __('No payment system yet') }}</div>
                </div>
            </div>

            {{-- Registrations per year --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                <h3 class="font-display font-semibold text-pine dark:text-mint mb-4">{{ __('Users Gained Per Year') }}</h3>
                <x-admin.line-chart
                    :labels="$registrationsByYear->pluck('label')->all()"
                    :values="$registrationsByYear->pluck('value')->all()"
                />
            </div>

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-pine/10 dark:border-mint/10 text-left text-xs font-display font-semibold text-jd-ink-muted dark:text-sage uppercase tracking-wide">
                            <th class="p-4">{{ __('Name') }}</th>
                            <th class="p-4">{{ __('Role') }}</th>
                            <th class="p-4">{{ __('Status') }}</th>
                            <th class="p-4">{{ __('Time on System') }}</th>
                            <th class="p-4">{{ __('Uploads') }}</th>
                            <th class="p-4">{{ __('Approved') }}</th>
                            <th class="p-4">{{ __('Downloads') }}</th>
                            <th class="p-4">{{ __('Joined') }}</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-pine/10 dark:divide-mint/10">
                        @foreach ($users as $user)
                            <tr class="cursor-pointer hover:bg-jd-surface-2 dark:hover:bg-cypress transition"
                                onclick="window.location='{{ route('admin.users.show', $user) }}'">
                                <td class="p-4">
                                    <div class="font-display font-medium text-pine dark:text-mint">{{ $user->name }}</div>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->email }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td class="p-4">
                                    @if ($user->isDeactivated())
                                        <span class="inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-danger/10 text-jd-danger">
                                            {{ __('Deactivated') }}
                                        </span>
                                    @elseif ($user->isOnline())
                                        <span class="inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-success/10 text-jd-success">
                                            <span class="w-1.5 h-1.5 rounded-full bg-jd-success"></span>
                                            {{ __('Online') }}
                                        </span>
                                    @elseif ($user->last_seen_at)
                                        <span class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('Seen') }} {{ $user->last_seen_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('Never logged in') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->formattedTotalActiveTime() }}</td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->resources_count }}</td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->approved_uploads_count }}</td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->downloads_count }}</td>
                                <td class="p-4 text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->created_at->format('M j, Y') }}</td>
                                <td class="p-4" onclick="event.stopPropagation()">
                                    @if ($user->id !== auth()->id())
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                                @csrf
                                                @method('patch')
                                                <select name="role" onchange="this.form.submit()" class="text-xs rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss focus:ring-moss font-display">
                                                    <option value="student" @selected($user->role === 'student')>{{ __('Student') }}</option>
                                                    <option value="admin" @selected($user->role === 'admin')>{{ __('Admin') }}</option>
                                                </select>
                                            </form>
                                            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" onsubmit="return confirm('{{ $user->isDeactivated() ? __('Reactivate this account?') : __('Deactivate this account? They will be signed out immediately.') }}')">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" @class([
                                                    'px-2.5 py-1.5 rounded-lg text-xs font-display font-semibold border whitespace-nowrap',
                                                    'border-jd-success/30 text-jd-success hover:bg-jd-success/10' => $user->isDeactivated(),
                                                    'border-jd-danger/30 text-jd-danger hover:bg-jd-danger/10' => ! $user->isDeactivated(),
                                                ])>{{ $user->isDeactivated() ? __('Reactivate') : __('Deactivate') }}</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('You') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
