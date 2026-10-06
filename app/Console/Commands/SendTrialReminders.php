<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Notifications\TrialEnding;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

#[Signature('billing:trial-reminders')]
#[Description('Notify workspace owners 7, 3 and 1 day(s) before their trial ends')]
class SendTrialReminders extends Command
{
    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();
        $sent = 0;

        foreach ([7, 3, 1] as $days) {
            $day = $today->copy()->addDays($days);

            Workspace::query()
                ->whereBetween('trial_ends_at', [$day, $day->copy()->endOfDay()])
                ->with('owner')
                ->each(function (Workspace $workspace) use ($days, &$sent) {
                    if ($workspace->activeSubscription() !== null) {
                        return;
                    }

                    // Idempotent per workspace + milestone even if the command runs twice.
                    if (! Cache::add("trial-reminder:{$workspace->id}:{$days}", true, now()->addDays(2))) {
                        return;
                    }

                    $workspace->owner->notify(new TrialEnding($workspace, $days));
                    $sent++;
                });
        }

        $this->info("Sent {$sent} trial reminder(s).");

        return self::SUCCESS;
    }
}
