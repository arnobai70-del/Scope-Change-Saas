<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('change-requests:remind')]
#[Description('Queue due client reminders for pending change requests')]
class SendChangeRequestReminders extends Command
{
    public function handle(ReminderService $reminders): int
    {
        $count = $reminders->dispatchDue();
        $this->info("Queued {$count} reminder(s).");

        return self::SUCCESS;
    }
}
