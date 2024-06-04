<?php

namespace tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GitlabWebhookTest extends WebTestCase
{
	private KernelBrowser $kernelBrowser;

	private function initServices(): void
	{
		$this->kernelBrowser = self::createClient();
	}

	public function testGitlabWebhook(): void
	{
		$this->initServices();

		$i = 0;
		while (true) {
			$payload = match (++$i) {
				1 => $this->getIssueOpenGitlabPayloadString(),
				default => null,
			};
			if (null === $payload) {
				break;
			}
			$uri = '/webhook/gitlab';
			$this->assertPayload($payload, $uri);
		}
	}

	private function assertPayload(array $payload, string $uri): void
	{
		$this->kernelBrowser->request('POST', $uri, [], [], [], json_encode($payload));

		$statusCode = $this->kernelBrowser->getResponse()->getStatusCode();
		$this->assertEquals(Response::HTTP_ACCEPTED, $statusCode);
	}

	private function getIssueOpenGitlabPayloadString(): array
	{
		return json_decode('
		{
			"object_kind": "issue",
			"event_type": "issue",
			"user": {
				"id": 15684224,
				"name": "Didier Corbière",
				"username": "marsender",
				"avatar_url": "https://secure.gravatar.com/avatar/18a0752f34d746dd378e23fad0f37af51a354e0d42d577f575ba59e72a872b62?s=80&d=identicon",
				"email": "[REDACTED]"
			},
			"project": {
				"id": 57869270,
				"name": "ws-website",
				"description": null,
				"web_url": "https://gitlab.com/workingshare/ws-website",
				"avatar_url": null,
				"git_ssh_url": "git@gitlab.com:workingshare/ws-website.git",
				"git_http_url": "https://gitlab.com/workingshare/ws-website.git",
				"namespace": "WorkingShare",
				"visibility_level": 0,
				"path_with_namespace": "workingshare/ws-website",
				"default_branch": "main",
				"ci_config_path": "",
				"homepage": "https://gitlab.com/workingshare/ws-website",
				"url": "git@gitlab.com:workingshare/ws-website.git",
				"ssh_url": "git@gitlab.com:workingshare/ws-website.git",
				"http_url": "https://gitlab.com/workingshare/ws-website.git"
			},
			"object_attributes": {
				"author_id": 15684224,
				"closed_at": null,
				"confidential": false,
				"created_at": "2024-05-23 09:46:27 UTC",
				"description": "",
				"discussion_locked": null,
				"due_date": null,
				"id": 146940757,
				"iid": 1,
				"last_edited_at": null,
				"last_edited_by_id": null,
				"milestone_id": null,
				"moved_to_id": null,
				"duplicated_to_id": null,
				"project_id": 57869270,
				"relative_position": null,
				"state_id": 1,
				"time_estimate": 0,
				"title": "Webhook test for WorkingShare GitLab projects",
				"updated_at": "2024-05-23 09:46:27 UTC",
				"updated_by_id": null,
				"weight": null,
				"health_status": null,
				"url": "https://gitlab.com/workingshare/ws-website/-/issues/1",
				"total_time_spent": 0,
				"time_change": 0,
				"human_total_time_spent": null,
				"human_time_change": null,
				"human_time_estimate": null,
				"assignee_ids": [
					15684224
				],
				"assignee_id": 15684224,
				"labels": [
		
				],
				"state": "opened",
				"severity": "unknown",
				"customer_relations_contacts": [
		
				],
				"action": "open"
			},
			"labels": [
		
			],
			"changes": {
				"author_id": {
					"previous": null,
					"current": 15684224
				},
				"created_at": {
					"previous": null,
					"current": "2024-05-23 09:46:27 UTC"
				},
				"description": {
					"previous": null,
					"current": ""
				},
				"id": {
					"previous": null,
					"current": 146940757
				},
				"iid": {
					"previous": null,
					"current": 1
				},
				"project_id": {
					"previous": null,
					"current": 57869270
				},
				"time_estimate": {
					"previous": null,
					"current": 0
				},
				"title": {
					"previous": null,
					"current": "Test GitLab webhook for issues"
				},
				"updated_at": {
					"previous": null,
					"current": "2024-05-23 09:46:27 UTC"
				}
			},
			"repository": {
				"name": "ws-website",
				"url": "git@gitlab.com:workingshare/ws-website.git",
				"description": null,
				"homepage": "https://gitlab.com/workingshare/ws-website"
			},
			"assignees": [
				{
					"id": 15684224,
					"name": "Didier Corbière",
					"username": "marsender",
					"avatar_url": "https://secure.gravatar.com/avatar/18a0752f34d746dd378e23fad0f37af51a354e0d42d577f575ba59e72a872b62?s=80&d=identicon",
					"email": "[REDACTED]"
				}
			]
		}
		', true);
	}
}
