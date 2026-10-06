<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\ChangeRequestStatus;
use App\Jobs\GenerateProofPack;
use App\Models\AuditEvent;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestRevision;
use App\Models\ProofPack;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

/**
 * Builds the evidence bundle for a change request. Content is rendered
 * from locked revision snapshots and immutable decision / audit records,
 * so regenerating the same state produces identical commercial content.
 */
class ProofPackService
{
    public const DISK = 'local';

    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly UsageLimitService $usage,
        private readonly AnalyticsService $analytics,
    ) {}

    public function request(ChangeRequest $changeRequest, User $user): ProofPack
    {
        $this->usage->ensureFeature($changeRequest->workspace, 'proof_packs', 'PDF Proof Packs are available on paid plans. You can still print the request from its page.');

        $revision = $changeRequest->currentRevision;

        if ($revision === null || ! $revision->isLocked()) {
            throw ValidationException::withMessages(['proof_pack' => 'Send the request to the client before generating a Proof Pack.']);
        }

        $proofPack = DB::transaction(function () use ($changeRequest, $user) {
            $version = (int) $changeRequest->proofPacks()->lockForUpdate()->max('version') + 1;

            $proofPack = new ProofPack([
                'change_request_id' => $changeRequest->id,
                'version' => $version,
                'status' => 'queued',
                'requested_by' => $user->id,
            ]);
            $proofPack->workspace_id = $changeRequest->workspace_id;
            $proofPack->save();

            $this->audit->record($changeRequest, 'proof_pack.requested', ActorType::User, $user, ['version' => $version]);

            return $proofPack;
        });

        GenerateProofPack::dispatch($proofPack->id)->afterCommit();

        return $proofPack;
    }

    public function generate(ProofPack $proofPack): ProofPack
    {
        $changeRequest = $proofPack->changeRequest;
        $content = $this->content($changeRequest);
        $contentHash = hash('sha256', (string) json_encode($content));

        $html = View::make('pdf.proof-pack', [
            'content' => $content,
            'contentHash' => $contentHash,
            'proofPack' => $proofPack,
            'generatedAt' => Carbon::now('UTC'),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();

        $path = sprintf('proof-packs/%d/%s-v%d.pdf', $changeRequest->workspace_id, $changeRequest->public_id, $proofPack->version);
        Storage::disk(self::DISK)->put($path, $pdf);

        DB::transaction(function () use ($proofPack, $changeRequest, $path, $pdf, $contentHash) {
            $proofPack->forceFill([
                'status' => 'ready',
                'file_path' => $path,
                'checksum' => hash('sha256', $pdf),
                'content_hash' => $contentHash,
                'generated_at' => Carbon::now(),
                'error' => null,
            ])->save();

            $this->audit->record($changeRequest, 'proof_pack.generated', ActorType::System, null, [
                'version' => $proofPack->version,
                'checksum' => $proofPack->checksum,
                'content_hash' => $contentHash,
            ]);

            if ($changeRequest->status === ChangeRequestStatus::Completed) {
                app(ChangeRequestService::class)->transition($changeRequest, ChangeRequestStatus::ProofPacked, ActorType::System, null, 'change_request.proof_packed');
            }
        });

        $this->analytics->track('proof_pack_generated', $changeRequest->workspace_id, $proofPack->requested_by, ['change_request_id' => $changeRequest->id]);

        return $proofPack;
    }

    /**
     * Deterministic Proof Pack content.
     *
     * @return array<string, mixed>
     */
    public function content(ChangeRequest $changeRequest): array
    {
        $changeRequest->loadMissing(['project.client', 'workspace']);
        $workspace = $changeRequest->workspace;
        $timezone = $workspace->timezone;

        $revisions = $changeRequest->revisions()->whereNotNull('locked_at')->with('decision')->get();
        $baseline = $changeRequest->project->lockedBaseline();

        $time = fn (?Carbon $at) => $at ? [
            'utc' => $at->copy()->utc()->toIso8601String(),
            'local' => $at->copy()->setTimezone($timezone)->format('j M Y, H:i T'),
        ] : null;

        return [
            'cover' => [
                'workspace' => $workspace->name,
                'project' => $changeRequest->project->title,
                'project_code' => $changeRequest->project->code,
                'client' => $changeRequest->project->client->displayName(),
                'reference' => $changeRequest->reference,
                'public_id' => $changeRequest->public_id,
                // Packing itself must not change the content hash of a regenerated pack.
                'status' => ($changeRequest->status === ChangeRequestStatus::ProofPacked ? ChangeRequestStatus::Completed : $changeRequest->status)->label(),
                'timezone' => $timezone,
            ],
            'baseline' => $baseline ? [
                'version' => $baseline->version,
                'title' => $baseline->title,
                'locked_at' => $time($baseline->locked_at),
                'content_hash' => $baseline->content_hash,
                'items' => $baseline->content_json['items'] ?? [],
            ] : null,
            'revisions' => $revisions->map(fn (ChangeRequestRevision $revision) => [
                'revision_no' => $revision->revision_no,
                'locked_at' => $time($revision->locked_at),
                'snapshot' => $revision->snapshot_json,
                'snapshot_hash' => $revision->snapshot_hash,
                'decision' => $revision->decision ? [
                    'decision' => $revision->decision->decision->value,
                    'client_name' => $revision->decision->client_name,
                    'client_email' => $revision->decision->client_email,
                    'decided_at' => $time($revision->decision->decided_at),
                    'accepted_terms_version' => $revision->decision->accepted_terms_version,
                    'reason' => $revision->decision->reason,
                ] : null,
            ])->values()->all(),
            'comments' => $changeRequest->comments()->where('visibility', 'shared')->get()->map(fn ($comment) => [
                'author' => $comment->actor_name ?? $comment->actor_type->value,
                'actor_type' => $comment->actor_type->value,
                'body' => $comment->body,
                'at' => $time($comment->created_at),
            ])->values()->all(),
            'payments' => $changeRequest->paymentRecords()->orderBy('id')->get()->map(fn ($payment) => [
                'status' => $payment->status->label(),
                'amount' => $payment->amount()->format(),
                'reference' => $payment->reference,
                'marked_sent_at' => $time($payment->marked_sent_at),
                'confirmed_at' => $time($payment->confirmed_at),
            ])->values()->all(),
            'completion' => [
                'started_at' => $time($changeRequest->started_at),
                'completed_at' => $time($changeRequest->completed_at),
            ],
            'timeline' => AuditEvent::query()
                ->where('entity_type', $changeRequest->getMorphClass())
                ->where('entity_id', $changeRequest->id)
                ->whereNotIn('event', ['proof_pack.requested', 'proof_pack.generated', 'change_request.proof_packed'])
                ->orderBy('id')
                ->get()
                ->map(fn (AuditEvent $event) => [
                    'event' => $event->event,
                    'actor' => $event->actor_label ?? $event->actor_type,
                    'at' => $time($event->occurred_at),
                    'hash' => $event->hash,
                ])->values()->all(),
        ];
    }
}
