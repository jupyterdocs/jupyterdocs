<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload a Resource') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                @auth
                    @if (! Auth::user()->canDownload())
                        <div class="mb-4 rounded-md bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-2 text-sm">
                            {{ __('Every upload gets you closer to unlocking downloads. You need :n more approved upload(s).', ['n' => Auth::user()->uploadsNeededToUnlockDownloads()]) }}
                        </div>
                    @endif
                @else
                    <div class="mb-4 rounded-md bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-2 text-sm">
                        {{ __('Uploading works without an account. But to unlock downloads later, ') }}
                        <a href="{{ route('register') }}" class="font-semibold underline">{{ __('create an account') }}</a>
                        {{ __(' first so your uploads count toward your total.') }}
                    </div>
                @endauth

                <form method="POST" action="{{ route('resources.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

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
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="resource_type_id" :value="__('Resource type')" />
                        <select id="resource_type_id" name="resource_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">{{ __('Select a type') }}</option>
                            @foreach ($resourceTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('resource_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('resource_type_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="course" :value="__('Course (optional)')" />
                            <x-text-input id="course" name="course" type="text" class="mt-1 block w-full" :value="old('course')" placeholder="e.g. Database Management Systems II" />
                        </div>
                        <div>
                            <x-input-label for="university" :value="__('University (optional)')" />
                            <x-text-input id="university" name="university" type="text" class="mt-1 block w-full" :value="old('university')" placeholder="e.g. MUST" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="tags" :value="__('Tags (optional, comma separated)')" />
                        <x-text-input id="tags" name="tags" type="text" class="mt-1 block w-full" :value="old('tags')" placeholder="databases, year 2, 2024" />
                    </div>

                    <div>
                        <x-input-label for="file" :value="__('File (PDF, Word, PowerPoint, Excel or text, max 20MB)')" />
                        <input id="file" name="file" type="file" class="mt-1 block w-full text-sm" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        <p id="thumbnail_status" class="mt-1 text-xs text-gray-400"></p>
                        <img id="thumbnail_preview" hidden class="mt-2 h-40 rounded border border-gray-200 object-cover" alt="Preview of first page">
                        <input type="hidden" name="thumbnail_data" id="thumbnail_data">
                        <input type="hidden" name="pages" id="pages_field">
                    </div>

                    <div class="flex items-start gap-2">
                        <input id="confirm_ownership" name="confirm_ownership" type="checkbox" class="mt-1 rounded border-gray-300" required>
                        <label for="confirm_ownership" class="text-sm text-gray-600">
                            {{ __('I confirm that I have the right to distribute this material.') }}
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('confirm_ownership')" class="mt-2" />

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
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
