@props(['value' => '', 'placeholder' => 'Search notes, past papers, courses…'])

{{-- A search input with an as-you-type dropdown of topics, courses and
     document titles. Suggestions are built with textContent only, so
     nothing a document is titled can inject markup. --}}
<div class="relative flex-1 min-w-[200px]" data-search-box data-suggest-url="{{ route('search.suggest') }}">
    <label for="search-q" class="block text-sm font-display font-medium text-pine dark:text-mint">{{ __('Search') }}</label>
    <input id="search-q" type="search" name="q" value="{{ $value }}" placeholder="{{ $placeholder }}"
           maxlength="200" autocomplete="off" spellcheck="false"
           role="combobox" aria-expanded="false" aria-controls="search-suggestions" aria-autocomplete="list"
           class="mt-1 block w-full rounded-lg border-pine/15 dark:border-mint/15 bg-white dark:bg-cypress text-pine dark:text-mint placeholder:text-jd-ink-muted/60 dark:placeholder-sage/60 focus:border-moss dark:focus:border-sage focus:ring-moss dark:focus:ring-sage text-sm font-display">
    <ul id="search-suggestions" role="listbox" hidden
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-pine/10 dark:border-mint/10 bg-white dark:bg-pine shadow-lg"></ul>
</div>

@once
    <script>
        (function () {
            var box = document.querySelector('[data-search-box]');
            if (! box) return;

            var input = box.querySelector('input');
            var list = box.querySelector('ul');
            var url = box.dataset.suggestUrl;
            var timer = null;
            var controller = null;
            var cache = {};
            var active = -1;

            function close() {
                list.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                active = -1;
            }

            function highlight(index) {
                var items = list.children;
                for (var i = 0; i < items.length; i++) {
                    items[i].classList.toggle('bg-jd-surface-2', i === index);
                    items[i].classList.toggle('dark:bg-cypress', i === index);
                    items[i].setAttribute('aria-selected', i === index ? 'true' : 'false');
                }
                active = index;
            }

            function choose(text) {
                input.value = text;
                close();
                input.form.submit();
            }

            function render(suggestions) {
                list.replaceChildren();

                if (! suggestions.length) return close();

                suggestions.forEach(function (item, index) {
                    var li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.className = 'flex items-center justify-between gap-3 px-3 py-2 text-sm cursor-pointer text-pine dark:text-mint';

                    var text = document.createElement('span');
                    text.className = 'truncate font-display';
                    text.textContent = item.text;

                    var type = document.createElement('span');
                    type.className = 'shrink-0 text-[10px] font-mono uppercase text-jd-ink-muted dark:text-sage';
                    type.textContent = item.type;

                    li.append(text, type);
                    // mousedown, not click: the input's blur would close the list first.
                    li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(item.text); });
                    li.addEventListener('mouseenter', function () { highlight(index); });
                    list.appendChild(li);
                });

                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                active = -1;
            }

            function fetchSuggestions() {
                var term = input.value.trim();

                if (term.length < 2) return close();
                if (cache[term]) return render(cache[term]);

                if (controller) controller.abort();
                controller = new AbortController();

                fetch(url + '?q=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal,
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        cache[term] = data.suggestions;
                        if (input.value.trim() === term) render(data.suggestions);
                    })
                    .catch(function () { /* aborted or offline: the plain search still works */ });
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(fetchSuggestions, 150);
            });

            input.addEventListener('keydown', function (e) {
                var count = list.children.length;

                if (list.hidden || ! count) return;

                if (e.key === 'ArrowDown') { e.preventDefault(); highlight((active + 1) % count); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); highlight((active - 1 + count) % count); }
                else if (e.key === 'Escape') { close(); }
                else if (e.key === 'Enter' && active > -1) {
                    e.preventDefault();
                    choose(list.children[active].firstChild.textContent);
                }
            });

            input.addEventListener('blur', function () { setTimeout(close, 120); });
            input.addEventListener('focus', function () { if (input.value.trim().length >= 2) fetchSuggestions(); });
        })();
    </script>
@endonce
