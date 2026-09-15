<?php

return [

    // Formats that get a full page-by-page PDF preview via CloudConvert/
    // Gotenberg. Limited to OOXML formats for now; legacy doc/ppt/xls/txt
    // are already accepted for upload and both conversion drivers can
    // handle them — extending this list later is a one-line change.
    'formats' => ['pptx', 'docx', 'xlsx'],

    'converted_path_prefix' => 'converted',

    // CloudConvert's free tier resets daily; this tracks how many
    // conversions have been used today so the app can fail over to
    // Gotenberg (or give up gracefully) once the quota is exhausted.
    'daily_limit' => (int) env('CLOUDCONVERT_DAILY_LIMIT', 10),

    'gotenberg' => [
        // Comma-separated VPS URLs. Empty by design until Phase 2 VPS exist
        // — GotenbergDriver treats an empty list as "no fallback available".
        'urls' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GOTENBERG_URLS', ''))
        ))),
        'shared_secret' => env('GOTENBERG_SHARED_SECRET'),
        'timeout' => (int) env('GOTENBERG_TIMEOUT', 60),
    ],

];
