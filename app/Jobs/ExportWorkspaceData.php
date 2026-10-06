<?php

namespace App\Jobs;

use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\DataExport;
use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Data portability: a full JSON export or a CSV of change requests.
 */
class ExportWorkspaceData implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $exportId) {}

    public function handle(): void
    {
        $export = DataExport::query()->find($this->exportId);

        if ($export === null || $export->status === 'ready') {
            return;
        }

        $workspaceId = $export->workspace_id;
        $path = sprintf('exports/%d/export-%d.%s', $workspaceId, $export->id, $export->type);

        $content = $export->type === 'csv' ? $this->csv($workspaceId) : $this->json($workspaceId);
        Storage::disk('local')->put($path, $content);

        $export->forceFill([
            'status' => 'ready',
            'file_path' => $path,
            'expires_at' => Carbon::now()->addDays(7),
        ])->save();
    }

    public function failed(?Throwable $e): void
    {
        DataExport::query()->whereKey($this->exportId)->update(['status' => 'failed']);
    }

    private function json(int $workspaceId): string
    {
        $changeRequests = ChangeRequest::query()->where('workspace_id', $workspaceId)
            ->with(['revisions.decision', 'revisions.scopeItems', 'comments', 'paymentRecords'])
            ->get()
            ->map(function (ChangeRequest $cr) {
                $data = $cr->toArray();
                // Internal hashes of public tokens are not useful outside the app.
                unset($data['approval_links']);

                return $data;
            });

        return (string) json_encode([
            'exported_at' => Carbon::now('UTC')->toIso8601String(),
            'clients' => Client::query()->where('workspace_id', $workspaceId)->with('contacts')->get(),
            'projects' => Project::query()->where('workspace_id', $workspaceId)->with('baselines.items')->get(),
            'change_requests' => $changeRequests,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function csv(int $workspaceId): string
    {
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, ['reference', 'project', 'title', 'status', 'revision', 'price', 'currency', 'payment_rule', 'sent_at_utc', 'decided_at_utc', 'decided_by']);

        ChangeRequest::query()->where('workspace_id', $workspaceId)
            ->with(['currentRevision.decision', 'project'])
            ->orderBy('id')
            ->each(function (ChangeRequest $cr) use ($handle) {
                $revision = $cr->currentRevision;
                fputcsv($handle, array_map([$this, 'safeCell'], [
                    $cr->reference,
                    $cr->project->title,
                    $revision?->title,
                    $cr->status->value,
                    $revision?->revision_no,
                    $revision?->price()->toDecimalString(),
                    $revision?->currency,
                    $revision?->payment_rule->value,
                    $cr->sent_at?->copy()->utc()->toIso8601String(),
                    $cr->decided_at?->copy()->utc()->toIso8601String(),
                    $revision?->decision?->client_email,
                ]));
            });

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Neutralise spreadsheet formula injection.
     */
    public function safeCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
