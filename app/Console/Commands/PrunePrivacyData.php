<?php

namespace App\Console\Commands;

use App\Models\ClientDecision;
use App\Models\DataExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Signature('privacy:prune')]
#[Description('Apply retention: clear old client IP/user agent data and expired export files')]
class PrunePrivacyData extends Command
{
    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays((int) config('security.client_ip_retention_days', 365));

        $cleared = 0;
        ClientDecision::query()
            ->where('decided_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNotNull('ip_address')->orWhereNotNull('user_agent'))
            ->each(function (ClientDecision $decision) use (&$cleared) {
                $decision->forceFill(['ip_address' => null, 'user_agent' => null])->save();
                $cleared++;
            });

        $exports = 0;
        DataExport::query()
            ->whereNotNull('file_path')
            ->where('expires_at', '<', Carbon::now())
            ->each(function (DataExport $export) use (&$exports) {
                Storage::disk('local')->delete((string) $export->file_path);
                $export->forceFill(['file_path' => null, 'status' => 'expired'])->save();
                $exports++;
            });

        $this->info("Cleared {$cleared} decision(s) and {$exports} export(s).");

        return self::SUCCESS;
    }
}
