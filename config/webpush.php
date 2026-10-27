<?php

return [

    /*
     * VAPID keys identify this server to the browsers' push services.
     * Generate them once with: php artisan webpush:vapid
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'pem_file' => env('VAPID_PEM_FILE'),
    ],

    'model' => \NotificationChannels\WebPush\PushSubscription::class,

    'table_name' => env('WEBPUSH_DB_TABLE', 'push_subscriptions'),

    'database_connection' => env('WEBPUSH_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    /*
     * Guzzle options for the outbound push request. Set WEBPUSH_PROXY if the
     * hospital server reaches the internet through a proxy.
     */
    'client_options' => array_filter([
        'timeout' => 10,
        'proxy' => env('WEBPUSH_PROXY'),
    ]),

    'automatic_padding' => env('WEBPUSH_AUTOMATIC_PADDING', true),

];
