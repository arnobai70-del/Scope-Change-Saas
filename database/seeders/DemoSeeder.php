<?php

namespace Database\Seeders;

use App\Enums\ScopeItemType;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\ChangeRequestService;
use App\Services\ScopeService;
use App\Services\WorkspaceService;
use Illuminate\Database\Seeder;

/**
 * Local demo data: demo@example.com / password (owner) and
 * admin@example.com / password (platform admin). Never run in production.
 */
class DemoSeeder extends Seeder
{
    public function run(WorkspaceService $workspaces, ScopeService $scope, ChangeRequestService $changes): void
    {
        if (app()->isProduction()) {
            return;
        }

        $user = User::query()->firstOrCreate(['email' => 'demo@example.com'], [
            'name' => 'Demo Owner',
            'password' => 'password',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $admin = User::query()->firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Platform Admin',
            'password' => 'password',
        ]);
        $admin->forceFill(['email_verified_at' => now(), 'is_admin' => true])->save();

        if ($user->workspaces()->exists()) {
            return;
        }

        $workspace = $workspaces->createFor($user, 'Northwind Studio');
        $workspace->forceFill(['onboarded_at' => now(), 'currency' => 'USD', 'default_payment_url' => 'https://example.com/pay'])->save();
        $workspaces->createFor($admin, 'Admin workspace')->forceFill(['onboarded_at' => now()])->save();

        $client = new Client(['type' => 'company', 'name' => 'Alex Morgan', 'company' => 'Acme Coffee', 'email' => 'alex@example.com']);
        $client->workspace_id = $workspace->id;
        $client->save();
        $client->contacts()->create(['name' => 'Alex Morgan', 'email' => 'alex@example.com', 'is_primary' => true]);

        $project = new Project(['client_id' => $client->id, 'title' => 'Acme Coffee website', 'currency' => 'USD', 'base_amount_minor' => 480000, 'revision_allowance' => 2]);
        $project->workspace_id = $workspace->id;
        $project->save();

        $scope->save($project, [
            'title' => 'Website redesign — agreed scope',
            'summary' => 'Five-page marketing site on the existing CMS.',
            'items' => [
                ['type' => ScopeItemType::Deliverable->value, 'title' => 'Home, About, Menu, Locations and Contact pages'],
                ['type' => ScopeItemType::Deliverable->value, 'title' => 'Responsive design for mobile and desktop'],
                ['type' => ScopeItemType::Revision->value, 'title' => 'Two rounds of revisions per page'],
                ['type' => ScopeItemType::Exclusion->value, 'title' => 'Online ordering or e-commerce'],
                ['type' => ScopeItemType::Assumption->value, 'title' => 'Client supplies all copy and photography'],
            ],
        ], $user);
        $scope->lock($project, $user);

        $changes->createDraft($project, [
            'title' => 'Add an online ordering page',
            'description' => 'Add a page where customers can pre-order coffee for pickup, using the client\'s existing Square account.',
            'scope_reason' => 'Online ordering was listed as an exclusion in the agreed scope.',
            'price_minor' => 95000,
            'currency' => 'USD',
            'timeline_json' => ['type' => 'days', 'value' => 5, 'note' => null],
            'payment_rule' => 'before_start',
            'payment_url' => 'https://example.com/pay',
        ], $user);
    }
}
