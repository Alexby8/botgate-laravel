<?php

declare(strict_types=1);

namespace BotGate\Laravel\Events;

use BotGate\DTO\Update;

/**
 * Dispatched for every verified update delivered to the BotGate webhook.
 *
 * Listeners own all business logic; the package itself only emits this event.
 */
final readonly class BotGateUpdateReceived
{
    public function __construct(public Update $update) {}
}
