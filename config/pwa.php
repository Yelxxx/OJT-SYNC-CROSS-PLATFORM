<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PWA Feature Flag
    |--------------------------------------------------------------------------
    |
    | Gates everything the browser needs to treat OJT Sync as an installable
    | app: the manifest link, the "pwa enabled" meta flag the JS bundle reads
    | before registering the service worker, and the service worker itself.
    | The service worker ships dark until QA passes, then this flips on per
    | environment (see CROSS_PLATFORM_PLAN.md, Phase 0).
    |
    */

    'enabled' => env('PWA_ENABLED', false),

];
