<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\ProofPack;
use App\Services\AuditTrailService;
use App\Services\ProofPackService;
use App\Support\Presenters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProofPackController extends Controller
{
    public function index(): Response
    {
        $workspace = $this->workspace();

        $packs = ProofPack::query()
            ->forWorkspace($workspace)
            ->with('changeRequest.currentRevision', 'changeRequest.project')
            ->latest('id')
            ->paginate(25)
            ->through(fn (ProofPack $p) => [
                'id' => $p->id,
                'version' => $p->version,
                'status' => $p->status,
                'checksum' => $p->checksum,
                'generated_at' => Presenters::time($p->generated_at, $workspace->timezone),
                'reference' => $p->changeRequest->reference,
                'title' => $p->changeRequest->currentRevision?->title,
                'project' => $p->changeRequest->project->title,
                'change_request_id' => $p->change_request_id,
                'download_url' => $p->isReady() ? route('proof-packs.download', $p) : null,
            ]);

        return Inertia::render('proof-packs/Index', ['packs' => $packs]);
    }

    public function store(ChangeRequest $changeRequest, ProofPackService $service): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $service->request($changeRequest, $this->user());
        $this->toast('Proof Pack is being generated. It will appear below in a moment.');

        return back();
    }

    /**
     * Proof Packs live on private storage and are only streamed to members
     * of the owning workspace.
     */
    public function download(ProofPack $proofPack, AuditTrailService $audit): StreamedResponse
    {
        $this->authorize('view', $proofPack);
        abort_unless($proofPack->isReady() && Storage::disk(ProofPackService::DISK)->exists((string) $proofPack->file_path), 404);

        $audit->record($proofPack->changeRequest, 'proof_pack.downloaded', ActorType::User, $this->user(), ['version' => $proofPack->version]);

        return Storage::disk(ProofPackService::DISK)->download(
            (string) $proofPack->file_path,
            sprintf('%s-proof-pack-v%d.pdf', $proofPack->changeRequest->reference, $proofPack->version),
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
