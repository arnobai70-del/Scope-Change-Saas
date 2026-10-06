<?php

namespace App\Jobs;

use App\Models\ProofPack;
use App\Services\ProofPackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateProofPack implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $proofPackId) {}

    public function handle(ProofPackService $service): void
    {
        $proofPack = ProofPack::query()->find($this->proofPackId);

        // Idempotent: a retry after success does nothing.
        if ($proofPack === null || $proofPack->status === 'ready') {
            return;
        }

        $service->generate($proofPack);
    }

    public function failed(?Throwable $exception): void
    {
        ProofPack::query()->whereKey($this->proofPackId)->update([
            'status' => 'failed',
            'error' => $exception ? mb_substr($exception->getMessage(), 0, 500) : 'Unknown error',
        ]);
    }
}
