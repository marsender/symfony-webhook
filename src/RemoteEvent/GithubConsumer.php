<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'github_callback.event')]
class GithubConsumer
{
	public function consume(RemoteEvent $remoteEvent): void
	{
		$remoteEvent->getPayload();
		// Process the event returned by our parser
	}
}
