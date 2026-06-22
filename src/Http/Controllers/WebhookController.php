<?php

declare(strict_types=1);

namespace BotGate\Laravel\Http\Controllers;

use BotGate\DTO\Update;
use BotGate\Laravel\Events\BotGateUpdateReceived;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives verified BotGate updates and re-emits them as a domain event.
 *
 * Holds no business logic: signature verification is handled by middleware and
 * application behaviour belongs in listeners for {@see BotGateUpdateReceived}.
 */
final readonly class WebhookController
{
    public function __construct(private Dispatcher $events) {}

    public function __invoke(Request $request): JsonResponse
    {
        $update = Update::fromJson($request->getContent());

        $this->events->dispatch(new BotGateUpdateReceived($update));

        return new JsonResponse(['ok' => true]);
    }
}
