<?php

namespace Tests\Feature\App;

use App\Enums\ChangeRequestStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\AuditEvent;
use App\Models\ChangeRequest;
use App\Notifications\ChangeRequestSentToClient;
use App\Services\AuditTrailService;
use App\Services\ChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class ChangeRequestManagementTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(int $projectId, array $extra = []): array
    {
        return $extra + [
            'project_id' => $projectId,
            'title' => 'Extra landing page',
            'description' => 'A campaign landing page.',
            'price' => '750',
            'currency' => 'EUR',
            'timeline_type' => 'days',
            'timeline_value' => 3,
            'payment_rule' => 'none',
            'recipient_name' => 'Casey',
            'recipient_email' => 'Casey@Example.com',
        ];
    }

    public function test_owner_can_create_a_draft_and_send_it(): void
    {
        Notification::fake();
        $workspace = $this->workspace();
        $project = $this->project($workspace);
        $scopeItemId = $project->lockedBaseline()?->items()->firstOrFail()->id;

        $this->actingAs($this->owner($workspace))
            ->post('/app/change-requests', $this->payload($project->id, ['scope_item_ids' => [$scopeItemId]]))
            ->assertRedirect();

        $cr = ChangeRequest::query()->firstOrFail();
        $this->assertSame(ChangeRequestStatus::Draft, $cr->status);
        $this->assertSame('casey@example.com', $cr->recipient_email);
        $this->assertSame(75000, $cr->currentRevision?->price_minor);
        $this->assertSame([$scopeItemId], $cr->currentRevision?->scopeItems()->pluck('scope_items.id')->all());

        $this->post("/app/change-requests/{$cr->id}/send")
            ->assertRedirect("/app/change-requests/{$cr->id}")
            ->assertSessionHas('sent_link');

        $cr->refresh();
        $this->assertSame(ChangeRequestStatus::Sent, $cr->status);
        $this->assertNotNull($cr->currentRevision?->locked_at);
        $this->assertNotNull($cr->currentRevision?->snapshot_hash);
        Notification::assertSentOnDemand(ChangeRequestSentToClient::class);

        $this->get("/app/change-requests/{$cr->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('change-requests/Show')->where('changeRequest.status', 'sent'));
    }

    public function test_approval_tokens_are_stored_hashed(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->assertDatabaseMissing('approval_links', ['token_hash' => $token]);
        $this->assertDatabaseHas('approval_links', ['change_request_id' => $cr->id, 'token_hash' => hash('sha256', $token)]);
    }

    public function test_price_validation_rejects_bad_input(): void
    {
        $workspace = $this->workspace();
        $project = $this->project($workspace);
        $this->actingAs($this->owner($workspace));

        $this->post('/app/change-requests', $this->payload($project->id, ['price' => '-10']))->assertSessionHasErrors('price');
        $this->post('/app/change-requests', $this->payload($project->id, ['price' => '10.5', 'currency' => 'JPY']))->assertSessionHasErrors('price');
        $this->post('/app/change-requests', $this->payload($project->id, ['price' => '0', 'payment_rule' => 'before_start']))->assertSessionHasErrors('price');
        $this->post('/app/change-requests', $this->payload($project->id, ['payment_url' => 'http://insecure.example.com']))->assertSessionHasErrors('payment_url');

        $this->assertSame(0, ChangeRequest::query()->count());
    }

    public function test_sent_revisions_are_immutable(): void
    {
        [$cr] = $this->sent($this->project($this->workspace()));
        $revision = $cr->currentRevision()->firstOrFail();

        $this->expectException(LogicException::class);
        $revision->update(['price_minor' => 1]);
    }

    public function test_editing_a_sent_request_redirects_and_revise_creates_new_revision(): void
    {
        $workspace = $this->workspace();
        [$cr] = $this->sent($this->project($workspace));
        $firstRevisionId = $cr->current_revision_id;
        $this->actingAs($this->owner($workspace));

        $this->get("/app/change-requests/{$cr->id}/edit")->assertRedirect("/app/change-requests/{$cr->id}");

        $this->post("/app/change-requests/{$cr->id}/revise")->assertRedirect("/app/change-requests/{$cr->id}/edit");

        $cr->refresh();
        $this->assertSame(ChangeRequestStatus::Draft, $cr->status);
        $this->assertNotSame($firstRevisionId, $cr->current_revision_id);
        $this->assertSame(2, $cr->currentRevision?->revision_no);
        $this->assertSame(2, $cr->revisions()->count());
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $cr = $this->draft($this->project($this->workspace()));

        $this->expectException(InvalidStateTransition::class);
        app(ChangeRequestService::class)->complete($cr, $this->owner($cr->workspace));
    }

    public function test_only_unsent_drafts_can_be_deleted(): void
    {
        $workspace = $this->workspace();
        [$sent] = $this->sent($this->project($workspace));
        $this->actingAs($this->owner($workspace));

        $this->delete("/app/change-requests/{$sent->id}")->assertStatus(409);
        $this->assertModelExists($sent);
    }

    public function test_audit_trail_is_hash_chained_and_detects_tampering(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        $this->get($this->clientUrl($cr, $token));
        $audit = app(AuditTrailService::class);

        $this->assertGreaterThanOrEqual(3, AuditEvent::query()->where('entity_id', $cr->id)->where('entity_type', $cr->getMorphClass())->count());
        $this->assertTrue($audit->verifyChain($cr));

        DB::table('audit_events')->where('entity_type', $cr->getMorphClass())->where('entity_id', $cr->id)->orderBy('id')->limit(1)
            ->update(['metadata_json' => json_encode(['reference' => 'forged'])]);

        $this->assertFalse($audit->verifyChain($cr));
    }

    public function test_audit_events_cannot_be_updated_through_the_model(): void
    {
        $cr = $this->draft($this->project($this->workspace()));
        $event = AuditEvent::query()->where('entity_id', $cr->id)->firstOrFail();

        $this->expectException(LogicException::class);
        $event->update(['event' => 'tampered']);
    }

    public function test_expiry_command_expires_overdue_requests(): void
    {
        [$cr] = $this->sent($this->project($this->workspace()));
        $this->travel(30)->days();

        $this->artisan('change-requests:expire')->assertSuccessful();

        $this->assertSame(ChangeRequestStatus::Expired, $cr->refresh()->status);
    }

    public function test_full_lifecycle_to_completion(): void
    {
        $workspace = $this->workspace();
        [$cr, $token] = $this->sent($this->project($workspace));
        $this->post($this->clientUrl($cr, $token, '/approve'), [
            'revision_id' => $cr->current_revision_id,
            'client_name' => 'Casey',
            'client_email' => 'casey@example.com',
            'accept_terms' => '1',
        ]);
        $this->actingAs($this->owner($workspace));

        $this->post("/app/change-requests/{$cr->id}/start")->assertRedirect();
        $this->assertSame(ChangeRequestStatus::InProgress, $cr->refresh()->status);

        $this->post("/app/change-requests/{$cr->id}/complete")->assertRedirect();
        $this->assertSame(ChangeRequestStatus::Completed, $cr->refresh()->status);
    }

    public function test_main_pages_render(): void
    {
        $workspace = $this->workspace();
        $project = $this->project($workspace);
        [$cr] = $this->sent($project);
        $this->actingAs($this->owner($workspace));

        foreach ([
            '/app/dashboard' => 'Dashboard',
            '/app/clients' => 'clients/Index',
            "/app/clients/{$project->client_id}" => 'clients/Show',
            '/app/projects' => 'projects/Index',
            "/app/projects/{$project->id}" => 'projects/Show',
            '/app/change-requests' => 'change-requests/Index',
            '/app/change-requests/create' => 'change-requests/Edit',
            "/app/change-requests/{$cr->id}" => 'change-requests/Show',
            '/app/proof-packs' => 'proof-packs/Index',
            '/app/templates' => 'templates/Index',
            '/app/reports' => 'reports/Index',
            '/app/notifications' => 'notifications/Index',
            '/app/team' => 'team/Index',
            '/app/billing' => 'billing/Index',
            '/app/settings/workspace' => 'settings/Workspace',
            '/app/settings/notifications' => 'settings/Notifications',
            '/app/settings/data' => 'settings/Data',
        ] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component($component));
        }

        $this->get("/app/change-requests/{$cr->id}/preview")->assertOk()->assertSee('Preview');
    }
}
