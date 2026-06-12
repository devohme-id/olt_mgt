<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id'   => env('TELEGRAM_CHAT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'enabled'          => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
    'rate_limit'       => env('TELEGRAM_RATE_LIMIT', 30), // max messages per minute
    'parse_mode'       => 'HTML',
    'disable_preview'  => true,

    /*
    |--------------------------------------------------------------------------
    | Severity Routing
    |--------------------------------------------------------------------------
    | Send specific severities to different channels/groups
    */
    'channels' => [
        'critical' => env('TELEGRAM_CRITICAL_CHAT_ID', env('TELEGRAM_CHAT_ID')),
        'major'    => env('TELEGRAM_MAJOR_CHAT_ID', env('TELEGRAM_CHAT_ID')),
        'minor'    => env('TELEGRAM_MINOR_CHAT_ID', env('TELEGRAM_CHAT_ID')),
        'warning'  => env('TELEGRAM_WARNING_CHAT_ID', env('TELEGRAM_CHAT_ID')),
    ],
];
