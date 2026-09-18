<x-app-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($resource->status !== 'approved')
            <div class="mb-4 rounded-lg bg-jd-warning/10 border border-jd-warning/20 text-jd-warning px-4 py-2 text-sm">
                {{ __('This resource is :status and only visible to you until an admin reviews it.', ['status' => $resource->status]) }}
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6 items-start">

            {{-- Left: document info --}}
            <aside class="w-full lg:w-72 shrink-0 order-1">
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm p-5">
                    <div class="text-xs font-mono font-semibold text-moss dark:text-sage uppercase tracking-wide">
                        {{ $resource->resourceType->name }}
                    </div>
                    <h1 class="mt-1 text-xl font-display font-bold text-pine dark:text-mint leading-snug">{{ $resource->title }}</h1>

                    <dl class="mt-4 space-y-2 text-sm font-mono">
                        @if ($resource->pagesLabel())
                            <div class="flex justify-between gap-3">
                                <dt class="text-jd-ink-muted dark:text-sage">{{ in_array($resource->format, ['xls', 'xlsx']) ? __('Sheets') : __('Pages') }}</dt>
                                <dd class="text-pine dark:text-mint text-right">{{ $resource->pagesLabel() }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-jd-ink-muted dark:text-sage">{{ __('Format') }}</dt>
                            <dd class="text-pine dark:text-mint uppercase">{{ $resource->format }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-jd-ink-muted dark:text-sage">{{ __('Size') }}</dt>
                            <dd class="text-pine dark:text-mint">{{ number_format($resource->file_size / 1024, 0) }} KB</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-jd-ink-muted dark:text-sage">{{ __('Downloads') }}</dt>
                            <dd class="text-pine dark:text-mint">{{ $resource->downloads_count }}</dd>
                        </div>
                        @if ($resource->course)
                            <div class="flex justify-between gap-3">
                                <dt class="text-jd-ink-muted dark:text-sage">{{ __('Course') }}</dt>
                                <dd class="text-pine dark:text-mint text-right">{{ $resource->course->name }}</dd>
                            </div>
                        @endif
                        @if ($resource->university)
                            <div class="flex justify-between gap-3">
                                <dt class="text-jd-ink-muted dark:text-sage">{{ __('University') }}</dt>
                                <dd class="text-pine dark:text-mint text-right">{{ $resource->university->name }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 pt-4 border-t border-pine/10 dark:border-mint/10 text-sm">
                        <span class="text-jd-ink-muted dark:text-sage">{{ __('Uploaded by') }}</span>
                        <span class="font-display font-medium text-pine dark:text-mint">{{ $resource->uploaderDisplayName() }}</span>
                        <div class="text-xs text-jd-ink-muted dark:text-sage font-mono mt-0.5">{{ $resource->created_at->format('M j, Y') }}</div>
                    </div>

                    @if ($resource->description)
                        <p class="mt-4 pt-4 border-t border-pine/10 dark:border-mint/10 text-sm text-jd-ink-muted dark:text-sage font-serif whitespace-pre-line">{{ $resource->description }}</p>
                    @endif

                    @if ($resource->tags->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($resource->tags as $tag)
                                <span class="text-xs bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage border border-pine/10 dark:border-mint/10 rounded-full px-3 py-1">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-5 pt-5 border-t border-pine/10 dark:border-mint/10 space-y-2">
                        @auth
                            @if ($canDownload)
                                <form method="POST" action="{{ route('resources.download', $resource) }}">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright transition">
                                        {{ __('Download') }}
                                    </button>
                                </form>
                            @else
                                <div class="text-xs text-jd-warning bg-jd-warning/10 border border-jd-warning/20 rounded-lg px-3 py-2">
                                    {{ __('Upload :n more approved document(s) to unlock downloads.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                                    <a href="{{ route('resources.create') }}" class="font-semibold underline block mt-1">{{ __('Upload now') }}</a>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright transition">
                                {{ __('Log in to download') }}
                            </a>
                        @endauth

                        @if ($canPreview && ! $canDownload)
                            <a href="{{ route('resources.preview', $resource) }}" target="_blank" rel="noopener"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-transparent border border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage rounded-lg text-sm font-display font-semibold hover:bg-jd-surface-2 transition">
                                {{ __('Admin Preview') }}
                            </a>
                        @endif

                        <button type="button" id="share-button"
                                data-url="{{ route('resources.show', $resource) }}"
                                data-title="{{ $resource->title }}"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-transparent border border-moss dark:border-sage text-moss dark:text-sage rounded-lg text-sm font-display font-semibold hover:bg-jd-surface-2 transition">
                            {{ __('Share') }}
                        </button>
                        <p id="share-status" class="text-xs text-center text-jd-ink-muted dark:text-sage h-4"></p>

                        @if (Auth::user()?->isAdmin())
                            <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}" onsubmit="return confirm('{{ __('Delete this resource permanently? This cannot be undone from the UI.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-transparent border border-jd-danger text-jd-danger rounded-lg text-sm font-display font-semibold hover:bg-jd-danger hover:text-white transition">
                                    {{ __('Delete Resource') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @if ($resource->status === 'approved')
                        @php
                            $tile = 'group w-full h-full flex flex-col items-center justify-center gap-1.5 rounded-lg bg-jd-surface-2 dark:bg-cypress border border-transparent px-2 py-3 text-xs font-display font-medium text-pine dark:text-mint hover:border-pine/20 dark:hover:border-mint/20 aria-pressed:bg-moss/15 aria-pressed:border-moss dark:aria-pressed:bg-sage/20 dark:aria-pressed:border-sage transition';
                        @endphp
                        <div class="mt-4 grid grid-cols-2 gap-2" data-vote-group>
                            @auth
                                {{-- Save --}}
                                <form method="POST" action="{{ route('resources.save', $resource) }}" data-save-form data-resource="{{ $resource->id }}">
                                    @csrf
                                    <button type="submit" aria-pressed="{{ $isSaved ? 'true' : 'false' }}" title="{{ $isSaved ? __('Remove from saved') : __('Save for later') }}" class="{{ $tile }}">
                                        <svg class="w-5 h-5 fill-none group-aria-pressed:fill-current" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h12a.75.75 0 0 1 .75.75v16.19a.375.375 0 0 1-.6.3L12 16.5l-6.15 4.49a.375.375 0 0 1-.6-.3V4.5A.75.75 0 0 1 6 3.75z"/></svg>
                                        <span data-save-label>{{ $isSaved ? __('Saved') : __('Save') }}</span>
                                    </button>
                                </form>

                                {{-- Like --}}
                                <form method="POST" action="{{ route('resources.vote', $resource) }}" data-vote-form="like">
                                    @csrf
                                    <input type="hidden" name="vote" value="like">
                                    <button type="submit" aria-pressed="{{ $userVote === 'like' ? 'true' : 'false' }}" title="{{ $votes['likes'] }} {{ __('like(s)') }}" class="{{ $tile }}">
                                        <svg class="w-5 h-5 fill-none group-aria-pressed:fill-current" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10v11M7 10H4.5A1.5 1.5 0 0 0 3 11.5v8A1.5 1.5 0 0 0 4.5 21H17.3a2 2 0 0 0 2-1.7l1.2-7A2 2 0 0 0 18.5 10H14V5.5A2.5 2.5 0 0 0 11.5 3L7 10z"/></svg>
                                        <span data-vote-label>{{ $votes['like_percent'] === null ? __('Like') : $votes['like_percent'].'%' }}</span>
                                    </button>
                                </form>

                                {{-- Dislike --}}
                                <form method="POST" action="{{ route('resources.vote', $resource) }}" data-vote-form="dislike">
                                    @csrf
                                    <input type="hidden" name="vote" value="dislike">
                                    <button type="submit" aria-pressed="{{ $userVote === 'dislike' ? 'true' : 'false' }}" title="{{ $votes['dislikes'] }} {{ __('dislike(s)') }}" class="{{ $tile }}">
                                        <svg class="w-5 h-5 fill-none group-aria-pressed:fill-current" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 14V3m0 11h2.5a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 19.5 3H6.7a2 2 0 0 0-2 1.7l-1.2 7A2 2 0 0 0 5.5 14H10v4.5a2.5 2.5 0 0 0 2.5 2.5L17 14z"/></svg>
                                        <span data-vote-label>{{ $votes['dislike_percent'] === null ? __('Dislike') : $votes['dislike_percent'].'%' }}</span>
                                    </button>
                                </form>

                                {{-- Report --}}
                                <div>
                                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'report-resource')" class="{{ $tile }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4m0 0h11l-1.5 4L15 12H4"/></svg>
                                        <span data-report-label>{{ $hasReported ? __('Reported') : __('Report') }}</span>
                                    </button>
                                </div>
                            @else
                                @foreach ([__('Save'), $votes['like_percent'] === null ? __('Like') : $votes['like_percent'].'%', $votes['dislike_percent'] === null ? __('Dislike') : $votes['dislike_percent'].'%', __('Report')] as $label)
                                    <a href="{{ route('login') }}" class="{{ $tile }}" title="{{ __('Log in to use this') }}">
                                        <span>{{ $label }}</span>
                                    </a>
                                @endforeach
                            @endauth
                        </div>
                    @endif
                </div>
            </aside>

            {{-- Center: page-by-page viewer --}}
            <main class="flex-1 min-w-0 order-3 lg:order-2 w-full">
                <x-document-reader :resource="$resource" />
            </main>

            {{-- Right: related documents --}}
            <aside class="w-full lg:w-72 shrink-0 order-2 lg:order-3 space-y-3">
                <h2 class="text-sm font-mono font-semibold text-jd-ink-muted dark:text-sage uppercase tracking-wide">{{ __('Related Documents') }}</h2>
                @forelse ($related as $item)
                    <a href="{{ route('resources.show', $item) }}" class="flex gap-3 bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm p-3 hover:shadow-md transition">
                        <div class="relative shrink-0 w-14 h-[4.5rem] bg-jd-surface-2 dark:bg-cypress rounded overflow-hidden flex items-center justify-center border border-pine/10 dark:border-mint/10">
                            <span class="absolute top-1 left-1 bg-pine dark:bg-abyss text-jd-bg text-[9px] font-mono font-bold px-1 py-0.5 rounded">
                                {{ strtoupper($item->format) }}
                            </span>
                            @if ($item->thumbnailUrl())
                                <img src="{{ $item->thumbnailUrl() }}" class="w-full h-full object-cover object-top" alt="">
                            @else
                                <svg class="w-6 h-6 text-sage/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-display font-semibold text-pine dark:text-mint line-clamp-2 leading-snug">{{ $item->title }}</p>
                            <p class="text-xs text-jd-ink-muted dark:text-sage font-mono mt-1">{{ $item->pagesLabel() ?? $item->resourceType->name }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('No related documents yet.') }}</p>
                @endforelse
            </aside>
        </div>
    </div>

    @auth
        @if ($resource->status === 'approved')
            <x-modal name="report-resource" :show="$errors->has('reason') || $errors->has('details')" maxWidth="lg" focusable>
                <form method="POST" action="{{ route('resources.report', $resource) }}" data-report-form class="p-6 space-y-4">
                    @csrf
                    <div>
                        <h2 class="text-lg font-display font-bold text-pine dark:text-mint">{{ __('Report this document') }}</h2>
                        <p class="mt-1 text-sm text-jd-ink-muted dark:text-sage">{{ __('Tell us what is wrong. An admin will review it.') }}</p>
                    </div>

                    <fieldset class="space-y-2">
                        <legend class="sr-only">{{ __('Reason') }}</legend>
                        @foreach (\App\Models\ResourceReport::REASONS as $key => $label)
                            <label class="flex items-center gap-3 rounded-lg border border-pine/10 dark:border-mint/10 px-3 py-2 text-sm text-pine dark:text-mint cursor-pointer hover:bg-jd-surface-2 dark:hover:bg-cypress">
                                <input type="radio" name="reason" value="{{ $key }}" required @checked(old('reason') === $key)
                                       class="text-moss dark:text-sage focus:ring-moss dark:focus:ring-sage">
                                {{ __($label) }}
                            </label>
                        @endforeach
                    </fieldset>

                    <div>
                        <label for="report-details" class="block text-sm font-display font-medium text-pine dark:text-mint">{{ __('Details') }} <span class="text-jd-ink-muted dark:text-sage font-normal">{{ __('(required for "Other")') }}</span></label>
                        <textarea id="report-details" name="details" rows="3" maxlength="1000"
                                  class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint text-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage">{{ old('details') }}</textarea>
                    </div>

                    <p data-report-error class="text-sm text-jd-danger min-h-[1.25rem]">{{ $errors->first('reason') ?: $errors->first('details') }}</p>

                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 rounded-lg border border-pine/20 dark:border-mint/20 text-sm font-display font-semibold text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress">{{ __('Cancel') }}</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-jd-danger text-white text-sm font-display font-semibold hover:opacity-90">{{ __('Submit report') }}</button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endauth

    <script>
        document.getElementById('share-button')?.addEventListener('click', async function () {
            const url = this.dataset.url;
            const title = this.dataset.title;
            const statusEl = document.getElementById('share-status');

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch (err) {
                    // user cancelled — no-op
                }
                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                statusEl.textContent = '{{ __('Link copied!') }}';
                setTimeout(() => statusEl.textContent = '', 2000);
            } catch (err) {
                statusEl.textContent = url;
            }
        });
    </script>
</x-app-layout>
