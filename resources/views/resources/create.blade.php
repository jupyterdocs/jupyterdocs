<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Upload a Resource') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-6">

                @auth
                    @if (! Auth::user()->canDownload())
                        <div class="mb-4 rounded-lg bg-jd-surface-2 dark:bg-cypress border border-moss/20 dark:border-sage/20 text-pine dark:text-mint px-4 py-2 text-sm">
                            {{ __('Every 2 uploads earns you a download, and uploads count right away — no admin approval needed. You need :n more.', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                        </div>
                    @endif
                @else
                    <div class="mb-4 rounded-lg bg-jd-surface-2 dark:bg-cypress border border-moss/20 dark:border-sage/20 text-pine dark:text-mint px-4 py-2 text-sm">
                        {{ __('Uploading works without an account. But to unlock downloads later, ') }}
                        <a href="{{ route('register') }}" class="font-semibold underline">{{ __('create an account') }}</a>
                        {{ __(' first so your uploads count toward your total.') }}
                    </div>
                @endauth

                <form method="POST" action="{{ route('resources.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="file" :value="__('Document (PDF, Word, PowerPoint, Excel or text, max 20MB)')" />
                        <label for="file" id="dropzone" class="mt-1 flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed border-pine/20 dark:border-mint/20 bg-jd-surface-2 dark:bg-cypress px-4 py-8 text-center cursor-pointer hover:border-moss dark:hover:border-sage transition">
                            <svg class="w-8 h-8 text-jd-ink-muted dark:text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                            <span id="dropzone_label" class="text-sm font-display font-medium text-pine dark:text-mint">{{ __('Click to choose a file, or drag it here') }}</span>
                            <input id="file" name="file" type="file" class="sr-only" required>
                        </label>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        <p id="thumbnail_status" class="mt-1 text-xs text-jd-ink-muted dark:text-sage font-mono"></p>
                        <img id="thumbnail_preview" hidden class="mt-2 h-40 rounded-lg border border-pine/10 dark:border-mint/10 object-cover" alt="Preview of first page">
                        <input type="hidden" name="thumbnail_data" id="thumbnail_data">
                        <input type="hidden" name="pages" id="pages_field">
                    </div>

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required placeholder="e.g. Mathematics for IT Professionals — 2nd Edition" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    @guest
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="uploader_name" :value="__('Your name')" />
                                <x-text-input id="uploader_name" name="uploader_name" type="text" class="mt-1 block w-full" :value="old('uploader_name')" required />
                                <x-input-error :messages="$errors->get('uploader_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="uploader_email" :value="__('Email (optional)')" />
                                <x-text-input id="uploader_email" name="uploader_email" type="email" class="mt-1 block w-full" :value="old('uploader_email')" />
                                <x-input-error :messages="$errors->get('uploader_email')" class="mt-2" />
                            </div>
                        </div>
                    @endguest

                    <div>
                        <div class="flex items-baseline justify-between">
                            <x-input-label for="description" :value="__('Description')" />
                            <span id="description_count" class="text-xs font-mono text-jd-ink-muted dark:text-sage">0 / 150</span>
                        </div>
                        <textarea id="description" name="description" rows="5" minlength="2" maxlength="150" required class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display" placeholder="What is this document, which topics does it cover, and who is it useful for? Up to 150 characters — this is what helps other students find it.">{{ old('description') }}</textarea>
                        <p class="mt-1 text-xs text-jd-ink-muted dark:text-sage">{{ __('Up to 150 characters. A short description makes this document easy for others to find.') }}</p>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="resource_type_id" :value="__('Resource type')" />
                        <select id="resource_type_id" name="resource_type_id" class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint shadow-sm focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage font-display" required>
                            <option value="">{{ __('Select a type') }}</option>
                            @foreach ($resourceTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('resource_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('resource_type_id')" class="mt-2" />
                    </div>

                    <details class="rounded-lg border border-pine/10 dark:border-mint/10 open:pb-4" @if(old('course') || old('university')) open @endif>
                        <summary class="cursor-pointer select-none px-3 py-2.5 text-sm font-display font-medium text-jd-ink-muted dark:text-sage">
                            {{ __('Add course & university (optional)') }}
                        </summary>
                        <div class="grid grid-cols-2 gap-4 px-3 pt-1">
                            <div>
                                <x-input-label for="course" :value="__('Course')" />
                                <x-text-input id="course" name="course" type="text" class="mt-1 block w-full" :value="old('course')" placeholder="e.g. Database Management Systems II" />
                            </div>
                            <div>
                                <x-input-label for="university" :value="__('University')" />
                                <x-text-input id="university" name="university" type="text" class="mt-1 block w-full" :value="old('university')" placeholder="e.g. MUST" />
                            </div>
                        </div>
                    </details>

                    <div class="flex items-start gap-2">
                        <input id="confirm_ownership" name="confirm_ownership" type="checkbox" class="mt-1 rounded border-pine/20 dark:border-mint/20 dark:bg-cypress text-moss dark:text-sage focus:ring-moss dark:focus:ring-sage" required>
                        <label for="confirm_ownership" class="text-sm text-jd-ink-muted dark:text-sage">
                            {{ __('I confirm that I have the right to distribute this material.') }}
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('confirm_ownership')" class="mt-2" />

                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright active:scale-95 transition">
                        {{ __('Upload') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        const descriptionField = document.getElementById('description');
        const descriptionCount = document.getElementById('description_count');

        function updateDescriptionCount() {
            const len = descriptionField.value.length;
            descriptionCount.textContent = `${len} / 150`;
        }

        descriptionField?.addEventListener('input', updateDescriptionCount);
        updateDescriptionCount();

        const dropzone = document.getElementById('dropzone');
        const dropzoneLabel = document.getElementById('dropzone_label');

        ['dragover', 'dragleave', 'drop'].forEach((eventName) => {
            dropzone?.addEventListener(eventName, (e) => e.preventDefault());
        });

        dropzone?.addEventListener('dragover', () => dropzone.classList.add('border-moss', 'dark:border-sage'));
        dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('border-moss', 'dark:border-sage'));
        dropzone?.addEventListener('drop', (e) => {
            dropzone.classList.remove('border-moss', 'dark:border-sage');
            const dropped = e.dataTransfer?.files;
            if (dropped && dropped.length) {
                document.getElementById('file').files = dropped;
                document.getElementById('file').dispatchEvent(new Event('change'));
            }
        });

        document.getElementById('file')?.addEventListener('change', async function (e) {
            const file = e.target.files[0];
            const thumbField = document.getElementById('thumbnail_data');
            const pagesField = document.getElementById('pages_field');
            const preview = document.getElementById('thumbnail_preview');
            const status = document.getElementById('thumbnail_status');

            thumbField.value = '';
            pagesField.value = '';
            preview.hidden = true;
            status.textContent = '';

            if (!file) {
                return;
            }

            if (dropzoneLabel) {
                dropzoneLabel.textContent = file.name;
            }

            const isPdf = file.type === 'application/pdf';
            const isExcel = /\.(xlsx|xls)$/i.test(file.name);

            if (isPdf && window.pdfjsLib) {
                status.textContent = 'Generating preview...';

                try {
                    const buffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
                    pagesField.value = pdf.numPages;

                    const page = await pdf.getPage(1);
                    const unscaled = page.getViewport({ scale: 1 });
                    const scale = 400 / unscaled.width;
                    const viewport = page.getViewport({ scale });

                    const canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                    thumbField.value = dataUrl;
                    preview.src = dataUrl;
                    preview.hidden = false;
                    status.textContent = `Preview ready — ${pdf.numPages} page(s) detected.`;
                } catch (err) {
                    console.warn('Could not generate PDF preview:', err);
                    status.textContent = '';
                }
            } else if (isExcel && window.XLSX) {
                status.textContent = 'Reading spreadsheet...';

                try {
                    const buffer = await file.arrayBuffer();
                    const workbook = XLSX.read(buffer, { type: 'array' });
                    pagesField.value = workbook.SheetNames.length;
                    status.textContent = `${workbook.SheetNames.length} sheet(s) detected.`;
                } catch (err) {
                    console.warn('Could not read spreadsheet:', err);
                    status.textContent = '';
                }
            }
        });
    </script>
</x-app-layout>
