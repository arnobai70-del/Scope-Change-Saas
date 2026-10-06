<?php

namespace Tests\Feature\App;

use App\Enums\ChangeRequestStatus;
use App\Models\ProofPack;
use App\Services\ProofPackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class ProofPackTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    public function test_generates_a_downloadable_pdf_from_locked_snapshots(): void
    {
        Storage::fake(ProofPackService::DISK);
        $workspace = $this->workspace();
        [$cr, $token] = $this->sent($this->project($workspace));
        $this->post($this->clientUrl($cr, $token, '/approve'), [
            'revision_id' => $cr->current_revision_id,
            'client_name' => 'Casey',
            'client_email' => 'casey@example.com',
            'accept_terms' => '1',
        ]);
        $this->actingAs($this->owner($workspace));

        $this->post("/app/change-requests/{$cr->id}/proof-pack")->assertRedirect();

        $pack = ProofPack::query()->firstOrFail();
        $this->assertSame('ready', $pack->status);
        $this->assertNotNull($pack->checksum);
        Storage::disk(ProofPackService::DISK)->assertExists((string) $pack->file_path);
        $this->assertStringStartsWith('%PDF', (string) Storage::disk(ProofPackService::DISK)->get((string) $pack->file_path));

        $this->get("/app/proof-packs/{$pack->id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_content_hash_is_stable_and_completed_requests_become_proof_packed(): void
    {
        Storage::fake(ProofPackService::DISK);
        $workspace = $this->workspace();
        [$cr, $token] = $this->sent($this->project($workspace));
        $this->post($this->clientUrl($cr, $token, '/approve'), [
            'revision_id' => $cr->current_revision_id,
            'client_name' => 'Casey',
            'client_email' => 'casey@example.com',
            'accept_terms' => '1',
        ]);
        $owner = $this->owner($workspace);
        $this->actingAs($owner)->post("/app/change-requests/{$cr->id}/complete");

        $service = app(ProofPackService::class);
        $first = $service->request($cr->refresh(), $owner)->refresh();
        $this->assertSame(ChangeRequestStatus::ProofPacked, $cr->refresh()->status);

        $second = $service->request($cr, $owner)->refresh();

        $this->assertSame(2, $second->version);
        $this->assertSame($first->content_hash, $second->content_hash);
    }

    public function test_drafts_cannot_be_packed(): void
    {
        $workspace = $this->workspace();
        $cr = $this->draft($this->project($workspace));

        $this->actingAs($this->owner($workspace))
            ->post("/app/change-requests/{$cr->id}/proof-pack")
            ->assertSessionHasErrors('proof_pack');
    }
}
