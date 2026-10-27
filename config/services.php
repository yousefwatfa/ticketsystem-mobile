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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    // telegram bot API configuration used by notification channel
    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME', 'nmcsystembot'),
        'proxy' => env('TELEGRAM_PROXY'),
        'base_uri' => env('TELEGRAM_API_BASE_URI', null),
        'guzzle' => [
            // Set to false in local env to bypass corporate/self-signed proxy TLS issues
            'verify' => filter_var(env('TELEGRAM_GUZZLE_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    // Expo push for the native app (access token optional; set it if "enhanced push security" is enabled in Expo)
    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN'),
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

];
