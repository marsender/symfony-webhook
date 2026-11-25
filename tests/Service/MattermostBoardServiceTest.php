<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MattermostBoardService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MattermostBoardServiceTest extends TestCase
{
	private const string API_URL = 'https://mattermost.test/plugins/focalboard/api/v2';
	private const int DATE_FROM = 1731663780000;
	private const int DATE_TO = 1732400000000;

	/** @var list<array{method: string, url: string, body: ?array}> */
	private array $requests = [];

	public function testGitlabCloseMatchesIssuesUrlFromWorkItemsUrl(): void
	{
		$service = $this->createService([
			$this->card('card-other-project', 'https://gitlab.com/facility-project/frontend/-/issues/65'),
			$this->card('card-other-issue', 'https://gitlab.com/facility-project/backend/-/issues/6'),
			$this->card('card-65', 'https://gitlab.com/facility-project/backend/-/issues/65', 'status-todo', ['from' => self::DATE_FROM]),
		]);

		$before = (int) floor(microtime(true) * 1000);
		$res = $service->consume($this->issue('facility-project/backend', 'close', 'https://gitlab.com/facility-project/backend/-/work_items/65'));
		$after = (int) ceil(microtime(true) * 1000);

		$this->assertTrue($res);
		$this->assertCount(2, $this->requests);
		$this->assertSame('GET', $this->requests[0]['method']);
		$this->assertSame(self::API_URL.'/boards/board-1/cards?page=0&per_page=0', $this->requests[0]['url']);
		$this->assertSame('PATCH', $this->requests[1]['method']);
		$this->assertSame(self::API_URL.'/cards/card-65', $this->requests[1]['url']);

		$updated = $this->requests[1]['body']['updatedProperties'];
		$this->assertSame('status-done', $updated['status-key']);
		$date = json_decode($updated['date-key'], true);
		$this->assertSame(self::DATE_FROM, $date['from']);
		$this->assertGreaterThanOrEqual($before, $date['to']);
		$this->assertLessThanOrEqual($after, $date['to']);
	}

	public function testGithubReopenRestoresInitialStatusAndRemovesDateEnd(): void
	{
		$service = $this->createService([
			$this->card('card-12', 'https://github.com/marsender/symfony-webhook/issues/12', 'status-done', ['from' => self::DATE_FROM, 'to' => self::DATE_TO]),
		]);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'reopened', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertTrue($res);
		$this->assertCount(2, $this->requests);
		$this->assertSame(self::API_URL.'/cards/card-12', $this->requests[1]['url']);
		$this->assertSame(['updatedProperties' => [
			'status-key' => 'status-todo',
			'date-key' => json_encode(['from' => self::DATE_FROM]),
		]], $this->requests[1]['body']);
	}

	public function testCloseAlreadyClosedCardSkipsPatch(): void
	{
		$service = $this->createService([
			$this->card('card-12', 'https://github.com/marsender/symfony-webhook/issues/12', 'status-done', ['from' => self::DATE_FROM, 'to' => self::DATE_TO]),
		]);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'closed', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertTrue($res);
		$this->assertCount(1, $this->requests);
	}

	public function testCloseCardAlreadyDoneSetsOnlyDateEnd(): void
	{
		$service = $this->createService([
			$this->card('card-12', 'https://github.com/marsender/symfony-webhook/issues/12', 'status-done', ['from' => self::DATE_FROM]),
		]);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'closed', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertTrue($res);
		$this->assertCount(2, $this->requests);
		$updated = $this->requests[1]['body']['updatedProperties'];
		$this->assertSame(['date-key'], array_keys($updated));
		$this->assertArrayHasKey('to', json_decode($updated['date-key'], true));
	}

	public function testCloseCardWithoutDateSetsOnlyStatus(): void
	{
		$service = $this->createService([
			$this->card('card-12', 'https://github.com/marsender/symfony-webhook/issues/12'),
		]);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'closed', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertTrue($res);
		$this->assertSame(['updatedProperties' => ['status-key' => 'status-done']], $this->requests[1]['body']);
	}

	public function testCloseWithoutMatchingCard(): void
	{
		$service = $this->createService([
			$this->card('card-120', 'https://github.com/marsender/symfony-webhook/issues/120'),
			$this->card('card-no-url', null),
		]);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'closed', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertFalse($res);
		$this->assertCount(1, $this->requests);
	}

	public function testCloseWithoutClosedStatusConfigCallsNoApi(): void
	{
		$service = $this->createService([]);

		$res = $service->consume($this->issue('marsender/legacy', 'close', 'https://gitlab.com/marsender/legacy/-/issues/1'));

		$this->assertFalse($res);
		$this->assertCount(0, $this->requests);
	}

	public function testPatchErrorReturnsFalse(): void
	{
		$service = $this->createService([
			$this->card('card-12', 'https://github.com/marsender/symfony-webhook/issues/12'),
		], 403);

		$res = $service->consume($this->issue('marsender/symfony-webhook', 'closed', 'https://github.com/marsender/symfony-webhook/issues/12'));

		$this->assertFalse($res);
		$this->assertCount(2, $this->requests);
	}

	private function createService(array $cards, int $patchStatusCode = 200): MattermostBoardService
	{
		$client = new MockHttpClient(function (string $method, string $url, array $options) use ($cards, $patchStatusCode): MockResponse {
			$body = $options['body'] ?? '';
			$this->requests[] = [
				'method' => $method,
				'url' => $url,
				'body' => '' === $body ? null : json_decode((string) $body, true),
			];
			$this->assertContains('Authorization: Bearer test-token', $options['headers']);

			if ('GET' === $method) {
				return new MockResponse((string) json_encode($cards));
			}

			return new MockResponse('{}', ['http_code' => $patchStatusCode]);
		});

		return new MattermostBoardService(
			$client,
			new NullLogger(),
			dirname(__DIR__),
			'https://mattermost.test/api/v4/users/login',
			'test-token',
			'',
			'',
			self::API_URL,
			'fixtures/mattermost_board.yaml',
		);
	}

	private function card(string $id, ?string $url, string $status = 'status-todo', ?array $date = null): array
	{
		$properties = ['status-key' => $status];
		if (null !== $url) {
			$properties['url-key'] = $url;
		}
		if (null !== $date) {
			$properties['date-key'] = json_encode($date);
		}

		return ['id' => $id, 'title' => $id, 'properties' => $properties];
	}

	private function issue(string $repository, string $action, string $url): array
	{
		return [
			'repository' => $repository,
			'user' => 'marsender',
			'action' => $action,
			'title' => 'Issue title',
			'url' => $url,
			'assignees' => [],
		];
	}
}
