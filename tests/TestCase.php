<?php

declare(strict_types=1);

namespace BotGate\Laravel\Tests;

use BotGate\Laravel\BotGateServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BotGateServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('botgate.api_key', 'test-api-key');
        $app['config']->set('botgate.webhook_secret', 'test-webhook-secret');
        $app['config']->set('botgate.retry.max_retries', 0);
    }
}
