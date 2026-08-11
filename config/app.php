<?php

return [
    // Set explicitly when the app lives in a subfolder (required for PWA manifest + service worker).
    'base_path' => '/fusionlink',
    'timezone' => 'Asia/Manila',
    // Protects HTTP /cron/billing — used if you call the URL instead of the CLI cron.
    'billing_cron_token' => 'change-me-before-production',
];
