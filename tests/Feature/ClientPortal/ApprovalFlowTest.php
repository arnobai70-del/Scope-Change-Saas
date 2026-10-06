<?php

namespace Tests\Feature\ClientPortal;

use App\Enums\ChangeRequestStatus;
use App\Models\ClientDecision;
use App\Notifications\ClientDecided;
use App\Services\ChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function decision(int $revisionId, array $extra = []): array
    {
        return $extra + [
            'revision_id' => $revisionId,
            'client_name' => 'Casey Client',
            'client_email' => 'casey@example.com',
            'accept_terms' => '1',
        ];
    }

    public function test_client_can_view_request_without_logging_in(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->get($this->clientUrl($cr, $token))
            ->assertOk()
            ->assertSee('Add a blog section')
            ->assertSee('$1,200.00')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->assertSame(ChangeRequestStatus::Viewed, $cr->refresh()->status);
        $this->assertNotNull($cr->first_viewed_at);
    }

    public function test_wrong_token_returns_not_found(): void
    {
        [$cr] = $this->sent($this->project($this->workspace()));

        $this->get($this->clientUrl($cr, str_repeat('x', 48)))->assertNotFound();
    }

    public function test_client_can_approve_and_sees_receipt(): void
    {
        Notification::fake();
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id))
            ->assertRedirect($this->clientUrl($cr, $token, '/receipt'));

        $cr->refresh();
        // No payment condition: an approval is immediately ready to start.
        $this->assertSame(ChangeRequestStatus::ReadyToStart, $cr->status);
        $this->assertDatabaseHas('client_decisions', ['change_request_id' => $cr->id, 'decision' => 'approved', 'client_email' => 'casey@example.com']);

        $this->get($this->clientUrl($cr, $token, '/receipt'))->assertOk()->assertSee('Approved');
        Notification::assertSentTo($this->owner($cr->workspace), ClientDecided::class);
    }

    public function test_approval_requires_explicit_acceptance(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id, ['accept_terms' => '0']))
            ->assertSessionHasErrors('accept_terms');

        $this->assertSame(ChangeRequestStatus::Sent, $cr->refresh()->status);
    }

    public function test_double_submission_records_a_single_decision(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        $payload = $this->decision($cr->current_revision_id);

        $this->post($this->clientUrl($cr, $token, '/approve'), $payload);
        $this->post($this->clientUrl($cr, $token, '/approve'), $payload);
        $this->post($this->clientUrl($cr, $token, '/decline'), $payload);

        $this->assertSame(1, ClientDecision::query()->where('change_request_id', $cr->id)->count());
        $this->assertTrue($cr->refresh()->status->isApprovedFamily());
    }

    public function test_client_can_decline_with_reason(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/decline'), $this->decision($cr->current_revision_id, ['reason' => 'Not this quarter']));

        $this->assertSame(ChangeRequestStatus::Declined, $cr->refresh()->status);
        $this->assertDatabaseHas('client_decisions', ['change_request_id' => $cr->id, 'decision' => 'declined', 'reason' => 'Not this quarter']);
    }

    public function test_expired_link_cannot_be_used(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        $this->travel(30)->days();

        $this->get($this->clientUrl($cr, $token))->assertStatus(410);
        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id));

        $this->assertDatabaseMissing('client_decisions', ['change_request_id' => $cr->id]);
    }

    public function test_revoked_link_cannot_be_used(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        app(ChangeRequestService::class)->revoke($cr, $this->owner($cr->workspace));

        $this->get($this->clientUrl($cr, $token))->assertStatus(410);
        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id))->assertStatus(410);

        $this->assertDatabaseMissing('client_decisions', ['change_request_id' => $cr->id]);
    }

    public function test_link_for_a_superseded_revision_cannot_approve(): void
    {
        $project = $this->project($this->workspace());
        [$cr, $oldToken] = $this->sent($project);
        $oldRevisionId = $cr->current_revision_id;
        $service = app(ChangeRequestService::class);
        $owner = $this->owner($cr->workspace);

        $service->revise($cr, $owner);
        $service->updateDraft($cr->refresh(), [
            'title' => 'Add a blog section',
            'description' => 'Now with comments',
            'price' => '1500.00',
            'currency' => 'USD',
            'timeline_type' => 'none',
            'payment_rule' => 'none',
        ], $owner);
        $result = $service->send($cr->refresh(), $owner, false);

        $this->get($this->clientUrl($cr, $oldToken))->assertStatus(410);
        $this->post($this->clientUrl($cr, $oldToken, '/approve'), $this->decision($oldRevisionId));
        $this->assertDatabaseMissing('client_decisions', ['change_request_id' => $cr->id]);

        $newToken = $this->tokenFrom($result['url']);
        $this->get($this->clientUrl($cr, $newToken))->assertOk()->assertSee('$1,500.00');
    }

    public function test_decision_with_mismatched_revision_is_rejected(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id + 999));

        $this->assertDatabaseMissing('client_decisions', ['change_request_id' => $cr->id]);
    }

    public function test_client_question_pauses_request_and_is_visible(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/comment'), [
            'client_name' => 'Casey',
            'client_email' => 'casey@example.com',
            'body' => 'Does this include <b>hosting</b>?',
        ])->assertRedirect();

        $this->assertSame(ChangeRequestStatus::Questioned, $cr->refresh()->status);
        $this->assertDatabaseHas('change_comments', ['change_request_id' => $cr->id, 'body' => 'Does this include hosting?']);
    }

    public function test_honeypot_blocks_bots(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));

        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id, ['website' => 'spam']))
            ->assertSessionHasErrors('website');
    }

    public function test_payment_before_start_flow(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()), [
            'payment_rule' => 'before_start',
            'payment_url' => 'https://pay.example.com/abc',
        ]);

        $this->post($this->clientUrl($cr, $token, '/approve'), $this->decision($cr->current_revision_id));
        $this->assertSame(ChangeRequestStatus::PaymentPending, $cr->refresh()->status);

        $this->get($this->clientUrl($cr, $token, '/pay'))->assertRedirect('https://pay.example.com/abc');

        $this->post($this->clientUrl($cr, $token, '/payment-sent'), ['reference' => 'TX-1']);
        $this->assertSame(ChangeRequestStatus::PaymentMarkedSent, $cr->refresh()->status);

        app(ChangeRequestService::class)->confirmPayment($cr, $this->owner($cr->workspace), 'TX-1');
        $this->assertSame(ChangeRequestStatus::ReadyToStart, $cr->refresh()->status);
    }

    public function test_internal_notes_are_never_shown_to_the_client(): void
    {
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        app(ChangeRequestService::class)->ownerComment($cr, $this->owner($cr->workspace), 'Secret margin note', true);
        app(ChangeRequestService::class)->ownerComment($cr, $this->owner($cr->workspace), 'Happy to help', false);

        $this->get($this->clientUrl($cr, $token))
            ->assertOk()
            ->assertSee('Happy to help')
            ->assertDontSee('Secret margin note');
    }
}
