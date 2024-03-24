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
			new HostRequestMatcher('github.com'),
			new IsJsonRequestMatcher(),
			new MethodRequestMatcher('POST'),
		]);
	}

	protected function doParse(Request $request, string $secret): ?RemoteEvent
	{
		// in this method you check the request payload to see if it contains
		// the needed information to process this webhook
		// $content = $request->toArray();
		// if (!isset($content['signature']['token'])) {
		// 	throw new RejectWebhookException(406, 'Payload is malformed.');
		// }

		$data = $request->getContent();
		$eventData = json_decode($data, true);

		// you can either return `null` or a `RemoteEvent` object
		return new RemoteEvent('github_callback.event', 'event-id', $eventData);
	}
}
