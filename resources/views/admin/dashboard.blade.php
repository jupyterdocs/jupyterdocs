<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Admin Overview') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            @if ($stats['needs_conversion'] > 0 || ! empty($localBatch))
                <div id="local-conversion-panel" class="bg-white dark:bg-pine border border-jd-warning/30 shadow-sm rounded-xl p-4 space-y-3">

                    {{-- "Would you like to?" — only when nothing is running yet. --}}
                    <div id="local-conversion-ask" @if (! empty($localBatch)) hidden @endif class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <p class="font-display font-semibold text-pine dark:text-mint">
                                {{ trans_choice(':count document needs PDF conversion.|:count documents need PDF conversion.', $stats['needs_conversion']) }}
                            </p>
                            <p class="text-xs text-jd-ink-muted dark:text-sage mt-0.5">
                                {{ __('Convert them now using this device\'s CPU instead of waiting on the remote queue?') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" id="local-conversion-yes" class="font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-warning text-white hover:opacity-90">
                                {{ __('Use this device') }}
                            </button>
                            <button type="button" id="local-conversion-no" class="font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">
                                {{ __('Not now') }}
                            </button>
                        </div>
                    </div>

                    {{-- A batch is running (or just finished) — rendered from the database, so
                         switching tabs or reloading never loses it. --}}
                    <div id="local-conversion-progress" @if (empty($localBatch)) hidden @endif class="space-y-3">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <p id="local-conversion-worker-status" class="text-sm font-display font-semibold {{ $localWorkerConnected ? 'text-jd-success' : 'text-jd-warning' }}">
                                @if ($localWorkerConnected)
                                    {{ __('● Connected — converting on this device') }}
                                @else
                                    {{ __('○ Waiting for a worker to run on this device') }}
                                @endif
                            </p>
                            <button type="button" id="local-conversion-setup" class="font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">
                                {{ __('Run this automatically from now on') }}
                            </button>
                        </div>

                        <div id="local-conversion-command-box" @if ($localWorkerConnected) hidden @endif class="flex items-center gap-2">
                            <code class="flex-1 text-xs font-mono px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-pine dark:text-mint overflow-x-auto">php artisan conversion:work-local</code>
                            <button type="button" id="local-conversion-copy" class="shrink-0 font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">
                                {{ __('Copy') }}
                            </button>
                        </div>

                        <div id="local-conversion-setup-box" hidden class="text-xs text-jd-ink-muted dark:text-sage space-y-1.5">
                            <p>{{ __('Run this once on this device and the worker starts automatically every time you log in — no more commands after that:') }}</p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 text-xs font-mono px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-pine dark:text-mint overflow-x-auto">php artisan conversion:install-local-worker</code>
                                <button type="button" id="local-conversion-copy-install" class="shrink-0 font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">
                                    {{ __('Copy') }}
                                </button>
                            </div>
                            <p>{{ __('Windows only for now. To undo it later: php artisan conversion:uninstall-local-worker') }}</p>
                        </div>

                        {{-- Per-document progress. --}}
                        <ul id="local-conversion-documents" class="divide-y divide-pine/10 dark:divide-mint/10 rounded-lg border border-pine/10 dark:border-mint/10 overflow-hidden">
                            @foreach ($localBatch as $doc)
                                <x-admin.local-conversion-row :doc="$doc" />
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Key stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-admin.stat-card label="Total Users" :value="$stats['total_users']" />
                <x-admin.stat-card label="Online Now" :value="$stats['online_now']" accent="jd-success" />
                <x-admin.stat-card label="Avg Daily Active (30d)" :value="$stats['avg_daily_active_users']" />
                <x-admin.stat-card label="New Users (7d)" :value="$stats['new_users_7d']" />
                <x-admin.stat-card label="Total Resources" :value="$stats['total_resources']" />
                <x-admin.stat-card label="Pending Review" :value="$stats['pending_review']" accent="jd-warning" :href="route('admin.moderation.index')" />
                <x-admin.stat-card label="Approved" :value="$stats['approved']" accent="jd-success" />
                <x-admin.stat-card label="Rejected" :value="$stats['rejected']" accent="jd-danger" />
                <x-admin.stat-card label="Needs PDF Conversion" :value="$stats['needs_conversion']" accent="jd-warning" :href="route('admin.content.index')" />
                <x-admin.stat-card label="Converting Now" :value="$stats['converting']" />
                <x-admin.stat-card label="Total Downloads" :value="$stats['total_downloads']" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Recent users --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="flex items-center justify-between p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Newest Users') }}</h3>
                        <a href="{{ route('admin.users.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('View all') }} &rarr;</a>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($recentUsers as $user)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-display font-medium text-pine dark:text-mint">{{ $user->name }}</div>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->email }} &middot; {{ $user->created_at->diffForHumans() }}</div>
                                </div>
                                <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage">{{ ucfirst($user->role) }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No users yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Recent uploads --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="flex items-center justify-between p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Recent Uploads') }}</h3>
                        <a href="{{ route('admin.content.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('View all') }} &rarr;</a>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($recentUploads as $resource)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $resource->resourceType->name }} &middot; {{ $resource->uploaderDisplayName() }} &middot; {{ $resource->created_at->diffForHumans() }}</div>
                                </div>
                                <span class="text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage">{{ $resource->format }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No uploads yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($failedConversions->isNotEmpty())
                <div class="bg-white dark:bg-pine border border-jd-danger/20 shadow-sm rounded-xl">
                    <div class="p-4 border-b border-jd-danger/20">
                        <h3 class="font-display font-semibold text-jd-danger">{{ __('Failed PDF Conversions') }}</h3>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @foreach ($failedConversions as $resource)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('by') }} {{ $resource->uploaderDisplayName() }} &middot; {{ strtoupper($resource->format) }}</div>
                                    @if ($resource->conversion_error)
                                        <div class="text-xs text-jd-danger mt-1">{{ Str::limit($resource->conversion_error, 140) }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('admin.content.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint shrink-0">{{ __('Review') }} &rarr;</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($stats['needs_conversion'] > 0 || ! empty($localBatch))
        <script>
            (function () {
                var panel = document.getElementById('local-conversion-panel');
                if (! panel) return;

                var ask = document.getElementById('local-conversion-ask');
                var progress = document.getElementById('local-conversion-progress');
                var workerStatus = document.getElementById('local-conversion-worker-status');
                var commandBox = document.getElementById('local-conversion-command-box');
                var setupBox = document.getElementById('local-conversion-setup-box');
                var documentList = document.getElementById('local-conversion-documents');
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                var pollTimer = null;

                var STATUS_LABEL = { pending: 'Waiting', processing: 'Converting…', done: 'Done', failed: 'Failed' };
                var STATUS_CLASS = {
                    pending: 'bg-jd-warning/10 text-jd-warning',
                    processing: 'bg-jd-warning/10 text-jd-warning',
                    done: 'bg-jd-success/10 text-jd-success',
                    failed: 'bg-jd-danger/10 text-jd-danger',
                };

                // The ask-to-start prompt only ever shows when nothing is running yet
                // — once a batch exists it's real server state, not something to nag
                // about, so it's never hidden by a stale session dismissal.
                if (progress.hidden && sessionStorage.getItem('jd-local-conversion-dismissed')) {
                    ask.hidden = true;
                }

                document.getElementById('local-conversion-no').addEventListener('click', function () {
                    sessionStorage.setItem('jd-local-conversion-dismissed', '1');
                    ask.hidden = true;
                });

                document.getElementById('local-conversion-yes').addEventListener('click', function (e) {
                    e.target.disabled = true;

                    fetch('{{ route('admin.conversion.start-local') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            ask.hidden = true;
                            progress.hidden = false;
                            renderDocuments(data.batch);
                            startPolling();
                        });
                });

                document.getElementById('local-conversion-setup').addEventListener('click', function () {
                    setupBox.hidden = ! setupBox.hidden;
                });

                document.getElementById('local-conversion-copy').addEventListener('click', function () {
                    copy('php artisan conversion:work-local');
                });

                document.getElementById('local-conversion-copy-install').addEventListener('click', function () {
                    copy('php artisan conversion:install-local-worker');
                });

                function copy(text) {
                    navigator.clipboard && navigator.clipboard.writeText(text);
                }

                function renderDocuments(docs) {
                    if (! docs) return;

                    docs.forEach(function (doc) {
                        var row = documentList.querySelector('[data-doc-id="' + doc.id + '"]');

                        if (! row) {
                            row = document.createElement('li');
                            row.dataset.docId = doc.id;
                            row.className = 'p-3 flex items-center justify-between gap-3 text-sm';
                            row.innerHTML =
                                '<div class="min-w-0">' +
                                    '<div class="font-display font-medium text-pine dark:text-mint truncate"></div>' +
                                    '<div class="text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage"></div>' +
                                '</div>' +
                                '<span data-doc-status class="shrink-0 inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full"></span>';
                            row.querySelector('.truncate').textContent = doc.title;
                            row.querySelector('.font-mono').textContent = doc.format;
                            documentList.appendChild(row);
                        }

                        var badge = row.querySelector('[data-doc-status]');
                        badge.textContent = STATUS_LABEL[doc.status] || doc.status;
                        badge.className = 'shrink-0 inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full ' +
                            (STATUS_CLASS[doc.status] || STATUS_CLASS.pending);
                    });
                }

                function startPolling() {
                    if (pollTimer) return;
                    poll();
                    pollTimer = setInterval(poll, 5000);
                }

                function poll() {
                    fetch('{{ route('admin.conversion.status') }}', { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            renderDocuments(data.batch);
                            commandBox.hidden = data.worker_connected;

                            workerStatus.textContent = data.worker_connected
                                ? '● Connected — converting on this device'
                                : '○ Waiting for a worker to run on this device';
                            workerStatus.className = 'text-sm font-display font-semibold ' +
                                (data.worker_connected ? 'text-jd-success' : 'text-jd-warning');
                        });
                }

                if (! progress.hidden) {
                    startPolling();
                }
            })();
        </script>
    @endif
</x-app-layout>
