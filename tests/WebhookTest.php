<?php

declare(strict_types=1);

namespace BotGate\Laravel\Tests;

use BotGate\Laravel\Events\BotGateUpdateReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

final class WebhookTest extends TestCase
{
    private const BODY = '{"update_id":123,"message":{"message_id":42,"chat":{"id":456},"text":"Test"}}';

    public function test_signed_webhook_dispatches_the_original_update(): void
    {
        Event::fake([BotGateUpdateReceived::class]);

        self::assertSame('/botgate/webhook', route('botgate.webhook', [], false));
        $this->webhook(hash_hmac('sha256', self::BODY, 'test-webhook-secret'))
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        Event::assertDispatchedTimes(BotGateUpdateReceived::class, 1);
        Event::assertDispatched(BotGateUpdateReceived::class, fn (BotGateUpdateReceived $event): bool => $event->update->updateId === 123
            && $event->update->payload === json_decode(self::BODY, true)
        );
    }

    public function test_invalid_signature_is_rejected_without_dispatching_an_update(): void
    {
        Event::fake([BotGateUpdateReceived::class]);

        $this->webhook('invalid')->assertForbidden();

        Event::assertNotDispatched(BotGateUpdateReceived::class);
    }

    public function test_missing_signature_is_rejected_without_dispatching_an_update(): void
    {
        Event::fake([BotGateUpdateReceived::class]);

        $this->webhook(null)->assertForbidden();

        Event::assertNotDispatched(BotGateUpdateReceived::class);
    }

    public function test_configured_signature_header_is_used(): void
    {
        Event::fake([BotGateUpdateReceived::class]);
        $this->app['config']->set('botgate.webhook.signature_header', 'X-Custom-Signature');

        $this->webhook(hash_hmac('sha256', self::BODY, 'test-webhook-secret'), 'HTTP_X_CUSTOM_SIGNATURE')
            ->assertOk();

        Event::assertDispatchedTimes(BotGateUpdateReceived::class, 1);
    }

    private function webhook(?string $signature, string $header = 'HTTP_X_BOTGATE_SIGNATURE'): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($signature !== null) {
            $server[$header] = $signature;
        }

        return $this->call('POST', '/botgate/webhook', [], [], [], $server, self::BODY);
    }
}
