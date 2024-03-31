<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ConsumeGithubIssueService
{
	private $client;

	public function __construct(private readonly LoggerInterface $logger)
	{
		$this->client = new Client(); // Initialize Guzzle client
	}

	public function consume(string $action, array $issue): void
	{
		$title = $issue['title'] ?? null;
		if (null === $title) {
			throw new \LogicException('Issue title is not set');
		}

		$url = 'http://ratio-force.localhost/fr/';
		$this->consumeWebhook($url);
	}

	private function consumeWebhook(string $issueUrl)
	{
		try {
			$response = $this->client->request('GET', $issueUrl);

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
