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
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-moss dark:bg-sage text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-mint transition">
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
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-moss dark:bg-sage text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-mint transition">
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
                    </div>
                </div>
            </aside>

            {{-- Center: page-by-page viewer --}}
            <main class="flex-1 min-w-0 order-3 lg:order-2 w-full">
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm overflow-hidden">
                    @if ($resource->format === 'pdf')
                        <div class="flex items-center justify-between gap-2 px-4 py-2 border-b border-pine/10 dark:border-mint/10 bg-jd-surface-2 dark:bg-cypress text-sm font-display">
                            <div class="flex items-center gap-2">
                                <button type="button" id="prev-page" class="px-2 py-1 rounded hover:bg-white text-pine dark:text-mint disabled:opacity-30" disabled>&larr;</button>
                                <span class="text-jd-ink-muted dark:text-sage flex items-center gap-1">
                                    <input type="number" id="page-input" value="1" min="1" class="w-14 text-center rounded border-pine/15 dark:border-mint/15 focus:border-moss focus:ring-moss text-sm py-1 font-mono">
                                    <span class="font-mono">/ <span id="page-count">&hellip;</span></span>
                                </span>
                                <button type="button" id="next-page" class="px-2 py-1 rounded hover:bg-white text-pine dark:text-mint disabled:opacity-30" disabled>&rarr;</button>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" id="zoom-out" class="w-7 h-7 rounded hover:bg-white text-pine dark:text-mint">&minus;</button>
                                <button type="button" id="zoom-in" class="w-7 h-7 rounded hover:bg-white text-pine dark:text-mint">+</button>
                            </div>
                        </div>
                        <div id="viewer-container" class="overflow-auto bg-jd-surface-3 dark:bg-abyss" style="max-height: 78vh;">
                            <p id="viewer-status" class="p-10 text-center text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Loading document…') }}</p>
                            <div class="flex justify-center p-4">
                                <canvas id="pdf-canvas" class="shadow-md bg-white dark:bg-pine" hidden></canvas>
                            </div>
                        </div>
                    @else
                        <div class="p-16 text-center">
                            <div class="mx-auto w-20 h-24 bg-jd-surface-2 dark:bg-cypress rounded border border-pine/10 dark:border-mint/10 flex items-center justify-center mb-4 overflow-hidden">
                                @if ($resource->thumbnailUrl())
                                    <img src="{{ $resource->thumbnailUrl() }}" class="w-full h-full object-cover object-top" alt="">
                                @else
                                    <svg class="w-10 h-10 text-sage/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                @endif
                            </div>
                            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Online preview is only available for PDF files right now.') }}</p>
                            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('This is a :format file — download it to view the full content.', ['format' => strtoupper($resource->format)]) }}</p>
                        </div>
                    @endif
                </div>
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

    @if ($resource->format === 'pdf')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            const canvas = document.getElementById('pdf-canvas');
            const ctx = canvas.getContext('2d');
            const viewerContainer = document.getElementById('viewer-container');
            const pageInput = document.getElementById('page-input');
            const pageCount = document.getElementById('page-count');
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const zoomInBtn = document.getElementById('zoom-in');
            const zoomOutBtn = document.getElementById('zoom-out');
            const status = document.getElementById('viewer-status');

            let pdfDoc = null;
            let currentPage = 1;
            let zoom = 1;
            let rendering = false;

            async function renderPage(num) {
                if (!pdfDoc || rendering) return;
                rendering = true;

                const page = await pdfDoc.getPage(num);
                const unscaled = page.getViewport({ scale: 1 });
                const containerWidth = viewerContainer.clientWidth - 32;
                const baseScale = Math.min(containerWidth / unscaled.width, 1.6);
                const viewport = page.getViewport({ scale: baseScale * zoom });

                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.hidden = false;

                await page.render({ canvasContext: ctx, viewport }).promise;

                rendering = false;
                currentPage = num;
                pageInput.value = num;
                prevBtn.disabled = num <= 1;
                nextBtn.disabled = num >= pdfDoc.numPages;
            }

            (async () => {
                try {
                    pdfDoc = await pdfjsLib.getDocument('{{ route('resources.read', $resource) }}').promise;
                    pageCount.textContent = pdfDoc.numPages;
                    pageInput.max = pdfDoc.numPages;
                    status.hidden = true;
                    await renderPage(1);
                } catch (err) {
                    status.textContent = '{{ __('Could not load this document for online viewing.') }}';
                    console.error(err);
                }
            })();

            prevBtn.addEventListener('click', () => currentPage > 1 && renderPage(currentPage - 1));
            nextBtn.addEventListener('click', () => pdfDoc && currentPage < pdfDoc.numPages && renderPage(currentPage + 1));
            pageInput.addEventListener('change', () => {
                if (!pdfDoc) return;
                const n = Math.min(Math.max(parseInt(pageInput.value, 10) || 1, 1), pdfDoc.numPages);
                renderPage(n);
            });
            zoomInBtn.addEventListener('click', () => { zoom = Math.min(zoom + 0.2, 3); renderPage(currentPage); });
            zoomOutBtn.addEventListener('click', () => { zoom = Math.max(zoom - 0.2, 0.4); renderPage(currentPage); });
        </script>
    @endif
</x-app-layout>
