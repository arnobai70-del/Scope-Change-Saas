<?php

namespace Tests\Concerns;

use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ApprovalLinkService;
use App\Services\ChangeRequestService;
use App\Services\ScopeService;
use App\Support\TenantContext;

trait BuildsWorkspaces
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function workspace(array $attributes = []): Workspace
    {
        return Workspace::factory()->create($attributes);
    }

    protected function owner(Workspace $workspace): User
    {
        return $workspace->owner()->firstOrFail();
    }

    protected function project(Workspace $workspace, bool $lockedScope = true): Project
    {
        $client = Client::factory()->create(['workspace_id' => $workspace->id, 'email' => 'client@example.com', 'name' => 'Casey Client']);
        $project = Project::factory()->create(['client_id' => $client->id]);

        $scope = app(ScopeService::class);
        $scope->save($project, [
            'title' => 'Website scope',
            'summary' => 'Five page website',
            'items' => [
                ['type' => 'deliverable', 'title' => 'Five static pages'],
                ['type' => 'exclusion', 'title' => 'Blog and CMS'],
            ],
        ], $this->owner($workspace));

        if ($lockedScope) {
            $scope->lock($project, $this->owner($workspace));
        }

        return $project->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function draft(Project $project, array $overrides = []): ChangeRequest
    {
        $this->actAsTenant($project->workspace);

        return app(ChangeRequestService::class)->createDraft($project, $overrides + [
            'title' => 'Add a blog section',
            'description' => 'Blog index, post template and CMS setup.',
            'scope_reason' => 'Blog and CMS were excluded.',
            'price' => '1200.00',
            'currency' => 'USD',
            'timeline_type' => 'days',
            'timeline_value' => 5,
            'payment_rule' => 'none',
        ], $this->owner($project->workspace));
    }

    /**
     * Create, send and return the change request with its raw approval token.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{0: ChangeRequest, 1: string}
     */
    protected function sent(Project $project, array $overrides = []): array
    {
        $changeRequest = $this->draft($project, $overrides);
        $result = app(ChangeRequestService::class)->send($changeRequest, $this->owner($project->workspace), false);

        return [$changeRequest->refresh(), $this->tokenFrom($result['url'])];
    }

    protected function tokenFrom(string $url): string
    {
        return (string) last(explode('/', rtrim((string) parse_url($url, PHP_URL_PATH), '/')));
    }

    protected function clientUrl(ChangeRequest $changeRequest, string $token, string $suffix = ''): string
    {
        return '/c/'.$changeRequest->public_id.'/'.$token.$suffix;
    }

    protected function actAsTenant(Workspace $workspace): void
    {
        app(TenantContext::class)->set($workspace);
    }

    protected function linkService(): ApprovalLinkService
    {
        return app(ApprovalLinkService::class);
    }
}
