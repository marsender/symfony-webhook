<?php

namespace App\Service;

// use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Call mattermost board api.
 *
 * @see https://htmlpreview.github.io/?https://github.com/mattermost/focalboard/blob/main/server/swagger/docs/html/index.html
 */
class MattermostBoardService
{
	private const authTokenPattern = 'MMAUTHTOKEN=';

	private array $config = [];
	private array $boardConfig = [];

	private array $issue;

	public function __construct(
		private readonly HttpClientInterface $client,
		private readonly LoggerInterface $logger,
		private readonly string $projectDir,
		private readonly string $mattermostApiUrl,
		private readonly string $mattermostAuthToken,
		private readonly string $mattermostLoginUser,
		private readonly string $mattermostLoginPassword,
		private readonly string $mattermostBoardApiUrl,
		private readonly string $mattermostBoardConfig
	) {
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

	private function loadConfig(): bool
	{
		$filePath = sprintf('%s/%s', $this->projectDir, $this->mattermostBoardConfig);

		try {
			$this->config = Yaml::parseFile($filePath);
		} catch (\Exception $e) {
			$this->logger->error('Mattermost load config exception', [
				'message' => $e->getMessage(),
			]);

			return false;
		}

		return true;
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
					if (str_starts_with($cookie, self::authTokenPattern)) {
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

			return null;
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
		if (!$this->loadConfig()) {
			return false;
		}

		$authToken = $this->getAuthenticationToken();
		if (null === $authToken) {
			return false;
		}

		if (!$this->setBoardConfig()) {
			return false;
		}

		$boardId = $this->getBoardId();
		if ('' === $boardId || '0' === $boardId) {
			return false;
		}
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
			]);

			return false;
		}

		$cardId = $data['id'] ?? null;

		return null !== $cardId;
	}

	private function setBoardConfig(): bool
	{
		$repository = $this->issue['repository'];

		// Board config is an array or the name of a board config array
		$boardConfig = $this->config['repos'][$repository] ?? null;
		// If not set, try to get the default board config
		if (null === $boardConfig) {
			$boardConfig = $this->config['repos']['default'] ?? null;
		}
		if (is_string($boardConfig)) {
			// Add repo name prefix to the issue title
			$parts = explode('/', (string) $repository);
			if (count($parts) > 1) {
				array_shift($parts);
			}
			$prefix = implode('/', $parts);
			$this->issue['title'] = sprintf('%s - %s', $prefix, $this->issue['title']);
			$boardConfig = $this->config['repos'][$boardConfig] ?? null;
		}
		// If not set and no default then abort
		if (null === $boardConfig) {
			return false;
		}

		$this->boardConfig = $boardConfig;

		return true;
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

		return $this->config['users'][$user] ?? '';
	}

	/**
	 * Get the array of assignees.
	 *
	 * @return string[]
	 */
	private function getAssignees(): array
	{
		$assignees = $this->issue['assignees'];

		$res = [];
		foreach ($assignees as $assignee) {
			$login = $assignee['login'] ?? '';
			if ('' === $login) {
				continue;
			}
			$user = $this->config['users'][$login] ?? '';
			if ('' === $user) {
				continue;
			}
			$res[] = $user;
		}

		return $res;
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
		];

		// Set assignees or by default the creator
		$key = $properties['assignedKey'];
		$assignees = $this->getAssignees();
		$res[$key] = [] === $assignees ? [$this->getCreatedBy()] : $assignees;

		return $res;
	}
}
