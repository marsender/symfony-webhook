<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class MattermostBoardService
{
	private readonly Client $client;

	private array $issue;

	public function __construct(private readonly LoggerInterface $logger)
	{
		$this->client = new Client(); // Initialize Guzzle client
	}

	public function consume(array $issue): void
	{
		$this->issue = $issue;

		$action = $this->issue['action'] ?? null;

		switch ($action) {
			case 'opened':
				$this->createCard();
				break;
			case 'assigned':
			case 'closed':
			default:
				return;
		}
	}

	private function createCard(): void
	{
		$boardUrl = 'http://ratio-force.localhost/fr/';

		try {
			$response = $this->client->request('GET', $boardUrl);

			if (200 == $response->getStatusCode()) {
				$data = json_decode($response->getBody(), true);
				// Handle the $data if needed
			} else {
				// Log the error
				$this->logger->error('Error consuming GitHub issue webhook', [
					'status_code' => $response->getStatusCode(),
					'response' => $response->getBody()->getContents(),
				]);
			}
		} catch (\Exception $e) {
			$this->logger->error('Exception consuming GitHub issue webhook', [
				'message' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
		}
	}
}
