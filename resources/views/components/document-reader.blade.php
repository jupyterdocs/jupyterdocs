@props(['resource'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm overflow-hidden']) }}>
    @if ($resource->hasPdfPreview())
        <div class="flex items-center justify-between gap-2 px-4 py-2 border-b border-pine/10 dark:border-mint/10 bg-jd-surface-2 dark:bg-cypress text-sm font-display">
            <div class="flex items-center gap-2">
                <button type="button" id="prev-page" class="px-2 py-1 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint disabled:opacity-30" disabled>&larr;</button>
                <span class="text-jd-ink-muted dark:text-sage flex items-center gap-1">
                    <input type="number" id="page-input" value="1" min="1" class="w-14 text-center rounded border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm py-1 font-mono">
                    <span class="font-mono">/ <span id="page-count">&hellip;</span></span>
                </span>
                <button type="button" id="next-page" class="px-2 py-1 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint disabled:opacity-30" disabled>&rarr;</button>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" id="zoom-out" class="w-7 h-7 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint">&minus;</button>
                <button type="button" id="zoom-in" class="w-7 h-7 rounded hover:bg-white dark:hover:bg-abyss text-pine dark:text-mint">+</button>
            </div>
        </div>
        <div id="viewer-container" class="overflow-y-auto bg-jd-surface-3 dark:bg-abyss" style="max-height: 78vh;">
            <p id="viewer-status" class="p-10 text-center text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Loading document…') }}</p>
            <div id="pages-container" class="flex flex-col items-center gap-4 p-4" hidden></div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            (function () {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

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
                    const token = ++renderTokens[num - 1];
                    const page = await pdfDoc.getPage(num);
                    if (token !== renderTokens[num - 1]) return; // superseded (e.g. zoom changed)

                    const viewport = page.getViewport({ scale: baseScale * zoom });
                    const canvas = wrapper.querySelector('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    const ctx = canvas.getContext('2d');
                    await page.render({ canvasContext: ctx, viewport }).promise;
                    if (token !== renderTokens[num - 1]) return;
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
                        wrapper.className = 'shadow-md bg-white dark:bg-pine shrink-0';
                        wrapper.style.width = pageWidth + 'px';
                        wrapper.style.height = pageHeight + 'px';

                        const canvas = document.createElement('canvas');
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

                (async () => {
                    try {
                        pdfDoc = await pdfjsLib.getDocument('{{ route('resources.read', $resource) }}').promise;
                        numPages = pdfDoc.numPages;
                        pageCount.textContent = numPages;
                        pageInput.max = numPages;

                        const firstPage = await pdfDoc.getPage(1);
                        const unscaled = firstPage.getViewport({ scale: 1 });
                        const containerWidth = viewerContainer.clientWidth - 32;
                        baseScale = Math.min(containerWidth / unscaled.width, 1.6);
                        const viewport = firstPage.getViewport({ scale: baseScale * zoom });
                        pageWidth = viewport.width;
                        pageHeight = viewport.height;

                        status.hidden = true;
                        pagesContainer.hidden = false;
                        buildWrappers();
                        setCurrentPage(1);

                        viewerContainer.addEventListener('scroll', onScroll);
                    } catch (err) {
                        status.textContent = '{{ __('Could not load this document for online viewing.') }}';
                        console.error(err);
                    }
                })();

                prevBtn.addEventListener('click', () => goToPage(currentPage - 1));
                nextBtn.addEventListener('click', () => goToPage(currentPage + 1));
                pageInput.addEventListener('change', () => {
                    if (!numPages) return;
                    const n = Math.min(Math.max(parseInt(pageInput.value, 10) || 1, 1), numPages);
                    goToPage(n);
                });

                function applyZoom(next) {
                    if (!numPages) return;
                    zoom = next;
                    const scale = baseScale * zoom;

                    pdfDoc.getPage(1).then((firstPage) => {
                        const viewport = firstPage.getViewport({ scale });
                        pageWidth = viewport.width;
                        pageHeight = viewport.height;
                        resizeWrappers();
                        goToPage(currentPage);
                        setupObserver();
                    });
                }

                zoomInBtn.addEventListener('click', () => applyZoom(Math.min(zoom + 0.2, 3)));
                zoomOutBtn.addEventListener('click', () => applyZoom(Math.max(zoom - 0.2, 0.4)));
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
