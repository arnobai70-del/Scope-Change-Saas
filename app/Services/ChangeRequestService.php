<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\ChangeRequestStatus;
use App\Enums\ClientDecisionType;
use App\Enums\PaymentRule;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\ApprovalLink;
use App\Models\ChangeComment;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestRevision;
use App\Models\ClientDecision;
use App\Models\PaymentRecord;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ChangeRequestExpired;
use App\Notifications\ChangeRequestSentToClient;
use App\Notifications\ClientAskedQuestion;
use App\Notifications\ClientDecided;
use App\Notifications\ClientViewedRequest;
use App\Notifications\OwnerRepliedToClient;
use App\Notifications\PaymentConfirmedToClient;
use App\Notifications\PaymentMarkedSent;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All change request business rules live here. Controllers stay thin and
 * every status change goes through transition(), which enforces the state
 * machine and writes the audit trail.
 */
class ChangeRequestService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly ApprovalLinkService $links,
        private readonly UsageLimitService $usage,
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated revision data
     */
    public function createDraft(Project $project, array $data, User $user): ChangeRequest
    {
        /** @var Workspace $workspace */
        $workspace = $project->workspace;

        $this->usage->ensureCanCreateChangeRequest($workspace);

        $changeRequest = DB::transaction(function () use ($project, $workspace, $data, $user) {
            /** @var Workspace $locked */
            $locked = Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            $sequence = $locked->change_request_sequence + 1;
            $locked->forceFill(['change_request_sequence' => $sequence])->save();

            $recipient = $project->client->recipient();

            $changeRequest = new ChangeRequest([
                'project_id' => $project->id,
                'recipient_name' => $data['recipient_name'] ?? $recipient['name'],
                'recipient_email' => $data['recipient_email'] ?? $recipient['email'],
            ]);
            $changeRequest->workspace_id = $workspace->id;
            $changeRequest->created_by = $user->id;
            $changeRequest->public_id = (string) Str::ulid();
            $changeRequest->reference = sprintf('CR-%s-%06d', Carbon::now('UTC')->format('Y'), $sequence);
            $changeRequest->status = ChangeRequestStatus::Draft;
            $changeRequest->save();

            $revision = $this->createRevision($changeRequest, 1, $data, $user);
            $changeRequest->forceFill(['current_revision_id' => $revision->id])->save();

            $this->usage->recordChangeRequestCreated($workspace);

            $this->audit->record($changeRequest, 'change_request.created', ActorType::User, $user, [
                'reference' => $changeRequest->reference,
                'revision_no' => 1,
            ]);

            return $changeRequest;
        });

        $this->analytics->track('change_request_created', $workspace->id, $user->id, [
            'change_request_id' => $changeRequest->id,
            'currency' => $data['currency'] ?? null,
        ]);

        return $changeRequest;
    }

    /**
     * Edit the current revision in place. Only possible while it has never
     * been sent: locked revisions are immutable.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(ChangeRequest $changeRequest, array $data, User $user): ChangeRequest
    {
        $revision = $this->requireCurrentRevision($changeRequest);

        if ($changeRequest->status !== ChangeRequestStatus::Draft || $revision->isLocked()) {
            throw ValidationException::withMessages([
                'status' => 'Only unsent drafts can be edited. Start a new revision to change a sent request.',
            ]);
        }

        DB::transaction(function () use ($changeRequest, $revision, $data, $user) {
            $revision->fill($this->revisionAttributes($data))->save();
            $this->syncScopeItems($revision, $data);

            $changeRequest->fill(array_filter([
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
            ], fn ($v) => $v !== null))->save();

            $this->audit->record($changeRequest, 'change_request.draft_updated', ActorType::User, $user, [
                'revision_no' => $revision->revision_no,
            ]);
        });

        return $changeRequest->refresh();
    }

    /**
     * Start revision N+1 from the current revision. The previous revision,
     * its decision and any approval stay untouched.
     */
    public function revise(ChangeRequest $changeRequest, User $user): ChangeRequest
    {
        $current = $this->requireCurrentRevision($changeRequest);

        if ($changeRequest->status === ChangeRequestStatus::Draft && ! $current->isLocked()) {
            return $changeRequest;
        }

        return DB::transaction(function () use ($changeRequest, $current, $user) {
            $this->links->revokeAll($changeRequest);

            $data = [
                'title' => $current->title,
                'description' => $current->description,
                'scope_reason' => $current->scope_reason,
                'scope_excerpt' => $current->scope_excerpt,
                'price_minor' => $current->price_minor,
                'currency' => $current->currency,
                'timeline_json' => $current->timeline_json,
                'payment_rule' => $current->payment_rule->value,
                'payment_url' => $current->payment_url,
                'payment_instructions' => $current->payment_instructions,
                'terms_note' => $current->terms_note,
                'scope_item_ids' => $current->scopeItems()->pluck('scope_items.id')->all(),
            ];

            $revision = $this->createRevision($changeRequest, $current->revision_no + 1, $data, $user);
            $changeRequest->forceFill(['current_revision_id' => $revision->id]);

            $this->transition($changeRequest, ChangeRequestStatus::Draft, ActorType::User, $user, 'change_request.revision_started', [
                'revision_no' => $revision->revision_no,
                'previous_revision_no' => $current->revision_no,
            ]);

            return $changeRequest;
        });
    }

    /**
     * Lock the current revision and issue a fresh client link. Works for
     * the first send and for reissuing after expiry or revocation.
     *
     * @return array{url: string, link: ApprovalLink}
     */
    public function send(ChangeRequest $changeRequest, User $user, bool $notifyClient = true): array
    {
        $revision = $this->requireCurrentRevision($changeRequest);

        if (! $changeRequest->status->canTransitionTo(ChangeRequestStatus::Sent)) {
            throw new InvalidStateTransition($changeRequest->status, ChangeRequestStatus::Sent);
        }

        if ($revision->payment_rule->requiresPayment() && $revision->price_minor <= 0) {
            throw ValidationException::withMessages(['price' => 'A paid change needs a price greater than zero.']);
        }

        if ($notifyClient && blank($changeRequest->recipient_email)) {
            throw ValidationException::withMessages(['recipient_email' => 'Add a client email address, or copy the link instead.']);
        }

        $result = DB::transaction(function () use ($changeRequest, $revision, $user) {
            if (! $revision->isLocked()) {
                $this->lockRevision($changeRequest, $revision);
            }

            /** @var Workspace $workspace */
            $workspace = $changeRequest->workspace;
            $expiresAt = Carbon::now()->addDays(max(1, $workspace->default_expiry_days));
            $wasReissue = $changeRequest->sent_at !== null;

            $issued = $this->links->issue($changeRequest, $revision, $expiresAt);

            $changeRequest->forceFill([
                'expires_at' => $expiresAt,
                'sent_at' => Carbon::now(),
                'reminders_muted_at' => null,
            ]);
            $changeRequest->reminderLogs()->delete();

            $this->transition($changeRequest, ChangeRequestStatus::Sent, ActorType::User, $user, $wasReissue ? 'change_request.reissued' : 'change_request.sent', [
                'revision_no' => $revision->revision_no,
                'snapshot_hash' => $revision->snapshot_hash,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return $issued;
        });

        if ($notifyClient && $changeRequest->recipient_email) {
            Notification::route('mail', [$changeRequest->recipient_email => $changeRequest->recipient_name])
                ->notify(new ChangeRequestSentToClient($changeRequest, $result['url']));
        }

        $this->analytics->track('change_request_sent', $changeRequest->workspace_id, $user->id, [
            'change_request_id' => $changeRequest->id,
            'revision_no' => $revision->revision_no,
            'price_minor' => $revision->price_minor,
            'currency' => $revision->currency,
            'payment_rule' => $revision->payment_rule->value,
        ]);

        return ['url' => $result['url'], 'link' => $result['link']];
    }

    public function revoke(ChangeRequest $changeRequest, User $user): ChangeRequest
    {
        DB::transaction(function () use ($changeRequest, $user) {
            $this->links->revokeAll($changeRequest);
            $this->transition($changeRequest, ChangeRequestStatus::Revoked, ActorType::User, $user, 'change_request.revoked');
        });

        return $changeRequest;
    }

    /**
     * Record that the client opened the page. Owner notifications are
     * debounced so refreshes do not spam the inbox.
     */
    public function recordView(ApprovalLink $link): void
    {
        $changeRequest = $link->changeRequest;
        $now = Carbon::now();
        $shouldNotify = $changeRequest->last_viewed_at === null || $changeRequest->last_viewed_at->lt($now->copy()->subHour());

        DB::transaction(function () use ($changeRequest, $link, $now, $shouldNotify) {
            $link->forceFill(['last_viewed_at' => $now])->save();
            $changeRequest->forceFill([
                'first_viewed_at' => $changeRequest->first_viewed_at ?? $now,
                'last_viewed_at' => $now,
            ]);

            if ($changeRequest->status === ChangeRequestStatus::Sent) {
                $this->transition($changeRequest, ChangeRequestStatus::Viewed, ActorType::Client, $changeRequest->recipient_email, 'client.viewed');
            } else {
                $changeRequest->save();

                if ($shouldNotify) {
                    $this->audit->record($changeRequest, 'client.viewed', ActorType::Client, $changeRequest->recipient_email);
                }
            }
        });

        if ($shouldNotify) {
            Notification::send($this->ownerRecipients($changeRequest), new ClientViewedRequest($changeRequest));
            $this->analytics->track('client_link_viewed', $changeRequest->workspace_id, null, ['change_request_id' => $changeRequest->id]);
        }
    }

    /**
     * Record the client's approve / decline decision. Idempotent: a repeat
     * submission for the same revision returns the existing decision.
     *
     * @param  array{client_name: string, client_email: string, reason?: string|null, ip?: string|null, user_agent?: string|null, accepted_terms?: bool}  $input
     */
    public function decide(ApprovalLink $link, int $revisionId, ClientDecisionType $type, array $input): ClientDecision
    {
        $created = false;

        $decision = DB::transaction(function () use ($link, $revisionId, $type, $input, &$created) {
            /** @var ChangeRequest $changeRequest */
            $changeRequest = ChangeRequest::query()->whereKey($link->change_request_id)->lockForUpdate()->firstOrFail();

            $existing = ClientDecision::query()->where('revision_id', $revisionId)
                ->where('change_request_id', $changeRequest->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $link->refresh();

            if (! $link->isUsable() || ! $changeRequest->status->awaitingClient() || $changeRequest->isExpiredByDate()) {
                throw ValidationException::withMessages(['decision' => 'This approval link is no longer active. Ask the sender for a new link.']);
            }

            if ($revisionId !== $changeRequest->current_revision_id || $revisionId !== $link->revision_id) {
                throw ValidationException::withMessages(['decision' => 'This request was updated after you opened it. Please review the latest version.']);
            }

            $revision = $this->requireCurrentRevision($changeRequest);
            $recordIp = (bool) config('security.record_client_ip', true);

            /** @var ClientDecision $decision */
            $decision = ClientDecision::query()->create([
                'change_request_id' => $changeRequest->id,
                'revision_id' => $revision->id,
                'decision' => $type,
                'client_name' => $input['client_name'],
                'client_email' => mb_strtolower($input['client_email']),
                'accepted_terms_version' => $type === ClientDecisionType::Approved ? $revision->terms_version : null,
                'reason' => $input['reason'] ?? null,
                'decided_at' => Carbon::now(),
                'ip_address' => $recordIp ? ($input['ip'] ?? null) : null,
                'user_agent' => $recordIp ? Str::limit((string) ($input['user_agent'] ?? ''), 500, '') : null,
                'metadata_json' => ['snapshot_hash' => $revision->snapshot_hash, 'revision_no' => $revision->revision_no],
            ]);
            $created = true;

            $changeRequest->forceFill(['decided_at' => $decision->decided_at]);
            $actor = $decision->client_name.' <'.$decision->client_email.'>';

            if ($type === ClientDecisionType::Declined) {
                $this->transition($changeRequest, ChangeRequestStatus::Declined, ActorType::Client, $actor, 'client.declined', [
                    'revision_no' => $revision->revision_no,
                    'snapshot_hash' => $revision->snapshot_hash,
                ]);

                return $decision;
            }

            $this->transition($changeRequest, ChangeRequestStatus::Approved, ActorType::Client, $actor, 'client.approved', [
                'revision_no' => $revision->revision_no,
                'snapshot_hash' => $revision->snapshot_hash,
                'price_minor' => $revision->price_minor,
                'currency' => $revision->currency,
                'terms_version' => $revision->terms_version,
            ]);

            $this->applyPaymentRule($changeRequest, $revision);

            return $decision;
        });

        if ($created) {
            $changeRequest = $link->changeRequest->refresh();
            Notification::send($this->ownerRecipients($changeRequest), new ClientDecided($changeRequest, $decision));
            $this->analytics->track($type === ClientDecisionType::Approved ? 'client_approved' : 'client_declined', $changeRequest->workspace_id, null, [
                'change_request_id' => $changeRequest->id,
                'response_seconds' => $changeRequest->sent_at ? (int) $changeRequest->sent_at->diffInSeconds($decision->decided_at, true) : null,
            ]);
        }

        return $decision;
    }

    public function clientComment(ApprovalLink $link, string $name, string $email, string $body): ChangeComment
    {
        $changeRequest = $link->changeRequest;

        if (! $link->isUsable() || ! $changeRequest->status->awaitingClient()) {
            throw ValidationException::withMessages(['body' => 'This approval link is no longer active.']);
        }

        $comment = DB::transaction(function () use ($changeRequest, $name, $email, $body) {
            /** @var ChangeComment $comment */
            $comment = $changeRequest->comments()->create([
                'revision_id' => $changeRequest->current_revision_id,
                'actor_type' => ActorType::Client,
                'actor_name' => $name,
                'actor_email' => mb_strtolower($email),
                'body' => $body,
                'visibility' => 'shared',
            ]);

            $actor = $name.' <'.mb_strtolower($email).'>';

            if ($changeRequest->status !== ChangeRequestStatus::Questioned) {
                $this->transition($changeRequest, ChangeRequestStatus::Questioned, ActorType::Client, $actor, 'client.questioned', ['comment_id' => $comment->id]);
            } else {
                $this->audit->record($changeRequest, 'client.commented', ActorType::Client, $actor, ['comment_id' => $comment->id]);
            }

            return $comment;
        });

        Notification::send($this->ownerRecipients($changeRequest), new ClientAskedQuestion($changeRequest, $comment));
        $this->analytics->track('client_questioned', $changeRequest->workspace_id, null, ['change_request_id' => $changeRequest->id]);

        return $comment;
    }

    /**
     * Owner reply (shared with client) or internal note (owner-only).
     */
    public function ownerComment(ChangeRequest $changeRequest, User $user, string $body, bool $internal): ChangeComment
    {
        $comment = DB::transaction(function () use ($changeRequest, $user, $body, $internal) {
            /** @var ChangeComment $comment */
            $comment = $changeRequest->comments()->create([
                'revision_id' => $changeRequest->current_revision_id,
                'actor_type' => ActorType::User,
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'body' => $body,
                'visibility' => $internal ? 'internal' : 'shared',
            ]);

            if (! $internal && $changeRequest->status === ChangeRequestStatus::Questioned) {
                $next = $changeRequest->first_viewed_at ? ChangeRequestStatus::Viewed : ChangeRequestStatus::Sent;
                $this->transition($changeRequest, $next, ActorType::User, $user, 'change_request.question_answered', ['comment_id' => $comment->id]);
            } else {
                $this->audit->record($changeRequest, $internal ? 'change_request.note_added' : 'change_request.reply_added', ActorType::User, $user, ['comment_id' => $comment->id]);
            }

            return $comment;
        });

        if (! $internal && $changeRequest->recipient_email && $changeRequest->status->awaitingClient()) {
            Notification::route('mail', [$changeRequest->recipient_email => $changeRequest->recipient_name])
                ->notify(new OwnerRepliedToClient($changeRequest, $comment));
        }

        return $comment;
    }

    public function clientMarkPaymentSent(ApprovalLink $link, ?string $reference): PaymentRecord
    {
        $changeRequest = $link->changeRequest;
        $payment = $changeRequest->currentPayment();

        if ($payment === null || $link->revoked_at !== null || $link->revision_id !== $changeRequest->current_revision_id) {
            throw ValidationException::withMessages(['payment' => 'There is no payment to mark for this request.']);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return $payment;
        }

        DB::transaction(function () use ($changeRequest, $payment, $reference) {
            $payment->forceFill([
                'status' => PaymentStatus::MarkedSent,
                'marked_sent_at' => Carbon::now(),
                'reference' => $reference !== null && $reference !== '' ? $reference : $payment->reference,
            ])->save();

            $actor = $changeRequest->recipient_email;

            if ($changeRequest->status === ChangeRequestStatus::PaymentPending) {
                $this->transition($changeRequest, ChangeRequestStatus::PaymentMarkedSent, ActorType::Client, $actor, 'payment.marked_sent');
            } else {
                $this->audit->record($changeRequest, 'payment.marked_sent', ActorType::Client, $actor);
            }
        });

        Notification::send($this->ownerRecipients($changeRequest), new PaymentMarkedSent($changeRequest));
        $this->analytics->track('payment_marked_sent', $changeRequest->workspace_id, null, ['change_request_id' => $changeRequest->id]);

        return $payment;
    }

    public function confirmPayment(ChangeRequest $changeRequest, User $user, ?string $reference): PaymentRecord
    {
        $payment = $changeRequest->currentPayment();

        if ($payment === null || $payment->status === PaymentStatus::NotRequired) {
            throw ValidationException::withMessages(['payment' => 'This change has no payment to confirm.']);
        }

        if ($payment->status === PaymentStatus::Confirmed) {
            return $payment;
        }

        DB::transaction(function () use ($changeRequest, $payment, $user, $reference) {
            $payment->forceFill([
                'status' => PaymentStatus::Confirmed,
                'confirmed_at' => Carbon::now(),
                'confirmed_by' => $user->id,
                'reference' => $reference !== null && $reference !== '' ? $reference : $payment->reference,
            ])->save();

            if (in_array($changeRequest->status, [ChangeRequestStatus::PaymentPending, ChangeRequestStatus::PaymentMarkedSent], true)) {
                $this->transition($changeRequest, ChangeRequestStatus::PaymentConfirmed, ActorType::User, $user, 'payment.confirmed');
                $this->transition($changeRequest, ChangeRequestStatus::ReadyToStart, ActorType::System, null, 'change_request.ready_to_start');
            } else {
                $this->audit->record($changeRequest, 'payment.confirmed', ActorType::User, $user);
            }
        });

        if ($changeRequest->recipient_email) {
            Notification::route('mail', [$changeRequest->recipient_email => $changeRequest->recipient_name])
                ->notify(new PaymentConfirmedToClient($changeRequest));
        }

        $this->analytics->track('payment_confirmed', $changeRequest->workspace_id, $user->id, ['change_request_id' => $changeRequest->id]);

        return $payment;
    }

    public function start(ChangeRequest $changeRequest, User $user): ChangeRequest
    {
        $changeRequest->forceFill(['started_at' => Carbon::now()]);
        $this->transition($changeRequest, ChangeRequestStatus::InProgress, ActorType::User, $user, 'change_request.started');

        return $changeRequest;
    }

    public function complete(ChangeRequest $changeRequest, User $user): ChangeRequest
    {
        $revision = $this->requireCurrentRevision($changeRequest);
        $payment = $changeRequest->currentPayment();

        if ($revision->payment_rule === PaymentRule::BeforeHandoff && $payment?->status !== PaymentStatus::Confirmed) {
            throw ValidationException::withMessages(['status' => 'Payment must be confirmed before handoff for this change.']);
        }

        $changeRequest->forceFill(['completed_at' => Carbon::now()]);
        $this->transition($changeRequest, ChangeRequestStatus::Completed, ActorType::User, $user, 'change_request.completed');

        return $changeRequest;
    }

    /**
     * Expire every pending request whose approval window has passed.
     */
    public function expireDue(): int
    {
        $count = 0;

        ChangeRequest::query()
            ->whereIn('status', ChangeRequestStatus::awaitingClientValues())
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->orderBy('id')
            ->each(function (ChangeRequest $changeRequest) use (&$count) {
                $expired = DB::transaction(function () use ($changeRequest) {
                    /** @var ChangeRequest $fresh */
                    $fresh = ChangeRequest::query()->whereKey($changeRequest->id)->lockForUpdate()->firstOrFail();

                    if (! $fresh->status->awaitingClient()) {
                        return false;
                    }

                    $this->links->revokeAll($fresh);
                    $this->transition($fresh, ChangeRequestStatus::Expired, ActorType::System, null, 'change_request.expired');

                    return true;
                });

                if ($expired) {
                    $count++;
                    Notification::send($this->ownerRecipients($changeRequest), new ChangeRequestExpired($changeRequest->refresh()));
                }
            });

        return $count;
    }

    /**
     * Move a change request through the state machine and audit it.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function transition(
        ChangeRequest $changeRequest,
        ChangeRequestStatus $to,
        ActorType $actorType,
        User|string|null $actor,
        string $event,
        array $metadata = [],
    ): void {
        $from = $changeRequest->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidStateTransition($from, $to);
        }

        $changeRequest->status = $to;
        $changeRequest->save();

        $this->audit->record($changeRequest, $event, $actorType, $actor, [
            'from' => $from->value,
            'to' => $to->value,
            ...$metadata,
        ]);
    }

    /**
     * Users who should hear about client activity: the creator and the
     * workspace owner, if they are still members.
     *
     * @return Collection<int, User>
     */
    public function ownerRecipients(ChangeRequest $changeRequest): Collection
    {
        /** @var Workspace $workspace */
        $workspace = $changeRequest->workspace;

        $ids = array_filter([$changeRequest->created_by, $workspace->owner_user_id]);

        return $workspace->members()->whereIn('users.id', $ids)->get()->values();
    }

    private function applyPaymentRule(ChangeRequest $changeRequest, ChangeRequestRevision $revision): void
    {
        $requiresPayment = $revision->payment_rule->requiresPayment() && $revision->price_minor > 0;

        $changeRequest->paymentRecords()->create([
            'revision_id' => $revision->id,
            'status' => $requiresPayment ? PaymentStatus::Pending : PaymentStatus::NotRequired,
            'amount_minor' => $revision->price_minor,
            'currency' => $revision->currency,
            'method_type' => 'external',
            'external_url' => $revision->payment_url,
        ]);

        if ($requiresPayment && $revision->payment_rule === PaymentRule::BeforeStart) {
            $this->transition($changeRequest, ChangeRequestStatus::PaymentPending, ActorType::System, null, 'payment.required_before_start');

            return;
        }

        $this->transition($changeRequest, ChangeRequestStatus::ReadyToStart, ActorType::System, null, 'change_request.ready_to_start', [
            'payment_rule' => $revision->payment_rule->value,
        ]);
    }

    private function lockRevision(ChangeRequest $changeRequest, ChangeRequestRevision $revision): void
    {
        $snapshot = $this->buildSnapshot($changeRequest, $revision);

        $revision->forceFill([
            'snapshot_json' => $snapshot,
            'snapshot_hash' => hash('sha256', (string) json_encode($snapshot)),
            'locked_at' => Carbon::now(),
        ])->save();
    }

    /**
     * The exact commercial content the client is asked to approve. Proof
     * Packs render from this snapshot, never from live editable tables.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(ChangeRequest $changeRequest, ChangeRequestRevision $revision): array
    {
        $project = $changeRequest->project;
        $baseline = $project->lockedBaseline();

        return [
            'reference' => $changeRequest->reference,
            'revision_no' => $revision->revision_no,
            'workspace' => ['name' => $changeRequest->workspace->name],
            'project' => ['title' => $project->title, 'code' => $project->code],
            'client' => ['name' => $project->client->displayName()],
            'title' => $revision->title,
            'description' => $revision->description,
            'scope_reason' => $revision->scope_reason,
            'scope_excerpt' => $revision->scope_excerpt,
            'scope_items' => $revision->scopeItems()->get()->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type->value,
                'title' => $item->title,
            ])->values()->all(),
            'baseline' => $baseline ? [
                'version' => $baseline->version,
                'content_hash' => $baseline->content_hash,
                'locked_at' => $baseline->locked_at?->toIso8601String(),
            ] : null,
            'price' => ['amount_minor' => $revision->price_minor, 'currency' => $revision->currency, 'formatted' => $revision->price()->format()],
            'timeline' => ['data' => $revision->timeline_json, 'label' => $revision->timelineLabel()],
            'payment_rule' => $revision->payment_rule->value,
            'payment_url' => $revision->payment_url,
            'payment_instructions' => $revision->payment_instructions,
            'terms_note' => $revision->terms_note,
            'terms_version' => $revision->terms_version,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createRevision(ChangeRequest $changeRequest, int $number, array $data, User $user): ChangeRequestRevision
    {
        /** @var ChangeRequestRevision $revision */
        $revision = $changeRequest->revisions()->create([
            'revision_no' => $number,
            'created_by' => $user->id,
            ...$this->revisionAttributes($data),
        ]);

        $this->syncScopeItems($revision, $data);

        return $revision;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function revisionAttributes(array $data): array
    {
        $currency = (string) $data['currency'];
        $price = $data['price_minor'] ?? Money::fromDecimal((string) ($data['price'] ?? '0'), $currency)->amount;

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'scope_reason' => $data['scope_reason'] ?? null,
            'scope_excerpt' => $data['scope_excerpt'] ?? null,
            'price_minor' => (int) $price,
            'currency' => $currency,
            'timeline_json' => $data['timeline_json'] ?? [
                'type' => $data['timeline_type'] ?? 'none',
                'value' => $data['timeline_value'] ?? null,
                'note' => $data['timeline_note'] ?? null,
            ],
            'payment_rule' => $data['payment_rule'] ?? PaymentRule::None->value,
            'payment_url' => $data['payment_url'] ?? null,
            'payment_instructions' => $data['payment_instructions'] ?? null,
            'terms_note' => $data['terms_note'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncScopeItems(ChangeRequestRevision $revision, array $data): void
    {
        if (! array_key_exists('scope_item_ids', $data)) {
            return;
        }

        /** @var list<int> $ids */
        $ids = array_map('intval', (array) $data['scope_item_ids']);
        $revision->scopeItems()->sync($ids);
    }

    private function requireCurrentRevision(ChangeRequest $changeRequest): ChangeRequestRevision
    {
        $revision = $changeRequest->currentRevision()->first();

        if ($revision === null) {
            throw new \LogicException('Change request has no current revision.');
        }

        return $revision;
    }
}
