<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    // See docs/DECISIONS.md — unified card + mobile money gateway for the Financial Center.
    // AppServiceProvider only binds the real DGatewayPaymentGateway when 'key' is set; until
    // then, FakePaymentGateway keeps the checkout flow working end-to-end with no real charges.
    'dgateway' => [
        'api_url' => env('DGATEWAY_API_URL', 'https://dgatewayapi.desispay.com'),
        'key' => env('DGATEWAY_API_KEY'),
        'webhook_secret' => env('DGATEWAY_WEBHOOK_SECRET'),
        'default_currency' => env('DGATEWAY_DEFAULT_CURRENCY', 'UGX'),
    ],

];
