<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'github')]
class GithubConsumer implements ConsumerInterface
{
	public function consume(RemoteEvent $remoteEvent): void
	{
		$remoteEvent->getPayload();
		// Process the event returned by the parser
	}
}
