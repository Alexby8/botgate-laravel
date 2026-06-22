<?php

declare(strict_types=1);

namespace BotGate\Laravel\Http\Middleware;

use BotGate\Webhook\SignatureValidator;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects webhook requests whose signature does not match the raw body.
 */
final readonly class VerifyBotGateSignature
{
    public function __construct(
        private SignatureValidator $validator,
        private Config $config,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $this->config->get('botgate.webhook.signature_header', 'X-BotGate-Signature');
        $signature = $request->header(is_string($header) ? $header : 'X-BotGate-Signature');

        if (! $this->validator->isValid($request->getContent(), is_string($signature) ? $signature : '')) {
            return new Response('Invalid BotGate webhook signature.', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
