<?php

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Application settings
//  Location: config/zavero.php
//
//  The first administrator account is created by `php artisan db:seed`
//  from these values (set them in .env).
// ══════════════════════════════════════════════════════════════════

return [

    'name' => 'CFO Hotels',

    // Languages the application speaks, and which of them is right-to-left.
    'locales' => ['en', 'ar'],
    'rtl_locales' => ['ar'],

    // First administrator (created by the seeder). Leave ADMIN_PASSWORD empty to get a random one printed on screen.
    'admin' => [
        'name'     => env('ADMIN_NAME', 'Administrator'),
        'email'    => env('ADMIN_EMAIL', 'admin@cfohotels.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'support_email' => env('SUPPORT_EMAIL', 'support@cfohotels.test'),

];
