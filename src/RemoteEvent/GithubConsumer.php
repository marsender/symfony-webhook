<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'github_callback.event')]
class GithubConsumer
{
	public function consume(RemoteEvent $event): void
	{
		$payload = $event->getPayload();
		// Process the event returned by our parser
	}
}
