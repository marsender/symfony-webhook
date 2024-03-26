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

	/**
	 * Webhook parsing and validation.
	 *
	 * @see https://docs.github.com/en/webhooks
	 */
	protected function doParse(Request $request, string $secret): ?RemoteEvent
	{
		$data = $request->getContent();
		if (is_string($data)) {
			$eventData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
		} else {
			$eventData = $request->toArray();
		}

		$sender = $eventData['sender']['login'] ?? null;
		if (null === $sender) {
			throw new RejectWebhookException(406, 'Webhook has no sender login');
		}

		$repositoryName = $eventData['repository']['full_name'] ?? null;
		if (null === $repositoryName) {
			throw new RejectWebhookException(406, 'Webhook has no repository name');
		}

		return new RemoteEvent($repositoryName, $sender, $eventData);
	}
}
