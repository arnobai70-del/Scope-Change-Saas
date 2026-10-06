<?php

namespace App\Console\Commands;

use App\Services\ChangeRequestService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('change-requests:expire')]
#[Description('Expire change requests whose approval window has passed')]
class ExpireChangeRequests extends Command
{
    public function handle(ChangeRequestService $service): int
    {
        $count = $service->expireDue();
        $this->info("Expired {$count} change request(s).");

        return self::SUCCESS;
    }
}
