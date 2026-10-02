<?php

declare(strict_types=1);

namespace BotGate\Laravel\Tests;

use BotGate\Client;
use BotGate\Config;
use BotGate\Http\HttpClientInterface;
use BotGate\Http\HttpRequest;
use BotGate\Http\HttpResponse;
use BotGate\Laravel\BotGateManager;
use BotGate\Laravel\BotGateServiceProvider;
use BotGate\Laravel\Facades\BotGate;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\ServiceProvider;

final class ServiceProviderTest extends TestCase
{
    public function test_client_manager_and_facade_resolve_the_same_configuration(): void
    {
        $this->app['config']->set('botgate.timeout', 15);

        $client = $this->app->make(Client::class);
        $config = $this->app->make(Config::class);
        $manager = $this->app->make(BotGateManager::class);

        self::assertSame($client, $this->app->make(Client::class));
        self::assertSame($client, $manager->client());
        self::assertSame($client, BotGate::client());
        self::assertSame($config, BotGate::config());
        self::assertSame($manager, $this->app->make('botgate'));
        self::assertSame('test-api-key', $config->apiKey);
        self::assertSame(Config::DEFAULT_BASE_URL, $config->baseUrl);
        self::assertSame(15.0, $config->timeout);
        self::assertSame(0, $config->maxRetries);
    }

    public function test_custom_transport_receives_requests_from_the_facade(): void
    {
        $transport = new class implements HttpClientInterface
        {
            public ?HttpRequest $request = null;

            public function send(HttpRequest $request): HttpResponse
            {
                $this->request = $request;

                return new HttpResponse(200, [], Utils::streamFor('{"ok":true,"result":{"message_id":42}}'));
            }
        };
        $this->app->instance(HttpClientInterface::class, $transport);

        $response = BotGate::bot('test-bot')->call('sendMessage', ['chat_id' => 123, 'text' => 'Test']);

        self::assertTrue($response->ok);
        self::assertSame(['message_id' => 42], $response->result);
        self::assertNotNull($transport->request);
        self::assertSame('POST', $transport->request->method);
        self::assertSame('https://bot-gate.ru/api/v1/bots/test-bot/sendMessage', $transport->request->uri);
        self::assertSame('Bearer test-api-key', $transport->request->headers['Authorization']);
        self::assertSame(['chat_id' => 123, 'text' => 'Test'], $transport->request->json);
    }

    public function test_configuration_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(BotGateServiceProvider::class, 'botgate-config');

        self::assertContains($this->app->configPath('botgate.php'), $paths);
        foreach (array_keys($paths) as $source) {
            self::assertFileExists($source);
        }
    }
}
