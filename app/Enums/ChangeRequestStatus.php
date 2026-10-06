<?php

namespace App\Enums;

/**
 * Lifecycle of a change request. Allowed transitions are the single source
 * of truth for every state change; see docs/state-machine.md.
 */
enum ChangeRequestStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Questioned = 'questioned';
    case Declined = 'declined';
    case Approved = 'approved';
    case PaymentPending = 'payment_pending';
    case PaymentMarkedSent = 'payment_marked_sent';
    case PaymentConfirmed = 'payment_confirmed';
    case ReadyToStart = 'ready_to_start';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case ProofPacked = 'proof_packed';
    case Expired = 'expired';
    case Revoked = 'revoked';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Viewed, self::Questioned, self::Approved, self::Declined, self::Expired, self::Revoked, self::Draft],
            self::Viewed => [self::Questioned, self::Approved, self::Declined, self::Expired, self::Revoked, self::Draft],
            self::Questioned => [self::Sent, self::Viewed, self::Approved, self::Declined, self::Expired, self::Revoked, self::Draft],
            self::Declined => [self::Draft],
            self::Approved => [self::PaymentPending, self::ReadyToStart, self::Draft],
            self::PaymentPending => [self::PaymentMarkedSent, self::PaymentConfirmed, self::Draft],
            self::PaymentMarkedSent => [self::PaymentConfirmed, self::PaymentPending],
            self::PaymentConfirmed => [self::ReadyToStart],
            self::ReadyToStart => [self::InProgress, self::Completed],
            self::InProgress => [self::Completed],
            self::Completed => [self::ProofPacked],
            self::ProofPacked => [],
            self::Expired => [self::Sent, self::Draft],
            self::Revoked => [self::Sent, self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Statuses in which the client can still decide.
     */
    public function awaitingClient(): bool
    {
        return in_array($this, [self::Sent, self::Viewed, self::Questioned], true);
    }

    /**
     * Statuses that count as an approved commercial change.
     */
    public function isApprovedFamily(): bool
    {
        return in_array($this, [
            self::Approved,
            self::PaymentPending,
            self::PaymentMarkedSent,
            self::PaymentConfirmed,
            self::ReadyToStart,
            self::InProgress,
            self::Completed,
            self::ProofPacked,
        ], true);
    }

    /**
     * Whether the owner may start a new revision from this status.
     */
    public function canRevise(): bool
    {
        return $this->canTransitionTo(self::Draft);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Questioned => 'Question asked',
            self::Declined => 'Declined',
            self::Approved => 'Approved',
            self::PaymentPending => 'Payment pending',
            self::PaymentMarkedSent => 'Payment marked sent',
            self::PaymentConfirmed => 'Payment confirmed',
            self::ReadyToStart => 'Ready to start',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::ProofPacked => 'Proof packed',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    /**
     * @return list<string>
     */
    public static function awaitingClientValues(): array
    {
        return [self::Sent->value, self::Viewed->value, self::Questioned->value];
    }

    /**
     * @return list<string>
     */
    public static function approvedFamilyValues(): array
    {
        return array_values(array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => $s->isApprovedFamily()),
        ));
    }
}
