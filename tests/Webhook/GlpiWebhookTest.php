<?php

namespace App\Tests\Webhook;

class GlpiWebhookTest extends AbstractWebhookTestCase
{
	protected function getProvider(): string
	{
		return 'glpi';
	}

	protected function getPayloads(): array
	{
		return [$this->getIssueOpenGlpiPayloadString()];
	}

	public function testGlpiWebhook(): void
	{
		$this->testPayloads();
	}

	private function getIssueOpenGlpiPayloadString(): array
	{
		return json_decode('
		{
			"ticket_id":4,
			"subject":"Test 4",
			"content":"&#60;p&#62;TTTT&#60;\/p&#62;"
		}
		', true);
	}
}
