<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'currency' => env('PAYMENT_CURRENCY', 'usd'),
    ],

    'user_management' => [
        'base_url' => env('USER_MANAGEMENT_API_URL', 'http://localhost:8001/api/v1'),
        'api_token' => env('USER_MANAGEMENT_API_TOKEN'),
        'timeout' => env('USER_MANAGEMENT_API_TIMEOUT', 30),
    ],

    'appointment_management' => [
        'base_url' => env('APPOINTMENT_MANAGEMENT_API_URL', 'http://localhost:8002/api/v1'),
        'api_token' => env('APPOINTMENT_MANAGEMENT_API_TOKEN'),
        'timeout' => env('APPOINTMENT_MANAGEMENT_API_TIMEOUT', 30),
    ],

    'payment_management' => [
        'base_url' => env('PAYMENT_MANAGEMENT_API_URL', 'http://localhost:8000/api/v1'),
        'api_token' => env('PAYMENT_MANAGEMENT_API_TOKEN'),
        'timeout' => env('PAYMENT_MANAGEMENT_API_TIMEOUT', 30),
    ],

];
