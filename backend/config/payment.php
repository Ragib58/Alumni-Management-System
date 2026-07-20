<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global mode
    |--------------------------------------------------------------------------
    | "sandbox" routes checkout through a locally-simulated gateway page so the
    | full flow (initiate → verify → confirm → ticket) is testable end-to-end
    | without live credentials. "live" performs real gateway API calls.
    */
    'mode' => env('PAYMENT_MODE', 'sandbox'),

    'currency' => env('PAYMENT_CURRENCY', 'BDT'),

    // Where the browser lands after the gateway finishes (SPA routes).
    'frontend_return_url'  => env('FRONTEND_URL', 'http://localhost:5173').'/payment/success',
    'frontend_cancel_url'  => env('FRONTEND_URL', 'http://localhost:5173').'/payment/failed',
    'frontend_sandbox_url' => env('FRONTEND_URL', 'http://localhost:5173').'/payment/simulate',

    'gateways' => [

        'sslcommerz' => [
            'store_id'       => env('SSLCZ_STORE_ID'),
            'store_password' => env('SSLCZ_STORE_PASSWORD'),
            'sandbox'        => env('SSLCZ_SANDBOX', true),
            'base_url'       => env('SSLCZ_SANDBOX', true)
                ? 'https://sandbox.sslcommerz.com'
                : 'https://securepay.sslcommerz.com',
        ],

        'bkash' => [
            'app_key'      => env('BKASH_APP_KEY'),
            'app_secret'   => env('BKASH_APP_SECRET'),
            'username'     => env('BKASH_USERNAME'),
            'password'     => env('BKASH_PASSWORD'),
            'sandbox'      => env('BKASH_SANDBOX', true),
            'base_url'     => env('BKASH_SANDBOX', true)
                ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
                : 'https://tokenized.pay.bka.sh/v1.2.0-beta',
        ],

        'nagad' => [
            'merchant_id'    => env('NAGAD_MERCHANT_ID'),
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER'),
            'public_key'     => env('NAGAD_PUBLIC_KEY'),
            'private_key'    => env('NAGAD_PRIVATE_KEY'),
            'sandbox'        => env('NAGAD_SANDBOX', true),
            'base_url'       => env('NAGAD_SANDBOX', true)
                ? 'https://sandbox.mynagad.com'
                : 'https://api.mynagad.com',
        ],

    ],

];
