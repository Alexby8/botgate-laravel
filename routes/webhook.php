<?php

declare(strict_types=1);

use BotGate\Laravel\Http\Controllers\WebhookController;
use BotGate\Laravel\Http\Middleware\VerifyBotGateSignature;
use Illuminate\Support\Facades\Route;

$middleware = array_merge(
    (array) config('botgate.webhook.middleware', []),
    [VerifyBotGateSignature::class],
);

Route::post((string) config('botgate.webhook.path', 'botgate/webhook'), WebhookController::class)
    ->middleware($middleware)
    ->name('botgate.webhook');
