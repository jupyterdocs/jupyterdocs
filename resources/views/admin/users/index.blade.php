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

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <x-admin.stat-card label="Total Users" :value="$users->total()" />
                <x-admin.stat-card label="Online Now" :value="$onlineUserIds->count()" accent="jd-success" />
                <x-admin.stat-card label="Admins" :value="$adminCount" />
            </div>

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-pine/10 dark:border-mint/10 text-left text-xs font-display font-semibold text-jd-ink-muted dark:text-sage uppercase tracking-wide">
                            <th class="p-4">{{ __('Name') }}</th>
                            <th class="p-4">{{ __('Role') }}</th>
                            <th class="p-4">{{ __('Status') }}</th>
                            <th class="p-4">{{ __('Uploads') }}</th>
                            <th class="p-4">{{ __('Approved') }}</th>
                            <th class="p-4">{{ __('Downloads') }}</th>
                            <th class="p-4">{{ __('Joined') }}</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-pine/10 dark:divide-mint/10">
                        @foreach ($users as $user)
                            @php
                                $isOnline = $onlineUserIds->contains($user->id);
                                $lastSeenTs = $lastSeen[$user->id] ?? null;
                            @endphp
                            <tr>
                                <td class="p-4">
                                    <div class="font-display font-medium text-pine dark:text-mint">{{ $user->name }}</div>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->email }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td class="p-4">
                                    @if ($isOnline)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-success/10 text-jd-success">
                                            <span class="w-1.5 h-1.5 rounded-full bg-jd-success"></span>
                                            {{ __('Online') }}
                                        </span>
                                    @elseif ($lastSeenTs)
                                        <span class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('Seen') }} {{ \Illuminate\Support\Carbon::createFromTimestamp($lastSeenTs)->diffForHumans() }}</span>
                                    @else
                                        <span class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('Never logged in') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->resources_count }}</td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->approved_uploads_count }}</td>
                                <td class="p-4 font-mono text-pine dark:text-mint">{{ $user->downloads_count }}</td>
                                <td class="p-4 text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->created_at->format('M j, Y') }}</td>
                                <td class="p-4">
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('patch')
                                            <select name="role" onchange="this.form.submit()" class="text-xs rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss focus:ring-moss font-display">
                                                <option value="student" @selected($user->role === 'student')>{{ __('Student') }}</option>
                                                <option value="lecturer" @selected($user->role === 'lecturer')>{{ __('Lecturer') }}</option>
                                                <option value="admin" @selected($user->role === 'admin')>{{ __('Admin') }}</option>
                                            </select>
                                        </form>
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
