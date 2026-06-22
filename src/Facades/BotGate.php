<?php

declare(strict_types=1);

namespace BotGate\Laravel\Facades;

use BotGate\BotClient;
use BotGate\Client;
use BotGate\Config;
use BotGate\Laravel\BotGateManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BotClient bot(string $botPublicId)
 * @method static Client client()
 * @method static Config config()
 *
 * @see BotGateManager
 */
final class BotGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'botgate';
    }
}
