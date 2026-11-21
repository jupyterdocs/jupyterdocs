<?php

return [

    // Master switch. Ads render nowhere until this is true AND a publisher
    // id and the slot for that placement are set, so the code can ship before
    // the AdSense account is approved.
    'enabled' => (bool) env('ADS_ENABLED', false),

    // Google AdSense publisher id, e.g. ca-pub-1234567890123456
    'client' => env('ADSENSE_CLIENT'),

    // One ad-unit id per placement (AdSense -> Ads -> By ad unit). Separate
    // units let you see which placement earns what.
    'slots' => [
        'document' => env('ADSENSE_SLOT_DOCUMENT'), // under the document preview
        'sidebar' => env('ADSENSE_SLOT_SIDEBAR'),   // under the related documents
        'feed' => env('ADSENSE_SLOT_FEED'),         // inside the browse/search grid
    ],

    // Admins see the site the way it is meant to be maintained, and clicking
    // your own ads breaks AdSense policy.
    'hide_for_admins' => true,

    // In the browse/search grid, show an ad after every N documents.
    'feed_every' => (int) env('ADS_FEED_EVERY', 8),

];
