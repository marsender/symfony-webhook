<?php

namespace App\RemoteEvent;

use App\Service\MattermostBoardService;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'github')]
class GithubConsumer implements ConsumerInterface
{
	private array $payload;

	public function __construct(
		private readonly MattermostBoardService $mattermostBoardService
	) {
	}

	public function consume(RemoteEvent $remoteEvent): void
	{
		// Process the event returned by the parser

		// For now, process only issues
		$this->payload = $remoteEvent->getPayload();
		$issue = $this->payload['issue'] ?? null;
		if (null === $issue) {
			return;
		}

		$data = [];
		$data['repository'] = $remoteEvent->getName();
		$data['user'] = $remoteEvent->getId();

		$action = $this->payload['action'] ?? null;
		if (null === $action) {
			throw new \LogicException('Github action is not set');
		}
		$data['action'] = $action;

		$title = $issue['title'] ?? null;
		if (null === $title) {
			throw new \LogicException('Github issue title is not set');
		}
		$data['title'] = $title;

		$url = $issue['html_url'] ?? null;
		if (null === $url) {
			throw new \LogicException('Github issue url is not set');
		}
		$data['url'] = $url;

		$assignees = $issue['assignees'] ?? null;
		if (null === $assignees) {
			throw new \LogicException('Github issue assignees are not set');
		}
		$data['assignees'] = $assignees;

		$this->mattermostBoardService->consume($data);
	}
}
