<?php

namespace App\Tests\Webhook;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractWebhookTestCase extends WebTestCase
{
	private KernelBrowser $kernelBrowser;

	abstract protected function getProvider(): string;

	abstract protected function getPayloads(): array;

	public function setUp(): void
	{
	}

	protected function initServices(): void
	{
		$this->kernelBrowser = self::createClient();
	}

	protected function testPayloads(): void
	{
		$this->initServices();

		$payloads = $this->getPayloads();

		foreach ($payloads as $payload) {
			$this->assertPayload($payload);
		}
	}

	protected function assertPayload(array $payload): void
	{
		$headers = [
			'HTTP_CONTENT_TYPE' => 'application/json',
		];

		$uri = match ($this->getProvider()) {
			'github' => '/webhook/github',
			'gitlab' => '/webhook/gitlab',
			'glpi' => '/webhook/glpi',
		};

		$this->kernelBrowser->request(method: 'POST', uri: $uri, server: $headers, content: json_encode($payload));

		$statusCode = $this->kernelBrowser->getResponse()->getStatusCode();
		$this->assertEquals(Response::HTTP_ACCEPTED, $statusCode);
	}
}
