<?php

declare(strict_types=1);

namespace BotGate\Laravel;

use BotGate\BotClient;
use BotGate\Client;
use BotGate\Config;

/**
 * Thin Laravel-facing facade over the SDK {@see Client}.
 *
 * Holds no business logic; it only exposes the underlying client through a
 * single entry point resolved from the container.
 */
final readonly class BotGateManager
{
    public function __construct(private Client $client) {}

    /**
     * Resolve a client scoped to a single bot.
     */
    public function bot(string $botPublicId): BotClient
    {
        return $this->client->bot($botPublicId);
    }

    /**
     * The underlying SDK client.
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * The resolved SDK configuration.
     */
    public function config(): Config
    {
        return $this->client->config();
    }
}
