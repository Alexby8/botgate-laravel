<?php

declare(strict_types=1);

namespace BotGate\Laravel;

use BotGate\Client;
use BotGate\Config;
use BotGate\Http\HttpClientInterface;
use BotGate\Webhook\SignatureValidator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class BotGateServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../config/botgate.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'botgate');

        $this->app->singleton(Config::class, $this->makeConfig(...));
        $this->app->singleton(Client::class, $this->makeClient(...));
        $this->app->singleton(SignatureValidator::class, $this->makeSignatureValidator(...));
        $this->app->singleton(BotGateManager::class, $this->makeManager(...));

        $this->app->alias(BotGateManager::class, 'botgate');
    }

    public function boot(): void
    {
        $this->publishes([self::CONFIG_PATH => $this->app->configPath('botgate.php')], 'botgate-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');
    }

    private function makeConfig(Application $app): Config
    {
        /** @var array<string,mixed> $config */
        $config = (array) $this->repository($app)->get('botgate', []);
        /** @var array{max_retries?:mixed,base_delay_ms?:mixed} $retry */
        $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];

        return new Config(
            apiKey: $this->stringValue($config['api_key'] ?? null, ''),
            baseUrl: $this->stringValue($config['base_url'] ?? null, Config::DEFAULT_BASE_URL),
            timeout: $this->floatValue($config['timeout'] ?? null, 30.0),
            maxRetries: $this->intValue($retry['max_retries'] ?? null, 3),
            retryBaseDelayMs: $this->intValue($retry['base_delay_ms'] ?? null, 500),
        );
    }

    private function stringValue(mixed $value, string $default): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    private function floatValue(mixed $value, float $default): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    private function intValue(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private function makeClient(Application $app): Client
    {
        /** @var Config $config */
        $config = $app->make(Config::class);

        // Honour a custom transport when one is bound, otherwise fall back to
        // the SDK default (Guzzle + retry). Keeps the dependency inverted.
        if ($app->bound(HttpClientInterface::class)) {
            /** @var HttpClientInterface $http */
            $http = $app->make(HttpClientInterface::class);

            return new Client($config, $http);
        }

        return Client::fromConfig($config);
    }

    private function makeSignatureValidator(Application $app): SignatureValidator
    {
        $secret = $this->repository($app)->get('botgate.webhook_secret', '');

        return new SignatureValidator(is_string($secret) ? $secret : '');
    }

    private function makeManager(Application $app): BotGateManager
    {
        /** @var Client $client */
        $client = $app->make(Client::class);

        return new BotGateManager($client);
    }

    private function repository(Application $app): Repository
    {
        /** @var Repository $repository */
        $repository = $app->make(Repository::class);

        return $repository;
    }
}
