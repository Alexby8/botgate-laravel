<?php

declare(strict_types=1);

use BotGate\Config;

return [
    /*
    |--------------------------------------------------------------------------
    | API credentials
    |--------------------------------------------------------------------------
    |
    | The API key issued by BotGate and the base URL of the BotGate instance
    | every outbound request is sent to.
    |
    */

    'api_key' => env('BOTGATE_API_KEY'),

    'base_url' => env('BOTGATE_BASE_URL', Config::DEFAULT_BASE_URL),

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    |
    | Request timeout (seconds) and retry/backoff policy applied by the default
    | HTTP client. Ignored when you bind your own HttpClientInterface.
    |
    */

    'timeout' => (float) env('BOTGATE_TIMEOUT', 30),

    'retry' => [
        'max_retries' => (int) env('BOTGATE_RETRY_MAX', 3),
        'base_delay_ms' => (int) env('BOTGATE_RETRY_BASE_DELAY_MS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Incoming webhook
    |--------------------------------------------------------------------------
    |
    | The shared secret used to verify the signature of incoming updates, the
    | route the package registers to receive them, the middleware applied to
    | that route and the header carrying the HMAC signature.
    |
    */

    'webhook_secret' => env('BOTGATE_WEBHOOK_SECRET'),

    'webhook' => [
        'path' => env('BOTGATE_WEBHOOK_PATH', 'botgate/webhook'),
        'middleware' => ['api'],
        'signature_header' => 'X-BotGate-Signature',
    ],
];
