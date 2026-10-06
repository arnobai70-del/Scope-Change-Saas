<?php

namespace App\Jobs;

use App\Enums\ActorType;
use App\Models\ChangeRequest;
use App\Models\ReminderLog;
use App\Notifications\ClientReminder;
use App\Services\AuditTrailService;
use App\Services\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class SendPendingReminder implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $markKinds  every kind that was due; the earlier ones are skipped
     */
    public function __construct(
        public readonly int $changeRequestId,
        public readonly string $kind,
        public readonly array $markKinds,
    ) {}

    public function handle(ReminderService $reminders, AuditTrailService $audit): void
    {
        $changeRequest = ChangeRequest::query()->with('workspace')->find($this->changeRequestId);

        // Re-check at send time: a decision, mute or revoke since dispatch stops the reminder.
        if ($changeRequest === null || ! $reminders->eligible($changeRequest)) {
            return;
        }

        try {
            DB::transaction(function () use ($changeRequest) {
                foreach ($this->markKinds as $kind) {
                    ReminderLog::query()->create([
                        'change_request_id' => $changeRequest->id,
                        'kind' => $kind,
                        'sent_at' => Carbon::now(),
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return; // already sent by an earlier attempt
        }

        $muteUrl = URL::temporarySignedRoute('client.reminders.mute', now()->addDays(60), ['publicId' => $changeRequest->public_id]);

        Notification::route('mail', [(string) $changeRequest->recipient_email => $changeRequest->recipient_name])
            ->notify(new ClientReminder($changeRequest, $this->kind, $muteUrl));

        $audit->record($changeRequest, 'reminder.sent', ActorType::System, null, ['kind' => $this->kind]);
    }
}
