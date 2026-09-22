<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Conversion') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            {{-- Worker status + one-time setup --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-sm font-display font-semibold {{ $localWorkerConnected ? 'text-jd-success' : 'text-jd-warning' }}">
                        @if ($localWorkerConnected)
                            {{ __('● This device is connected and converting') }}
                        @else
                            {{ __('○ No worker running on this device yet') }}
                        @endif
                    </p>
                    <button type="button" id="conversion-setup-toggle" class="font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">
                        {{ __('Set up / commands') }}
                    </button>
                </div>

                <div id="conversion-setup-box" hidden class="text-xs text-jd-ink-muted dark:text-sage space-y-3">
                    <div>
                        <p>{{ __('Run this in a terminal on this device to start converting:') }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="flex-1 text-xs font-mono px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-pine dark:text-mint overflow-x-auto">php artisan conversion:work-local</code>
                            <button type="button" data-copy="php artisan conversion:work-local" class="conversion-copy shrink-0 font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">{{ __('Copy') }}</button>
                        </div>
                    </div>
                    <div>
                        <p>{{ __('Or run this once and it starts automatically every time you log in — no more commands after that:') }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="flex-1 text-xs font-mono px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-pine dark:text-mint overflow-x-auto">php artisan conversion:install-local-worker</code>
                            <button type="button" data-copy="php artisan conversion:install-local-worker" class="conversion-copy shrink-0 font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage hover:opacity-90">{{ __('Copy') }}</button>
                        </div>
                        <p class="mt-1">{{ __('Windows only for now. To undo it later: php artisan conversion:uninstall-local-worker') }}</p>
                    </div>
                </div>
            </div>

            {{-- Currently converting --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                    <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Converting now') }}</h3>
                </div>
                <ul class="divide-y divide-pine/10 dark:divide-mint/10">
                    @forelse ($localBatch as $resource)
                        <li class="p-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-display font-medium text-pine dark:text-mint truncate">{{ $resource->title }}</div>
                                <div class="text-xs text-jd-ink-muted dark:text-sage">
                                    {{ __('This device') }}@if ($resource->queuedBy) &middot; {{ $resource->queuedBy->name }} @endif
                                    &middot;
                                    @if ($resource->conversion_status === 'pending')
                                        {{ __('waiting for a worker') }}
                                    @elseif ($resource->conversion_status === 'failed')
                                        {{ __('failed') }}
                                    @else
                                        {{ __('converting…') }}
                                    @endif
                                    &middot; {{ $resource->updated_at->diffForHumans() }}
                                </div>
                                @if ($resource->conversion_status === 'failed' && $resource->conversion_error)
                                    <div class="text-xs text-jd-danger mt-0.5">{{ Str::limit($resource->conversion_error, 100) }}</div>
                                @endif
                            </div>
                            <span class="shrink-0 text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage">{{ $resource->format }}</span>
                        </li>
                    @empty
                    @endforelse

                    @forelse ($remoteProcessing as $resource)
                        <li class="p-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-display font-medium text-pine dark:text-mint truncate">{{ $resource->title }}</div>
                                <div class="text-xs text-jd-ink-muted dark:text-sage">
                                    {{ __('Remote pipeline (CloudConvert/Gotenberg)') }} &middot; {{ $resource->updated_at->diffForHumans() }}
                                </div>
                            </div>
                            <span class="shrink-0 text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage">{{ $resource->format }}</span>
                        </li>
                    @empty
                    @endforelse

                    @if ($localBatch->isEmpty() && $remoteProcessing->isEmpty())
                        <li class="p-4 text-jd-ink-muted dark:text-sage">{{ __('Nothing is converting right now.') }}</li>
                    @endif
                </ul>
            </div>

            {{-- Waiting: pick which convert next --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                <div class="p-4 border-b border-pine/10 dark:border-mint/10 flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Waiting to convert') }}</h3>
                    @if ($backlog->isNotEmpty())
                        <div class="flex items-center gap-3">
                            <label class="text-xs text-jd-ink-muted dark:text-sage flex items-center gap-1.5">
                                <input type="checkbox" id="conversion-select-all" class="rounded border-pine/20">
                                {{ __('Select all') }}
                            </label>
                            <button type="button" id="conversion-start" disabled class="font-display font-semibold text-xs px-3 py-2 rounded-lg bg-jd-warning text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ __('Convert selected on this device') }} (<span id="conversion-selected-count">0</span>)
                            </button>
                        </div>
                    @endif
                </div>
                <p class="px-4 pt-3 text-xs text-jd-ink-muted dark:text-sage">
                    {{ __('Nothing converts on this device unless you select it here. Check the box on each document in the order you want them done — that order is what the worker follows.') }}
                </p>
                <ul id="conversion-backlog-list" class="divide-y divide-pine/10 dark:divide-mint/10 mt-1">
                    @forelse ($backlog as $resource)
                        <li class="p-4 flex items-center gap-3">
                            <input type="checkbox" class="conversion-checkbox rounded border-pine/20 shrink-0" value="{{ $resource->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-display font-medium text-pine dark:text-mint truncate">{{ $resource->title }}</div>
                                <div class="text-xs text-jd-ink-muted dark:text-sage">
                                    {{ $resource->uploaderDisplayName() }} &middot; {{ $resource->created_at->diffForHumans() }}
                                    @if ($resource->conversion_status === 'pending')
                                        &middot; {{ __('stuck in the remote queue') }}
                                    @endif
                                </div>
                                @if ($resource->conversion_status === 'failed' && $resource->conversion_error)
                                    <div class="text-xs text-jd-danger mt-0.5">{{ Str::limit($resource->conversion_error, 100) }}</div>
                                @endif
                            </div>
                            <span @class([
                                'shrink-0 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                                'bg-jd-danger/10 text-jd-danger' => $resource->conversion_status === 'failed',
                                'bg-jd-warning/10 text-jd-warning' => $resource->conversion_status === 'pending',
                                'bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage' => $resource->conversion_status === 'none',
                            ])>
                                @switch ($resource->conversion_status)
                                    @case ('failed') {{ __('Failed') }} @break
                                    @case ('pending') {{ __('Pending') }} @break
                                    @default {{ strtoupper($resource->format) }}
                                @endswitch
                            </span>
                        </li>
                    @empty
                        <li class="p-4 text-jd-ink-muted dark:text-sage">{{ __('Nothing is waiting.') }}</li>
                    @endforelse
                </ul>
            </div>

            {{-- Recently converted --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                    <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Recently converted') }}</h3>
                </div>
                <ul class="divide-y divide-pine/10 dark:divide-mint/10">
                    @forelse ($recentlyConverted as $resource)
                        <li class="p-4 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline truncate block">{{ $resource->title }}</a>
                                <div class="text-xs text-jd-ink-muted dark:text-sage">
                                    {{ $resource->converted_at?->diffForHumans() }}
                                    @if ($resource->conversion_driver === 'local' && $resource->queuedBy)
                                        &middot; {{ __('queued by') }} {{ $resource->queuedBy->name }}
                                    @endif
                                </div>
                            </div>
                            <x-admin.conversion-driver-badge :driver="$resource->conversion_driver" />
                        </li>
                    @empty
                        <li class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No documents converted yet.') }}</li>
                    @endforelse
                </ul>
                @if ($recentlyConverted->hasPages())
                    <div class="p-4 border-t border-pine/10 dark:border-mint/10">
                        {{ $recentlyConverted->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            var setupToggle = document.getElementById('conversion-setup-toggle');
            var setupBox = document.getElementById('conversion-setup-box');
            if (setupToggle) {
                setupToggle.addEventListener('click', function () {
                    setupBox.hidden = ! setupBox.hidden;
                });
            }

            document.querySelectorAll('.conversion-copy').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    navigator.clipboard && navigator.clipboard.writeText(btn.dataset.copy);
                });
            });

            var selectAll = document.getElementById('conversion-select-all');
            var startBtn = document.getElementById('conversion-start');
            var countLabel = document.getElementById('conversion-selected-count');
            var checkboxes = document.querySelectorAll('.conversion-checkbox');

            // The order documents are checked in — not DOM order — is what
            // gets sent, so an admin can pick exactly what converts next and
            // in what sequence, simply by the order they click.
            var selectionOrder = [];

            function refreshCount() {
                if (countLabel) countLabel.textContent = selectionOrder.length;
                if (startBtn) startBtn.disabled = selectionOrder.length === 0;
            }

            checkboxes.forEach(function (box) {
                box.addEventListener('change', function () {
                    var id = box.value;

                    if (box.checked) {
                        if (selectionOrder.indexOf(id) === -1) selectionOrder.push(id);
                    } else {
                        selectionOrder = selectionOrder.filter(function (v) { return v !== id; });
                    }

                    if (selectAll) selectAll.checked = selectionOrder.length === checkboxes.length;
                    refreshCount();
                });
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (box) {
                        box.checked = selectAll.checked;
                        box.dispatchEvent(new Event('change'));
                    });
                });
            }

            if (startBtn) {
                startBtn.addEventListener('click', function () {
                    startBtn.disabled = true;
                    var csrf = document.querySelector('meta[name="csrf-token"]').content;

                    fetch('{{ route('admin.conversion.start-local') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ resource_ids: selectionOrder }),
                    }).then(function () {
                        location.reload();
                    });
                });
            }

            @if ($localBatch->isNotEmpty() || $remoteProcessing->isNotEmpty())
                // Something is actively converting — keep the page fresh so
                // progress shows up without the admin having to reload by hand.
                setInterval(function () { location.reload(); }, 8000);
            @endif
        })();
    </script>
</x-app-layout>
