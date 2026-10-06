<?php

namespace Tests\Unit;

use App\Enums\ChangeRequestStatus;
use PHPUnit\Framework\TestCase;

class ChangeRequestStatusTest extends TestCase
{
    public function test_drafts_can_only_be_sent(): void
    {
        $this->assertTrue(ChangeRequestStatus::Draft->canTransitionTo(ChangeRequestStatus::Sent));
        $this->assertFalse(ChangeRequestStatus::Draft->canTransitionTo(ChangeRequestStatus::Approved));
    }

    public function test_terminal_states_cannot_be_reopened_to_approval(): void
    {
        $this->assertFalse(ChangeRequestStatus::ProofPacked->canTransitionTo(ChangeRequestStatus::Approved));
        $this->assertSame([], ChangeRequestStatus::ProofPacked->allowedTransitions());
        $this->assertFalse(ChangeRequestStatus::Declined->canTransitionTo(ChangeRequestStatus::Approved));
        $this->assertFalse(ChangeRequestStatus::Completed->canTransitionTo(ChangeRequestStatus::InProgress));
    }

    public function test_payment_must_be_confirmed_before_work_starts(): void
    {
        $this->assertFalse(ChangeRequestStatus::PaymentPending->canTransitionTo(ChangeRequestStatus::ReadyToStart));
        $this->assertFalse(ChangeRequestStatus::PaymentMarkedSent->canTransitionTo(ChangeRequestStatus::InProgress));
        $this->assertTrue(ChangeRequestStatus::PaymentConfirmed->canTransitionTo(ChangeRequestStatus::ReadyToStart));
    }

    public function test_every_transition_target_is_a_valid_case(): void
    {
        foreach (ChangeRequestStatus::cases() as $status) {
            foreach ($status->allowedTransitions() as $target) {
                $this->assertInstanceOf(ChangeRequestStatus::class, $target);
                $this->assertNotSame($status, $target, "{$status->value} must not transition to itself");
            }
        }
    }

    public function test_awaiting_client_states(): void
    {
        $this->assertTrue(ChangeRequestStatus::Sent->awaitingClient());
        $this->assertTrue(ChangeRequestStatus::Viewed->awaitingClient());
        $this->assertTrue(ChangeRequestStatus::Questioned->awaitingClient());
        $this->assertFalse(ChangeRequestStatus::Approved->awaitingClient());
        $this->assertFalse(ChangeRequestStatus::Draft->awaitingClient());
    }
}
