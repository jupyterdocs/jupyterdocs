@props(['resource'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl shadow-sm overflow-hidden']) }}>
    @if ($resource->format === 'pdf')
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
        <div id="viewer-container" class="overflow-auto bg-jd-surface-3 dark:bg-abyss" style="max-height: 78vh;">
            <p id="viewer-status" class="p-10 text-center text-sm text-jd-ink-muted dark:text-sage font-serif italic">{{ __('Loading document…') }}</p>
            <div class="flex justify-center p-4">
                <canvas id="pdf-canvas" class="shadow-md bg-white dark:bg-pine" hidden></canvas>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            (function () {
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
            })();
        </script>
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
