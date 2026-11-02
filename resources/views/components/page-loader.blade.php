{{-- Planet loader shown while the browser navigates to another page. Waits a
     moment before appearing so fast navigations never flash it. --}}
<div id="page-loader" aria-hidden="true">
    <x-planet-loader :size="7" :label="__('Travelling to the next page…')" />
</div>

<script>
    (function () {
        var loader = document.getElementById('page-loader');
        var timer = null;
        var failsafe = null;

        function show() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                loader.classList.add('is-visible');
                loader.setAttribute('aria-hidden', 'false');
                // If the navigation never completes (offline, cancelled), do not trap the user.
                failsafe = window.setTimeout(hide, 15000);
            }, 250);
        }

        function hide() {
            window.clearTimeout(timer);
            window.clearTimeout(failsafe);
            loader.classList.remove('is-visible');
            loader.setAttribute('aria-hidden', 'true');
        }

        // Back/forward cache restores the page with the loader still showing.
        window.addEventListener('pageshow', hide);

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-loader')) return;
            if (a.origin !== location.origin || a.protocol.indexOf('http') !== 0) return;
            // Same-page links (anchors) do not navigate.
            if (a.pathname === location.pathname && a.search === location.search) return;
            show();
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-no-loader')) return;
            // Download forms answer with a file, so the page never navigates and the loader would hang.
            if ((form.getAttribute('action') || '').indexOf('/download') !== -1) return;
            // Let other submit handlers cancel first (e.g. confirm dialogs).
            window.setTimeout(function () { if (!e.defaultPrevented) show(); }, 0);
        });
    })();
</script>
