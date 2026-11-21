<?php

return [

    // Google Analytics 4 measurement id (G-XXXXXXXXXX). Leave empty to run no
    // third-party analytics at all.
    'analytics_id' => env('GA_MEASUREMENT_ID'),

    // Public profiles for the footer and the Organization schema. Only the
    // ones that are set are shown, so nothing links to an account that does
    // not exist yet.
    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK'),
        'x' => env('SOCIAL_X'),
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'linkedin' => env('SOCIAL_LINKEDIN'),
        'youtube' => env('SOCIAL_YOUTUBE'),
    ],

];
