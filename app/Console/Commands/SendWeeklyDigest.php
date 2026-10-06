<?php

namespace App\Console\Commands;

use App\Enums\ChangeRequestStatus;
use App\Models\ClientDecision;
use App\Models\Workspace;
use App\Notifications\WeeklyDigest;
use App\Support\Money;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

#[Signature('digest:weekly')]
#[Description('Send the opt-in weekly digest on Monday morning in each workspace timezone')]
class SendWeeklyDigest extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Workspace::query()->with('members')->each(function (Workspace $workspace) use (&$sent) {
            $local = Carbon::now($workspace->timezone);

            if (! $local->isMonday() || $local->hour !== 8) {
                return;
            }

            if (! Cache::add("weekly-digest:{$workspace->id}:{$local->format('o-W')}", true, now()->addDays(2))) {
                return;
            }

            $since = Carbon::now()->subWeek();
            $approved = ClientDecision::query()
                ->where('decision', 'approved')
                ->where('decided_at', '>=', $since)
                ->whereHas('changeRequest', fn ($q) => $q->where('workspace_id', $workspace->id))
                ->with('revision')
                ->get();

            $value = new Money(0, $workspace->currency);
            foreach ($approved as $decision) {
                if ($decision->revision->currency === $workspace->currency) {
                    $value = $value->add($decision->revision->price());
                }
            }

            $stats = [
                'pending' => $workspace->changeRequests()->whereIn('status', ChangeRequestStatus::awaitingClientValues())->count(),
                'approved' => $approved->count(),
                'approved_value' => $value->format(),
                'questions' => $workspace->changeRequests()->where('status', ChangeRequestStatus::Questioned->value)->count(),
            ];

            foreach ($workspace->members as $member) {
                if ($member->wantsNotification('weekly_digest')) {
                    $member->notify(new WeeklyDigest($workspace, $stats));
                    $sent++;
                }
            }
        });

        $this->info("Sent {$sent} digest(s).");

        return self::SUCCESS;
    }
}
