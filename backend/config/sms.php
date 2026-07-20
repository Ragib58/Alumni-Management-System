<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    | "log"  — writes SMS to the log (default; no external service needed)
    | "twilio" / "vonage" / "bulksms" — production providers (creds via .env)
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'from' => env('SMS_FROM', 'AMS'),

    'providers' => [
        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
        'vonage' => [
            'key'    => env('VONAGE_KEY'),
            'secret' => env('VONAGE_SECRET'),
        ],
        // Generic HTTP gateway (many BD providers: SSL Wireless, etc.)
        'http' => [
            'url'    => env('SMS_HTTP_URL'),
            'token'  => env('SMS_HTTP_TOKEN'),
        ],
    ],

];
