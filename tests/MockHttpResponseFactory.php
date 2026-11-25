<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Test env http client responses, so that webhook tests never reach the real Mattermost api.
 */
class MockHttpResponseFactory
{
	public function __invoke(string $method, string $url, array $options = []): MockResponse
	{
		return new MockResponse('[]');
	}
}
