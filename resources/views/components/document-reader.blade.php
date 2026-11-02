@props(['resource'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm overflow-hidden']) }}>
    @if ($resource->hasPdfPreview())
        {{-- Nothing below downloads until the reader opts in: no pdf.js, no PDF bytes. --}}
        <div id="reader-start">
            {{-- Low-res first-page preview: the thumbnail is already cached/loaded, so this costs no extra data. --}}
            <button type="button" id="read-preview" class="relative block w-full bg-jd-surface-3 dark:bg-abyss px-3 pt-4 sm:px-6 sm:pt-6 text-left cursor-pointer" aria-label="{{ __('Start reading') }}">
                <div class="relative mx-auto max-w-xl overflow-hidden bg-white dark:bg-pine shadow-md" style="max-height: 62vh;">
                    @if ($resource->thumbnailUrl())
                        <img src="{{ $resource->thumbnailUrl() }}" class="block w-full h-auto" alt="{{ __('Preview of :title', ['title' => $resource->title]) }}" decoding="async">
                    @else
                        <div class="h-64 flex items-center justify-center">
                            <svg class="w-12 h-12 text-sage/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                    @endif
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-jd-surface-3 dark:from-abyss to-transparent"></div>
                </div>
            </button>
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 border-t border-pine/10 dark:border-mint/10">
                <div class="min-w-0">
                    <p class="font-display font-semibold text-pine dark:text-mint">{{ __('Preview of page 1') }}</p>
                    <p class="mt-0.5 text-xs font-mono text-jd-ink-muted dark:text-sage">
                        @if ($resource->pagesLabel()){{ $resource->pagesLabel() }} &middot; @endif{{ $resource->file_size >= 1048576 ? number_format($resource->file_size / 1048576, 1).' MB' : number_format($resource->file_size / 1024, 0).' KB' }}
                    </p>
                    <p class="mt-1 text-xs text-jd-ink-muted dark:text-sage font-serif italic">{{ __('The full document only downloads when you tap Start reading, so it will not use your data unless you want to read.') }}</p>
                </div>
                <button type="button" id="read-start" class="shrink-0 inline-flex items-center justify-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright transition">
                    {{ __('Start reading') }}
                </button>
            </div>
        </div>

        <div id="reader-shell" class="flex flex-col bg-jd-surface-3 dark:bg-abyss" hidden>
            <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-pine/10 dark:border-mint/10 bg-jd-surface-2 dark:bg-cypress text-sm font-display">
                <button type="button" id="read-close" class="px-2 py-1 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint font-semibold" aria-label="{{ __('Close reader') }}">&times; {{ __('Close') }}</button>
                <div class="flex items-center gap-1">
                    <button type="button" id="prev-page" class="px-2 py-1 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint disabled:opacity-30" disabled>&larr;</button>
                    <span class="text-jd-ink-muted dark:text-sage flex items-center gap-1">
                        <input type="number" id="page-input" value="1" min="1" class="w-12 text-center rounded border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm py-1 font-mono">
                        <span class="font-mono">/ <span id="page-count">&hellip;</span></span>
                    </span>
                    <button type="button" id="next-page" class="px-2 py-1 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint disabled:opacity-30" disabled>&rarr;</button>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" id="zoom-out" class="w-8 h-8 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint">&minus;</button>
                    <button type="button" id="zoom-in" class="w-8 h-8 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint">+</button>
                </div>
            </div>
            <div id="viewer-container" class="flex-1 overflow-y-auto overscroll-contain">
                <p id="viewer-status" class="p-10 text-center text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Loading document…') }}</p>
                <div id="pages-container" class="flex flex-col items-center gap-4 p-1 sm:p-4" hidden></div>
            </div>
        </div>

        <script>
            (function () {
                const PDFJS_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                const PDFJS_WORKER = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                const startPanel = document.getElementById('reader-start');
                const shell = document.getElementById('reader-shell');
                const startBtn = document.getElementById('read-start');
                const closeBtn = document.getElementById('read-close');
                const viewerContainer = document.getElementById('viewer-container');
                const pagesContainer = document.getElementById('pages-container');
                const pageInput = document.getElementById('page-input');
                const pageCount = document.getElementById('page-count');
                const prevBtn = document.getElementById('prev-page');
                const nextBtn = document.getElementById('next-page');
                const zoomInBtn = document.getElementById('zoom-in');
                const zoomOutBtn = document.getElementById('zoom-out');
                const status = document.getElementById('viewer-status');

                const PAGE_GAP = 16; // matches gap-4

                let pdfJsPromise = null;
                let session = 0; // bumped on every open/close so stale async work can bail out
                let isOpen = false;
                let pdfDoc = null;
                let numPages = 0;
                let zoom = 1;
                let baseScale = 1;
                let pageWidth = 0;
                let pageHeight = 0;
                let currentPage = 1;
                let wrappers = [];
                let renderTokens = [];
                let observer = null;
                let scrollRaf = null;
                let programmaticScroll = false;
                let firstPageOffset = 0;

                function loadPdfJs() {
                    if (window.pdfjsLib) return Promise.resolve();
                    if (!pdfJsPromise) {
                        pdfJsPromise = new Promise((resolve, reject) => {
                            const el = document.createElement('script');
                            el.src = PDFJS_SRC;
                            el.onload = () => {
                                pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
                                resolve();
                            };
                            el.onerror = () => { pdfJsPromise = null; reject(new Error('pdf.js failed to load')); };
                            document.head.appendChild(el);
                        });
                    }
                    return pdfJsPromise;
                }

                function stride() {
                    return pageHeight + PAGE_GAP;
                }

                function setCurrentPage(num) {
                    num = Math.min(Math.max(num, 1), numPages);
                    currentPage = num;
                    pageInput.value = num;
                    prevBtn.disabled = num <= 1;
                    nextBtn.disabled = num >= numPages;
                }

                function goToPage(num) {
                    num = Math.min(Math.max(num, 1), numPages);
                    const wrapper = wrappers[num - 1];
                    if (!wrapper) return;
                    programmaticScroll = true;
                    viewerContainer.scrollTo({ top: wrapper.offsetTop - 16, behavior: 'smooth' });
                    setCurrentPage(num);
                    window.clearTimeout(goToPage._t);
                    goToPage._t = window.setTimeout(() => { programmaticScroll = false; }, 600);
                }

                async function renderInto(num, wrapper) {
                    const mySession = session;
                    const token = ++renderTokens[num - 1];
                    const page = await pdfDoc.getPage(num);
                    if (mySession !== session || token !== renderTokens[num - 1]) return; // superseded (zoom changed / reader closed)

                    const viewport = page.getViewport({ scale: baseScale * zoom });
                    const canvas = wrapper.querySelector('canvas');

                    // Draw at the device's real pixel density (phones are 2-3x)
                    // and let CSS shrink the canvas back to layout size;
                    // otherwise the browser upscales a low-res bitmap and text
                    // turns blurry. Capped so huge zoomed pages stay under
                    // mobile browsers' canvas size limits.
                    let outputScale = Math.min(Math.max(window.devicePixelRatio || 1, 2), 3);
                    const maxPixels = 16 * 1024 * 1024;
                    outputScale = Math.min(outputScale, Math.sqrt(maxPixels / (viewport.width * viewport.height)));

                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = Math.floor(viewport.width) + 'px';
                    canvas.style.height = Math.floor(viewport.height) + 'px';

                    const ctx = canvas.getContext('2d');
                    await page.render({
                        canvasContext: ctx,
                        viewport,
                        transform: [outputScale, 0, 0, outputScale, 0, 0],
                    }).promise;
                    if (mySession !== session || token !== renderTokens[num - 1]) return;
                    wrapper.dataset.rendered = '1';
                }

                function unrender(wrapper) {
                    const canvas = wrapper.querySelector('canvas');
                    canvas.width = 0;
                    canvas.height = 0;
                    delete wrapper.dataset.rendered;
                }

                function setupObserver() {
                    if (observer) observer.disconnect();
                    observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            const wrapper = entry.target;
                            const num = parseInt(wrapper.dataset.page, 10);
                            if (entry.isIntersecting) {
                                if (!wrapper.dataset.rendered) renderInto(num, wrapper);
                            } else if (wrapper.dataset.rendered) {
                                unrender(wrapper);
                            }
                        });
                    }, { root: viewerContainer, rootMargin: '1000px 0px', threshold: 0.01 });

                    wrappers.forEach((w) => observer.observe(w));
                }

                function buildWrappers() {
                    pagesContainer.innerHTML = '';
                    wrappers = [];
                    renderTokens = new Array(numPages).fill(0);

                    for (let i = 1; i <= numPages; i++) {
                        const wrapper = document.createElement('div');
                        wrapper.dataset.page = i;
                        wrapper.className = 'shadow-md bg-white dark:bg-pine shrink-0 overflow-hidden';
                        wrapper.style.width = pageWidth + 'px';
                        wrapper.style.height = pageHeight + 'px';

                        const canvas = document.createElement('canvas');
                        canvas.style.display = 'block';
                        wrapper.appendChild(canvas);
                        pagesContainer.appendChild(wrapper);
                        wrappers.push(wrapper);
                    }

                    setupObserver();
                    firstPageOffset = wrappers.length ? wrappers[0].offsetTop : 0;
                }

                function resizeWrappers() {
                    wrappers.forEach((w) => {
                        w.style.width = pageWidth + 'px';
                        w.style.height = pageHeight + 'px';
                        if (w.dataset.rendered) unrender(w);
                    });
                    firstPageOffset = wrappers.length ? wrappers[0].offsetTop : 0;
                }

                // Fit pages to the reader's current width (fullscreen, so this
                // is the whole screen), then apply the user's zoom on top.
                async function fit() {
                    const firstPage = await pdfDoc.getPage(1);
                    const unscaled = firstPage.getViewport({ scale: 1 });
                    const containerWidth = viewerContainer.clientWidth - (window.innerWidth < 640 ? 8 : 32);
                    baseScale = Math.min(containerWidth / unscaled.width, 1.6);
                    const viewport = firstPage.getViewport({ scale: baseScale * zoom });
                    pageWidth = viewport.width;
                    pageHeight = viewport.height;
                }

                function onScroll() {
                    if (scrollRaf) return;
                    scrollRaf = requestAnimationFrame(() => {
                        scrollRaf = null;
                        if (programmaticScroll || !numPages) return;
                        const offset = viewerContainer.scrollTop - firstPageOffset;
                        const page = Math.floor(offset / stride() + 0.5) + 1;
                        setCurrentPage(page);
                    });
                }

                async function openReader() {
                    if (isOpen) return;
                    isOpen = true;
                    const mySession = ++session;

                    startPanel.hidden = true;
                    shell.hidden = false;
                    // Read mode: the reader takes over the whole screen.
                    shell.classList.add('fixed', 'inset-0', 'z-50');
                    document.documentElement.style.overflow = 'hidden';
                    status.hidden = false;
                    status.textContent = '{{ __('Loading document…') }}';
                    pagesContainer.hidden = true;

                    try {
                        await loadPdfJs();
                        if (mySession !== session) return;

                        // Streaming stays on: a single streamed download beats
                        // dozens of sequential range requests that each pay
                        // fixed server latency. Big range chunks remain the
                        // fallback for jumping to a specific page.
                        pdfDoc = await pdfjsLib.getDocument({
                            url: '{{ route('resources.read', $resource) }}',
                            rangeChunkSize: 2 * 1024 * 1024,
                        }).promise;
                        if (mySession !== session) { pdfDoc.destroy(); pdfDoc = null; return; }

                        numPages = pdfDoc.numPages;
                        pageCount.textContent = numPages;
                        pageInput.max = numPages;

                        await fit();
                        if (mySession !== session) return;

                        status.hidden = true;
                        pagesContainer.hidden = false;
                        buildWrappers();
                        setCurrentPage(1);
                    } catch (err) {
                        if (mySession !== session) return;
                        status.textContent = '{{ __('Could not load this document for online viewing.') }}';
                        console.error(err);
                    }
                }

                // Closing frees the PDF, every rendered canvas and the observer,
                // so a reader who stops reading stops costing memory.
                function closeReader() {
                    if (!isOpen) return;
                    isOpen = false;
                    session++;

                    if (observer) { observer.disconnect(); observer = null; }
                    wrappers.forEach((w) => { if (w.dataset.rendered) unrender(w); });
                    wrappers = [];
                    pagesContainer.innerHTML = '';
                    pagesContainer.hidden = true;
                    if (pdfDoc) { pdfDoc.destroy(); pdfDoc = null; }
                    numPages = 0;
                    zoom = 1;

                    shell.classList.remove('fixed', 'inset-0', 'z-50');
                    shell.hidden = true;
                    startPanel.hidden = false;
                    document.documentElement.style.overflow = '';
                }

                startBtn.addEventListener('click', openReader);
                document.getElementById('read-preview').addEventListener('click', openReader);
                closeBtn.addEventListener('click', closeReader);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeReader(); });
                viewerContainer.addEventListener('scroll', onScroll);

                // Rotating a phone or resizing the window re-fits the pages.
                let resizeTimer = null;
                window.addEventListener('resize', () => {
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(async () => {
                        if (!isOpen || !pdfDoc || !numPages) return;
                        const mySession = session;
                        await fit();
                        if (mySession !== session) return;
                        resizeWrappers();
                        setupObserver();
                        goToPage(currentPage);
                    }, 200);
                });

                prevBtn.addEventListener('click', () => goToPage(currentPage - 1));
                nextBtn.addEventListener('click', () => goToPage(currentPage + 1));
                pageInput.addEventListener('change', () => {
                    if (!numPages) return;
                    const n = Math.min(Math.max(parseInt(pageInput.value, 10) || 1, 1), numPages);
                    goToPage(n);
                });

                async function applyZoom(next) {
                    if (!numPages) return;
                    const mySession = session;
                    zoom = next;
                    await fit();
                    if (mySession !== session) return;
                    resizeWrappers();
                    goToPage(currentPage);
                    setupObserver();
                }

                zoomInBtn.addEventListener('click', () => applyZoom(Math.min(zoom + 0.25, 4)));
                zoomOutBtn.addEventListener('click', () => applyZoom(Math.max(zoom - 0.25, 0.4)));
            })();
        </script>
    @elseif (in_array($resource->conversion_status, ['pending', 'processing']))
        <div class="p-16 text-center">
            <div class="mx-auto w-20 h-24 bg-jd-surface-2 dark:bg-cypress rounded border border-pine/10 dark:border-mint/10 flex items-center justify-center mb-4 overflow-hidden">
                @if ($resource->thumbnailUrl())
                    <img src="{{ $resource->thumbnailUrl() }}" class="w-full h-full object-cover object-top" alt="">
                @else
                    <svg class="w-10 h-10 text-sage/60 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                @endif
            </div>
            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __("We're preparing an online preview for this document — check back shortly.") }}</p>
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
            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Online preview isn\'t available for this document.') }}</p>
            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('This is a :format file — download it to view the full content.', ['format' => strtoupper($resource->format)]) }}</p>
        </div>
    @endif
</div>
