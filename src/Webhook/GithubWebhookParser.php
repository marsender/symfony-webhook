<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class GithubWebhookParser extends AbstractRequestParser
{
	protected function getRequestMatcher(): RequestMatcherInterface
	{
		// these define the conditions that the incoming webhook request
		// must match in order to be handled by this parser
		return new ChainRequestMatcher([
			// new HostRequestMatcher('github.com'),
			new IsJsonRequestMatcher(),
			new MethodRequestMatcher('POST'),
		]);
	}

	protected function doParse(Request $request, string $secret): ?RemoteEvent
	{
		// Use toArray if it's not json
		// $eventData = $request->toArray();
		$data = $request->getContent();
		$eventData = json_decode($data, true);

		$hookId = $eventData['hook_id'] ?? null;
		if (null === $hookId) {
			throw new RejectWebhookException(406, 'Webhook has no id.');
		}
		if (!isset($eventData['repository']['name'])) {
			throw new RejectWebhookException(406, 'Webhook payload is malformed.');
		}

		// you can either return `null` or a `RemoteEvent` object
		return new RemoteEvent('github', $hookId, $eventData);
	}
}
