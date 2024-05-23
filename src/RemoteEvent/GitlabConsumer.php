<?php

namespace App\RemoteEvent;

use App\Service\MattermostBoardService;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(name: 'gitlab')]
class GitlabConsumer implements ConsumerInterface
{
	private array $payload;

	public function __construct(
		public readonly MattermostBoardService $mattermostBoardService
	) {
	}

	public function consume(RemoteEvent $remoteEvent): void
	{
		// Process the event returned by the parser

		// For now, process only issues
		$this->payload = $remoteEvent->getPayload();
		$eventType = $this->payload['event_type'] ?? null;
		if ('issue' !== $eventType) {
			return;
		}

		$issue = $this->payload['object_attributes'] ?? null;
		if (null === $issue) {
			return;
		}

		$data = [];
		$data['repository'] = $remoteEvent->getName();
		$data['user'] = $remoteEvent->getId();

		$action = $issue['action'] ?? null;
		if (null === $action) {
			throw new \LogicException('Gitlab action is not set');
		}
		$data['action'] = $action;

		$title = $issue['title'] ?? null;
		if (null === $title) {
			throw new \LogicException('Gitlab issue title is not set');
		}
		$data['title'] = $title;

		$url = $issue['url'] ?? null;
		if (null === $url) {
			throw new \LogicException('Gitlab issue url is not set');
		}
		$data['url'] = $url;

		$assignees = $this->payload['assignees'] ?? null;
		if (null === $assignees) {
			throw new \LogicException('Gitlab issue assignees are not set');
		}
		$data['assignees'] = $assignees;

		$this->mattermostBoardService->consume($data);
	}
}
