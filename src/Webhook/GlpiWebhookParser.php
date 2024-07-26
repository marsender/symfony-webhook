<?php

namespace App\Webhook;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class GlpiWebhookParser extends AbstractRequestParser
{
	public function __construct(
		// private readonly LoggerInterface $logger
	) {
	}

	/**
	 * Request matcher that will catch /webhook/glpi path.
	 */
	protected function getRequestMatcher(): RequestMatcherInterface
	{
		// Check the conditions that the incoming webhook request
		// must match in order to be handled by this parser
		return new ChainRequestMatcher([
			// new HostRequestMatcher('glpi.website.com'),
			new IsJsonRequestMatcher(),
			new MethodRequestMatcher('POST'),
		]);
	}

	/**
	 * GLPI FPWebhook Webhook parsing and validation.
	 *
	 * @see https://github.com/FutureProcessing/glpi-webhook
	 */
	protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
	{
		$appEnv = $_ENV['APP_ENV'];

		// Validate the request against $secret
		if ('test' !== $appEnv && '' !== $secret) {
			$signature = $request->headers->get('X-Hub-Signature-256');
			$secretSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
			if (!is_string($signature) || !str_starts_with($signature, 'sha256=')	|| !hash_equals($secretSignature, $signature)) {
				throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid authentication token');
			}
		}

		$payload = $request->getPayload();
		// $this->logger->debug($payload);

		// Validate the request payload
		if (!$payload->has('ticket_id')) {
			throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Request payload does not contain required fields');
		}

		// Parse the request payload and return a RemoteEvent object
		$payload = $payload->all();

		$ticketId = $payload['ticket_id'] ?? null;
		if (null === $ticketId) {
			throw new RejectWebhookException(Response::HTTP_NOT_ACCEPTABLE, 'Webhook has no ticket_id');
		}

		$name = 'glpi-ticket';

		return new RemoteEvent($name, $ticketId, $payload);
	}
}
