<?php

namespace App\RemoteEvent;

use App\Service\ConsumeGithubIssueService;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'github')]
class GithubConsumer implements ConsumerInterface
{
	private string $id;
	private string $name;
	private array $payload;

	public function __construct(
		public readonly ConsumeGithubIssueService $consumeGithubIssueService
	) {
	}

	public function consume(RemoteEvent $remoteEvent): void
	{
		// Process the event returned by the parser
		$this->id = $remoteEvent->getId();
		$this->name = $remoteEvent->getName();
		$this->payload = $remoteEvent->getPayload();

		if (isset($this->payload['issue'])) {
			$action = $this->payload['action'] ?? null;
			switch ($action) {
				case 'assigned':
				case 'opened':
				case 'closed':
					$this->consumeGithubIssueService->consume($action, $this->payload['issue']);
					break;
			}
		}
	}
}
