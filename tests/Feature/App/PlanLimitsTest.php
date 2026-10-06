<?php

namespace Tests\Feature\App;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    public function test_free_plan_allows_one_active_project(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => null]);
        $client = Client::factory()->create(['workspace_id' => $workspace->id]);
        $this->actingAs($this->owner($workspace));

        $this->post('/app/projects', ['client_id' => $client->id, 'title' => 'First', 'currency' => 'USD'])->assertSessionHasNoErrors();
        $this->post('/app/projects', ['client_id' => $client->id, 'title' => 'Second', 'currency' => 'USD'])
            ->assertSessionHasErrors('plan')
            ->assertSessionHas('upgrade', 'active_projects');

        $this->assertSame(1, Project::query()->where('workspace_id', $workspace->id)->count());
    }

    public function test_free_plan_limits_monthly_change_requests_even_after_deletion(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => null]);
        $project = $this->project($workspace);
        $this->actingAs($this->owner($workspace));

        for ($i = 0; $i < 3; $i++) {
            $this->draft($project)->delete();
        }

        $this->post('/app/change-requests', [
            'project_id' => $project->id,
            'title' => 'Fourth',
            'description' => 'Over the limit',
            'price' => '10',
            'currency' => 'USD',
            'timeline_type' => 'none',
            'payment_rule' => 'none',
        ])->assertSessionHasErrors('plan');
    }

    public function test_proof_packs_require_a_paid_plan(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => null]);
        [$cr] = $this->sent($this->project($workspace));

        $this->actingAs($this->owner($workspace))
            ->post("/app/change-requests/{$cr->id}/proof-pack")
            ->assertSessionHasErrors('plan');

        $this->assertSame(0, $cr->proofPacks()->count());
    }

    public function test_trial_unlocks_pro_features(): void
    {
        $workspace = $this->workspace(['trial_ends_at' => now()->addDays(5)]);

        $this->actingAs($this->owner($workspace))
            ->get('/app/billing')
            ->assertInertia(fn ($page) => $page->where('plan', 'pro')->where('usage.active_projects.limit', null));
    }
}
