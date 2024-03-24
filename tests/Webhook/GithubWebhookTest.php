<?php

namespace tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GithubWebhookTest extends WebTestCase
{
	private KernelBrowser $kernelBrowser;

	private function initServices(): void
	{
		$this->kernelBrowser = self::createClient();
	}

	public function testWebhook(): void
	{
		$this->initServices();

		$data = $this->getPayloadData();

		$this->kernelBrowser->request('POST', '/webhook/github', [], [], [], json_encode($data));

		$statusCode = $this->kernelBrowser->getResponse()->getStatusCode();
		$this->assertEquals(Response::HTTP_ACCEPTED, $statusCode);
	}

	private function getPayloadData(): array
	{
		return [
			'zen' => 'Encourage flow.',
			'hook_id' => 468512335,
			'hook' => [
				'type' => 'Repository',
				'id' => 468512335,
				'name' => 'web',
				'active' => true,
				'events' => [
					'issues',
					'push',
				],
				'config' => [
					'content_type' => 'form',
					'insecure_ssl' => '0',
					'url' => 'https://mm.opale-concept.com/hooks/p1up64kurtgdxerb3rtwnqsqbw',
				],
				'updated_at' => '2024-03-24T15:28:59Z',
				'created_at' => '2024-03-24T15:28:59Z',
				'url' => 'https://api.github.com/repos/marsender/ratio-force/hooks/468512335',
				'test_url' => 'https://api.github.com/repos/marsender/ratio-force/hooks/468512335/test',
				'ping_url' => 'https://api.github.com/repos/marsender/ratio-force/hooks/468512335/pings',
				'deliveries_url' => 'https://api.github.com/repos/marsender/ratio-force/hooks/468512335/deliveries',
				'last_response' => [
					'code' => null,
					'status' => 'unused',
					'message' => null,
				],
			],
			'repository' => [
				'id' => 772493775,
				'node_id' => 'R_kgDOLgtRzw',
				'name' => 'ratio-force',
				'full_name' => 'marsender/ratio-force',
				'private' => true,
				'owner' => [
					'login' => 'marsender',
					'id' => 279979,
					'node_id' => 'MDQ6VXNlcjI3OTk3OQ==',
					'avatar_url' => 'https://avatars.githubusercontent.com/u/279979?v=4',
					'gravatar_id' => '',
					'url' => 'https://api.github.com/users/marsender',
					'html_url' => 'https://github.com/marsender',
					'followers_url' => 'https://api.github.com/users/marsender/followers',
					'following_url' => 'https://api.github.com/users/marsender/following{/other_user}',
					'gists_url' => 'https://api.github.com/users/marsender/gists{/gist_id}',
					'starred_url' => 'https://api.github.com/users/marsender/starred{/owner}{/repo}',
					'subscriptions_url' => 'https://api.github.com/users/marsender/subscriptions',
					'organizations_url' => 'https://api.github.com/users/marsender/orgs',
					'repos_url' => 'https://api.github.com/users/marsender/repos',
					'events_url' => 'https://api.github.com/users/marsender/events{/privacy}',
					'received_events_url' => 'https://api.github.com/users/marsender/received_events',
					'type' => 'User',
					'site_admin' => false,
				],
				'html_url' => 'https://github.com/marsender/ratio-force',
				'description' => null,
				'fork' => false,
				'url' => 'https://api.github.com/repos/marsender/ratio-force',
				'forks_url' => 'https://api.github.com/repos/marsender/ratio-force/forks',
				'keys_url' => 'https://api.github.com/repos/marsender/ratio-force/keys{/key_id}',
				'collaborators_url' => 'https://api.github.com/repos/marsender/ratio-force/collaborators{/collaborator}',
				'teams_url' => 'https://api.github.com/repos/marsender/ratio-force/teams',
				'hooks_url' => 'https://api.github.com/repos/marsender/ratio-force/hooks',
				'issue_events_url' => 'https://api.github.com/repos/marsender/ratio-force/issues/events{/number}',
				'events_url' => 'https://api.github.com/repos/marsender/ratio-force/events',
				'assignees_url' => 'https://api.github.com/repos/marsender/ratio-force/assignees{/user}',
				'branches_url' => 'https://api.github.com/repos/marsender/ratio-force/branches{/branch}',
				'tags_url' => 'https://api.github.com/repos/marsender/ratio-force/tags',
				'blobs_url' => 'https://api.github.com/repos/marsender/ratio-force/git/blobs{/sha}',
				'git_tags_url' => 'https://api.github.com/repos/marsender/ratio-force/git/tags{/sha}',
				'git_refs_url' => 'https://api.github.com/repos/marsender/ratio-force/git/refs{/sha}',
				'trees_url' => 'https://api.github.com/repos/marsender/ratio-force/git/trees{/sha}',
				'statuses_url' => 'https://api.github.com/repos/marsender/ratio-force/statuses/{sha}',
				'languages_url' => 'https://api.github.com/repos/marsender/ratio-force/languages',
				'stargazers_url' => 'https://api.github.com/repos/marsender/ratio-force/stargazers',
				'contributors_url' => 'https://api.github.com/repos/marsender/ratio-force/contributors',
				'subscribers_url' => 'https://api.github.com/repos/marsender/ratio-force/subscribers',
				'subscription_url' => 'https://api.github.com/repos/marsender/ratio-force/subscription',
				'commits_url' => 'https://api.github.com/repos/marsender/ratio-force/commits{/sha}',
				'git_commits_url' => 'https://api.github.com/repos/marsender/ratio-force/git/commits{/sha}',
				'comments_url' => 'https://api.github.com/repos/marsender/ratio-force/comments{/number}',
				'issue_comment_url' => 'https://api.github.com/repos/marsender/ratio-force/issues/comments{/number}',
				'contents_url' => 'https://api.github.com/repos/marsender/ratio-force/contents/{+path}',
				'compare_url' => 'https://api.github.com/repos/marsender/ratio-force/compare/{base}...{head}',
				'merges_url' => 'https://api.github.com/repos/marsender/ratio-force/merges',
				'archive_url' => 'https://api.github.com/repos/marsender/ratio-force/{archive_format}{/ref}',
				'downloads_url' => 'https://api.github.com/repos/marsender/ratio-force/downloads',
				'issues_url' => 'https://api.github.com/repos/marsender/ratio-force/issues{/number}',
				'pulls_url' => 'https://api.github.com/repos/marsender/ratio-force/pulls{/number}',
				'milestones_url' => 'https://api.github.com/repos/marsender/ratio-force/milestones{/number}',
				'notifications_url' => 'https://api.github.com/repos/marsender/ratio-force/notifications{?since,all,participating}',
				'labels_url' => 'https://api.github.com/repos/marsender/ratio-force/labels{/name}',
				'releases_url' => 'https://api.github.com/repos/marsender/ratio-force/releases{/id}',
				'deployments_url' => 'https://api.github.com/repos/marsender/ratio-force/deployments',
				'created_at' => '2024-03-15T09:54:17Z',
				'updated_at' => '2024-03-20T13:35:59Z',
				'pushed_at' => '2024-03-23T20:07:56Z',
				'git_url' => 'git://github.com/marsender/ratio-force.git',
				'ssh_url' => 'git@github.com:marsender/ratio-force.git',
				'clone_url' => 'https://github.com/marsender/ratio-force.git',
				'svn_url' => 'https://github.com/marsender/ratio-force',
				'homepage' => null,
				'size' => 1560,
				'stargazers_count' => 0,
				'watchers_count' => 0,
				'language' => 'PHP',
				'has_issues' => true,
				'has_projects' => true,
				'has_downloads' => true,
				'has_wiki' => false,
				'has_pages' => false,
				'has_discussions' => false,
				'forks_count' => 0,
				'mirror_url' => null,
				'archived' => false,
				'disabled' => false,
				'open_issues_count' => 0,
				'license' => null,
				'allow_forking' => true,
				'is_template' => false,
				'web_commit_signoff_required' => false,
				'topics' => [],
				'visibility' => 'private',
				'forks' => 0,
				'open_issues' => 0,
				'watchers' => 0,
				'default_branch' => 'main',
			],
			'sender' => [
				'login' => 'marsender',
				'id' => 279979,
				'node_id' => 'MDQ6VXNlcjI3OTk3OQ==',
				'avatar_url' => 'https://avatars.githubusercontent.com/u/279979?v=4',
				'gravatar_id' => '',
				'url' => 'https://api.github.com/users/marsender',
				'html_url' => 'https://github.com/marsender',
				'followers_url' => 'https://api.github.com/users/marsender/followers',
				'following_url' => 'https://api.github.com/users/marsender/following{/other_user}',
				'gists_url' => 'https://api.github.com/users/marsender/gists{/gist_id}',
				'starred_url' => 'https://api.github.com/users/marsender/starred{/owner}{/repo}',
				'subscriptions_url' => 'https://api.github.com/users/marsender/subscriptions',
				'organizations_url' => 'https://api.github.com/users/marsender/orgs',
				'repos_url' => 'https://api.github.com/users/marsender/repos',
				'events_url' => 'https://api.github.com/users/marsender/events{/privacy}',
				'received_events_url' => 'https://api.github.com/users/marsender/received_events',
				'type' => 'User',
				'site_admin' => false,
			],
		];
	}
}
