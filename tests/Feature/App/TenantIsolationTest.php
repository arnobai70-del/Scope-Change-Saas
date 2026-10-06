<?php

namespace Tests\Feature\App;

use App\Models\Client;
use App\Models\ProofPack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    public function test_members_cannot_see_another_workspaces_records(): void
    {
        $mine = $this->workspace();
        $theirs = $this->workspace();
        $theirProject = $this->project($theirs);
        $theirRequest = $this->draft($theirProject);
        $theirClient = $theirProject->client;

        $this->actingAs($this->owner($mine));

        $this->get("/app/clients/{$theirClient->id}")->assertNotFound();
        $this->get("/app/projects/{$theirProject->id}")->assertNotFound();
        $this->get("/app/change-requests/{$theirRequest->id}")->assertNotFound();
        $this->get("/app/change-requests/{$theirRequest->id}/preview")->assertNotFound();
        $this->put("/app/clients/{$theirClient->id}", ['type' => 'person', 'name' => 'Hijacked'])->assertNotFound();
        $this->post("/app/change-requests/{$theirRequest->id}/send")->assertNotFound();
        $this->delete("/app/change-requests/{$theirRequest->id}")->assertNotFound();

        $this->assertSame($theirClient->name, $theirClient->refresh()->name);
    }

    public function test_index_pages_only_list_own_records(): void
    {
        $mine = $this->workspace();
        $theirs = $this->workspace();
        Client::factory()->create(['workspace_id' => $mine->id, 'name' => 'My Client']);
        Client::factory()->create(['workspace_id' => $theirs->id, 'name' => 'Their Client']);

        $this->actingAs($this->owner($mine))
            ->get('/app/clients')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('clients/Index')
                ->has('clients.data', 1)
                ->where('clients.data.0.name', 'My Client'));
    }

    public function test_cannot_create_project_for_another_workspaces_client(): void
    {
        $mine = $this->workspace();
        $foreignClient = Client::factory()->create();

        $this->actingAs($this->owner($mine))
            ->post('/app/projects', ['client_id' => $foreignClient->id, 'title' => 'Sneaky', 'currency' => 'USD'])
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseMissing('projects', ['title' => 'Sneaky']);
    }

    public function test_cannot_switch_to_a_workspace_you_do_not_belong_to(): void
    {
        $mine = $this->workspace();
        $theirs = $this->workspace();
        $user = $this->owner($mine);

        $this->actingAs($user)->post("/app/workspaces/{$theirs->id}/switch")->assertNotFound();

        $this->assertSame($mine->id, $user->refresh()->current_workspace_id);
    }

    public function test_proof_pack_downloads_are_tenant_scoped(): void
    {
        $mine = $this->workspace();
        $theirs = $this->workspace();
        [$cr] = $this->sent($this->project($theirs));
        $pack = new ProofPack;
        $pack->forceFill(['change_request_id' => $cr->id, 'workspace_id' => $theirs->id, 'version' => 1, 'status' => 'ready', 'file_path' => 'x.pdf', 'requested_by' => $this->owner($theirs)->id])->save();

        $this->actingAs($this->owner($mine))->get("/app/proof-packs/{$pack->id}/download")->assertNotFound();
    }
}
