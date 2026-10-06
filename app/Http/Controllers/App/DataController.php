<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Jobs\ExportWorkspaceData;
use App\Models\DataExport;
use App\Models\DeletionRequest;
use App\Services\AnalyticsService;
use App\Services\AuditTrailService;
use App\Support\Presenters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataController extends Controller
{
    public function index(): Response
    {
        $workspace = $this->workspace();

        return Inertia::render('settings/Data', [
            'exports' => DataExport::query()->forWorkspace($workspace)->latest('id')->limit(10)->get()->map(fn (DataExport $e) => [
                'id' => $e->id,
                'type' => $e->type,
                'status' => $e->status,
                'created_at' => Presenters::time($e->created_at, $workspace->timezone),
                'download_url' => $e->status === 'ready' && $e->file_path ? route('data.download', $e) : null,
            ]),
            'deletionRequested' => DeletionRequest::query()->where('workspace_id', $workspace->id)->where('status', 'pending')->exists(),
            'canManage' => $workspace->roleOf($this->user())?->canManageWorkspace() ?? false,
        ]);
    }

    public function export(Request $request, AnalyticsService $analytics): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);

        $data = $request->validate(['type' => ['required', 'in:json,csv']]);

        $export = new DataExport(['type' => $data['type'], 'status' => 'queued', 'requested_by' => $this->user()->id]);
        $export->workspace_id = $workspace->id;
        $export->save();

        ExportWorkspaceData::dispatch($export->id)->afterCommit();
        $analytics->track('workspace_exported', $workspace->id, $this->user()->id);
        $this->toast('Export started. Refresh in a moment to download it.');

        return back();
    }

    public function download(DataExport $export): StreamedResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);
        abort_unless($export->workspace_id === $workspace->id && $export->status === 'ready' && $export->file_path, 404);

        return Storage::disk('local')->download((string) $export->file_path, 'workspace-export-'.$export->id.'.'.$export->type);
    }

    public function requestDeletion(Request $request, AuditTrailService $audit, AnalyticsService $analytics): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('billing', $workspace); // owner only

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000'], 'confirm' => ['accepted']]);

        DeletionRequest::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'status' => 'pending'],
            ['user_id' => $this->user()->id, 'scope' => 'workspace', 'reason' => $data['reason'] ?? null],
        );

        $audit->record($workspace, 'workspace.deletion_requested', ActorType::User, $this->user());
        $analytics->track('account_deletion_requested', $workspace->id, $this->user()->id);
        $this->toast('Deletion requested. Our team will confirm by email; records we must keep for legal reasons are retained only as long as required.');

        return back();
    }
}
