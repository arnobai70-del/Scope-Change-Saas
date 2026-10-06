<?php

namespace App\Services;

use App\Enums\ChangeRequestStatus;
use App\Jobs\SendPendingReminder;
use App\Models\ChangeRequest;
use Illuminate\Support\Carbon;

/**
 * Client reminder schedule: 24h and 72h after sending, and 24h before
 * expiry. When several are due at once only the most urgent is sent.
 */
class ReminderService
{
    public const KINDS = ['first', 'second', 'final'];

    public function __construct(private readonly PlanService $plans) {}

    /**
     * @return list<string> due kinds not yet sent, in schedule order
     */
    public function dueKinds(ChangeRequest $changeRequest, Carbon $now): array
    {
        if ($changeRequest->sent_at === null || ! $changeRequest->status->awaitingClient()) {
            return [];
        }

        $sent = $changeRequest->reminderLogs()->pluck('kind')->all();
        $schedule = [
            'first' => $changeRequest->sent_at->copy()->addHours(24),
            'second' => $changeRequest->sent_at->copy()->addHours(72),
            'final' => $changeRequest->expires_at?->copy()->subHours(24),
        ];

        $due = [];

        foreach ($schedule as $kind => $at) {
            if ($at !== null && $at->lte($now) && ! in_array($kind, $sent, true)
                && ($changeRequest->expires_at === null || $changeRequest->expires_at->isAfter($now))) {
                $due[] = $kind;
            }
        }

        return $due;
    }

    public function eligible(ChangeRequest $changeRequest): bool
    {
        $workspace = $changeRequest->workspace;

        return $changeRequest->status->awaitingClient()
            && $changeRequest->reminders_muted_at === null
            && filled($changeRequest->recipient_email)
            && $workspace->reminders_enabled
            && ! $workspace->isSuspended()
            && $this->plans->hasFeature($workspace, 'reminders');
    }

    public function dispatchDue(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        $count = 0;

        ChangeRequest::query()
            ->whereIn('status', ChangeRequestStatus::awaitingClientValues())
            ->whereNull('reminders_muted_at')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $now->copy()->subHours(24))
            ->with('workspace')
            ->orderBy('id')
            ->each(function (ChangeRequest $changeRequest) use ($now, &$count) {
                if (! $this->eligible($changeRequest)) {
                    return;
                }

                $due = $this->dueKinds($changeRequest, $now);

                if ($due !== []) {
                    SendPendingReminder::dispatch($changeRequest->id, $due[array_key_last($due)], $due);
                    $count++;
                }
            });

        return $count;
    }
}
