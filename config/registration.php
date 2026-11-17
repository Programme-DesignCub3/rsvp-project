<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment BERITA Template
    |--------------------------------------------------------------------------
    |
    | Available placeholders: {name}, {type}. The rendered value is stored on
    | the visitor when they register, so changing this only affects new records.
    |
    */
    'payment_berita_template' => env('PAYMENT_BERITA_TEMPLATE', '{name} {type}'),

    /*
    |--------------------------------------------------------------------------
    | Placeholder Bendahara Account
    |--------------------------------------------------------------------------
    |
    | Used only by BendaharaUserSeeder. Change the password before production.
    |
    */
    'bendahara_email' => env('BENDAHARA_EMAIL', 'bendahara@bnimagnitude.com'),
    'bendahara_password' => env('BENDAHARA_PASSWORD', 'password'),
];
