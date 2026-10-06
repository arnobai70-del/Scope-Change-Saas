<?php

namespace App\Support;

use App\Models\ChangeComment;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestRevision;
use App\Models\Client;
use App\Models\Project;
use App\Models\ScopeBaseline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Shapes models into the props the Vue pages expect. Keeping this in one
 * place guarantees private fields (internal notes, IPs) are never sent to
 * the wrong audience.
 */
class Presenters
{
    public static function time(?Carbon $at, string $timezone): ?string
    {
        return $at?->copy()->setTimezone($timezone)->format('j M Y, H:i');
    }

    /**
     * @return array<string, mixed>
     */
    public static function changeRequestRow(ChangeRequest $cr, string $timezone): array
    {
        $revision = $cr->currentRevision;

        return [
            'id' => $cr->id,
            'reference' => $cr->reference,
            'title' => $revision?->title,
            'status' => $cr->status->value,
            'status_label' => $cr->status->label(),
            'price' => $revision?->price(),
            'project' => ['id' => $cr->project->id, 'title' => $cr->project->title],
            'client' => $cr->project->relationLoaded('client') ? $cr->project->client->displayName() : null,
            'revision_no' => $revision?->revision_no,
            'sent_at' => self::time($cr->sent_at, $timezone),
            'expires_at' => self::time($cr->expires_at, $timezone),
            'updated_at' => self::time($cr->updated_at, $timezone),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function revision(ChangeRequestRevision $revision, string $timezone): array
    {
        return [
            'id' => $revision->id,
            'revision_no' => $revision->revision_no,
            'title' => $revision->title,
            'description' => $revision->description,
            'scope_reason' => $revision->scope_reason,
            'scope_excerpt' => $revision->scope_excerpt,
            'price' => $revision->price(),
            'currency' => $revision->currency,
            'timeline' => $revision->timeline_json,
            'timeline_label' => $revision->timelineLabel(),
            'payment_rule' => $revision->payment_rule->value,
            'payment_rule_label' => $revision->payment_rule->label(),
            'payment_url' => $revision->payment_url,
            'payment_instructions' => $revision->payment_instructions,
            'terms_note' => $revision->terms_note,
            'locked_at' => self::time($revision->locked_at, $timezone),
            'snapshot_hash' => $revision->snapshot_hash,
            'scope_items' => $revision->scopeItems->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'title' => $item->title,
            ])->values()->all(),
            'decision' => $revision->decision ? [
                'decision' => $revision->decision->decision->value,
                'client_name' => $revision->decision->client_name,
                'client_email' => $revision->decision->client_email,
                'reason' => $revision->decision->reason,
                'decided_at' => self::time($revision->decision->decided_at, $timezone),
                'decided_at_utc' => $revision->decision->decided_at->copy()->utc()->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function comment(ChangeComment $comment, string $timezone): array
    {
        return [
            'id' => $comment->id,
            'actor_type' => $comment->actor_type->value,
            'author' => $comment->actor_name ?? ucfirst($comment->actor_type->value),
            'body' => $comment->body,
            'internal' => $comment->isInternal(),
            'created_at' => self::time($comment->created_at, $timezone),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function baseline(?ScopeBaseline $baseline, string $timezone): ?array
    {
        if ($baseline === null) {
            return null;
        }

        return [
            'id' => $baseline->id,
            'version' => $baseline->version,
            'title' => $baseline->title,
            'summary' => $baseline->summary,
            'locked' => $baseline->isLocked(),
            'locked_at' => self::time($baseline->locked_at, $timezone),
            'content_hash' => $baseline->content_hash,
            'items' => $baseline->items->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'title' => $item->title,
                'detail' => $item->detail,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function client(Client $client): array
    {
        return [
            'id' => $client->id,
            'type' => $client->type,
            'name' => $client->name,
            'company' => $client->company,
            'display_name' => $client->displayName(),
            'email' => $client->email,
            'phone' => $client->phone,
            'notes' => $client->notes,
            'archived' => $client->archived_at !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'code' => $project->code,
            'status' => $project->status->value,
            'status_label' => $project->status->label(),
            'currency' => $project->currency,
            'base_amount' => $project->baseAmount(),
            'revision_allowance' => $project->revision_allowance,
            'starts_on' => $project->starts_on?->toDateString(),
            'ends_on' => $project->ends_on?->toDateString(),
            'client' => $project->relationLoaded('client') ? ['id' => $project->client->id, 'name' => $project->client->displayName()] : null,
        ];
    }

    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'change_request.created' => 'Change request created',
            'change_request.draft_updated' => 'Draft updated',
            'change_request.sent' => 'Sent to client',
            'change_request.reissued' => 'Link reissued',
            'change_request.revoked' => 'Link revoked',
            'change_request.revision_started' => 'New revision started',
            'change_request.question_answered' => 'Question answered',
            'change_request.reply_added' => 'Reply sent to client',
            'change_request.note_added' => 'Internal note added',
            'change_request.ready_to_start' => 'Ready to start',
            'change_request.started' => 'Work started',
            'change_request.completed' => 'Marked completed',
            'change_request.expired' => 'Expired without decision',
            'change_request.proof_packed' => 'Proof Pack finalised',
            'client.viewed' => 'Client viewed',
            'client.questioned' => 'Client asked a question',
            'client.commented' => 'Client commented',
            'client.approved' => 'Client approved',
            'client.declined' => 'Client declined',
            'payment.required_before_start' => 'Payment required before start',
            'payment.marked_sent' => 'Client marked payment sent',
            'payment.confirmed' => 'Payment confirmed',
            'proof_pack.requested' => 'Proof Pack requested',
            'proof_pack.generated' => 'Proof Pack generated',
            'reminder.sent' => 'Reminder sent',
            default => Str::of($event)->replace(['.', '_'], ' ')->ucfirst()->toString(),
        };
    }
}
