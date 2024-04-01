<?php

namespace App\Service;

// use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MattermostBoardService
{
	private const authTokenPattern = 'MMAUTHTOKEN=';

	private array $config;
	private array $boardConfig;

	private array $issue;

	public function __construct(
		private readonly HttpClientInterface $client,
		private readonly LoggerInterface $logger,
		private readonly string $mattermostApiUrl,
		private readonly string $mattermostAuthToken,
		private readonly string $mattermostLoginUser,
		private readonly string $mattermostLoginPassword,
		private readonly string $mattermostBoardApiUrl
	) {
		// Set a config per github repository
		$this->config = [];

		$createdBy = [
			'marsender' => 'ynrzgwid4bb3jgn377jqsoy8oc',
			'QDZantoine' => 't4wjf6qs47gb3qcpceysxaq6de',
		];

		$repo = 'alveos/easylibrary_back';
		$data = [
			'boardId' => 'b8crmfdz4jtgt3rzeogww9tzqxc',
			'createdBy' => $createdBy,
			'properties' => [
				'dateKey' => 'a9ieh4p9kmq17fynqyspioi5b5w',
				'statusKey' => 'ajexcf358qpw6bwhtpsapucbk3e',
				'statusValue' => 'a6fd8iyp33t9ckgbrorwbep4j6e',
				'urlKey' => 'ac95t57ir869uacidk3w3dwf8qh',
				'assignedKey' => 'auzf4n7979j895njp7qw7i3hpto',
			],
		];
		$this->config[$repo] = $data;

		$repo = 'QDZantoine/spot-to-work';
		$data = [
			'boardId' => 'bttz8yzp5htbgdgwbm3a8aszf7a',
			'createdBy' => $createdBy,
			'properties' => [
				'dateKey' => 'a5m9ngza95egtm64nww3w9agjja',
				'statusKey' => 'ap4cno3hufwtzrwjyt6jusihrzy',
				'statusValue' => 'ayfyddrgs7bq5zsjn6d146ox78c',
				'urlKey' => 'a51xn7pcmdyp7zepzuxfbqgaxur',
				'assignedKey' => 'ay6zw4ohtou871oeb9364kfsm4w',
			],
		];
		$this->config[$repo] = $data;

		$repo = 'marsender/ratio-force';
		$data = [
			'boardId' => 'b3t3dne4jgff4mriw9igyafyuur',
			'createdBy' => $createdBy,
			'properties' => [
				'dateKey' => 'aiz9t6wz5bfzf7n5q99big9344c',
				'statusKey' => 'auixkgzpweq77d7x9b98cw6gw3e',
				'statusValue' => 'a3sa3y94o1y3onhwotuzqxcxhdc',
				'urlKey' => 'a5ry3q77jraod5dk9od4sdk318c',
				'assignedKey' => 'aukh6okxkwqibjo1ychphp4yx1a',
			],
		];
		$this->config[$repo] = $data;

		$repo = 'marsender/symfony-webhook';
		$data = [
			'boardId' => 'bnun4wowr4inf8ebeh4mrcyx6cc',
			'createdBy' => $createdBy,
			'properties' => [
				'dateKey' => 'adhstgkj3usbw5ne6kaziwrdhbh',
				'statusKey' => 'adj7gm9wxsy1w61hfksxzkce75h',
				'statusValue' => 'a59obmejixs19cefych19kyxmaw',
				'urlKey' => 'a45oaj4wx8k6j5h9wbc769neqga',
				'assignedKey' => 'asyzr433wkogfectua9rnh894zr',
			],
		];
		$this->config[$repo] = $data;
		$repo = 'symfony-assetmapper';
		$this->config[$repo] = $data;
	}

	public function consume(array $issue): bool
	{
		$res = false;

		$this->issue = $issue;

		$action = $this->issue['action'] ?? null;

		switch ($action) {
			case 'opened':
				$res = $this->createCard();
				break;
			case 'assigned':
			case 'closed':
			default:
				break;
		}

		return $res;
	}

	/**
	 * Get board api authentication token.
	 *
	 * @see https://api.mattermost.com/#tag/authentication
	 *
	 * Curl test to get session and csrf token used for boards access :
	 * curl -i -d '{"login_id":"user","password":"ChangeMe"}' \
	 * -H "X-Requested-With: XMLHttpRequest" \
	 * https://host/api/v4/users/login
	 *
	 * Get csrf token from header
	 * set-cookie: MMAUTHTOKEN=sdfdrzhizigs9858gxu8tti8ce; Path=/; Expires=Sat, 28 Sep 2024 11:13:03 GMT; Max-Age=15552000; HttpOnly; Secure
	 */
	private function getAuthenticationToken(): ?string
	{
		// Get permanent auth token if given
		if ('' !== $this->mattermostAuthToken) {
			return $this->mattermostAuthToken;
		}

		// Quit if user login is not set
		if ('' === $this->mattermostLoginUser || '' === $this->mattermostLoginPassword) {
			return null;
		}

		$authToken = null;

		$login = [
			'login_id' => $this->mattermostLoginUser,
			'password' => $this->mattermostLoginPassword,
		];

		try {
			$response = $this->client->request('POST', $this->mattermostApiUrl, [
				'headers' => [
					'X-Requested-With' => 'XMLHttpRequest',
				],
				'json' => $login,
			]);

			$statusCode = $response->getStatusCode();
			if (Response::HTTP_OK !== $statusCode) {
				$this->logger->error('Mattermost authentication error', [
					'status_code' => $statusCode,
					'response' => $response->getContent(),
				]);

				return null;
			}

			// Get the headers
			$headers = $response->getHeaders();

			// Get the token from the headers
			$cookies = $headers['set-cookie'] ?? null;
			if (is_array($cookies)) {
				foreach ($cookies as $cookie) {
					// Extract the token from the header
					if (0 === strpos($cookie, self::authTokenPattern)) {
						$length = strlen(self::authTokenPattern);
						$authToken = substr($cookie, $length, strpos($cookie, ';') - $length);
						break;
					}
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Mattermost authentication exception', [
				'message' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
		}

		return $authToken;
	}

	/**
	 * Create a board card.
	 *
	 * Curl test
	 * boardId=ChangeMe
	 * curl -H 'Authorization: Bearer ChangeMe' \
	 * -H 'X-Requested-With: XMLHttpRequest' \
	 * -H 'X-CSRF-Token: ChangeMe' \
	 * -H 'Content-Type: application/json' \
	 * -X POST \
	 * https://host/plugins/focalboard/api/v2/boards/${boardId}/cards \
	 * -d '
	 * {
	 *   "title": "This is an api created card",
	 *   "createdBy": "ynrzgwid4bb3jgn377jqsoy8oc",
	 *   "properties": {
	 *     "ajexcf358qpw6bwhtpsapucbk3e": "a6fd8iyp33t9ckgbrorwbep4j6e",
	 *     "ac95t57ir869uacidk3w3dwf8qh": "url"
	 *   }
	 * }
	 * '
	 */
	private function createCard(): bool
	{
		$authToken = $this->getAuthenticationToken();
		if (null === $authToken) {
			return false;
		}

		$this->setBoardConfig();
		if (null === $this->boardConfig) {
			return false;
		}

		$boardId = $this->getBoardId();
		$url = sprintf('%s/boards/%s/cards', $this->mattermostBoardApiUrl, $boardId);

		$card = [];
		$card['title'] = $this->issue['title'];
		$card['createdBy'] = $this->getCreatedBy();
		$card['properties'] = $this->getProperties();

		try {
			$response = $this->client->request('POST', $url, [
				'headers' => [
					'Authorization' => sprintf('Bearer %s', $authToken),
					'X-Requested-With' => 'XMLHttpRequest',
				],
				'json' => $card,
			]);

			$statusCode = $response->getStatusCode();
			if (Response::HTTP_OK !== $statusCode) {
				$this->logger->error('Mattermost create card error', [
					'status_code' => $statusCode,
					// 'response' => $response->getContent(),
				]);

				return false;
			}

			$data = json_decode($response->getContent(), true);
		} catch (\Exception $e) {
			$this->logger->error('Mattermost create card exception', [
				'message' => $e->getMessage(),
				// 'trace' => $e->getTraceAsString(),
			]);

			return false;
		}

		$cardId = $data['id'] ?? null;
		if (null === $cardId) {
			return false;
		}

		return true;
	}

	private function setBoardConfig(): void
	{
		$repository = $this->issue['repository'];

		$this->boardConfig = $this->config[$repository] ?? null;
	}

	/**
	 * Get the board id where the card must be added.
	 *
	 * The board id is in the board url, eg: https://host/boards/team/teamId/boardId/key
	 */
	private function getBoardId(): string
	{
		return $this->boardConfig['boardId'] ?? '';
	}

	private function getCreatedBy(): string
	{
		$user = $this->issue['user'];

		return $this->boardConfig['createdBy'][$user] ?? '';
	}

	private function getProperties(): array
	{
		$res = [];

		$issueUrl = $this->issue['url'];
		$properties = $this->boardConfig['properties'];

		// Get today timestamp
		$dateTimestamp = round(microtime(true) * 1000);

		$res = [
			$properties['statusKey'] => $properties['statusValue'],
			$properties['dateKey'] => json_encode(['from' => $dateTimestamp]),
			$properties['urlKey'] => $issueUrl,
			$properties['assignedKey'] => [$this->getCreatedBy()],
		];

		return $res;
	}
}
