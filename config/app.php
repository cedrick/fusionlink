<?php

return [
    // Set explicitly when the app lives in a subfolder (required for PWA manifest + service worker).
    'base_path' => '/fusionlink',
    'timezone' => 'Asia/Manila',
    // Protects HTTP /cron/billing — used if you call the URL instead of the CLI cron.
    'billing_cron_token' => 'a88e443b183b321c2299bd65a11f09f90cc16d30050994a7',
];
