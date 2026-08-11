<?php
/**
 * Copy to config/mail.php and fill in SMTP credentials.
 * Prefer storing production SMTP in Settings (database) when available.
 */
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'noreply@example.com',
    'password' => 'change-me',
    'from_email' => 'noreply@example.com',
    'from_name' => 'FUSIONLINK',
];
