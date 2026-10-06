<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Enums\ChangeRequestStatus;
use App\Enums\PaymentRule;
use App\Enums\ProjectStatus;
use App\Enums\TimelineImpact;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\ChangeRequestRequest;
use App\Models\AuditEvent;
use App\Models\ChangeComment;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestRevision;
use App\Models\Project;
use App\Models\Template;
use App\Services\AuditTrailService;
use App\Services\ChangeRequestService;
use App\Services\PlanService;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChangeRequestController extends Controller
{
    public function __construct(
        private readonly ChangeRequestService $service,
        private readonly PlanService $plans,
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $this->workspace();
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $groups = [
            'awaiting' => ChangeRequestStatus::awaitingClientValues(),
            'approved' => ChangeRequestStatus::approvedFamilyValues(),
            'closed' => [ChangeRequestStatus::Declined->value, ChangeRequestStatus::Expired->value, ChangeRequestStatus::Revoked->value],
            'draft' => [ChangeRequestStatus::Draft->value],
        ];

        $changeRequests = ChangeRequest::query()
            ->forWorkspace($workspace)
            ->with(['currentRevision', 'project.client'])
            ->when(isset($groups[$status]), fn ($q) => $q->whereIn('status', $groups[$status]))
            ->when($request->integer('project_id') > 0, fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('currentRevision', fn ($r) => $r->where('title', 'like', "%{$search}%"))))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ChangeRequest $cr) => Presenters::changeRequestRow($cr, $workspace->timezone));

        return Inertia::render('change-requests/Index', [
            'changeRequests' => $changeRequests,
            'filters' => ['status' => $status, 'q' => $search],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $workspace = $this->workspace();

        $projects = Project::query()
            ->forWorkspace($workspace)
            ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::OnHold->value])
            ->with('client')
            ->orderBy('title')
            ->get();

        if ($projects->isEmpty()) {
            $this->toast('Create a project first so the change has an agreed scope to compare against.', 'info');

            return to_route('projects.index', ['new' => 1]);
        }

        $selected = $projects->firstWhere('id', $request->integer('project_id')) ?? $projects->firstOrFail();

        return Inertia::render('change-requests/Edit', [
            'mode' => 'create',
            'projects' => $projects->map(fn (Project $p) => $this->projectOption($p)),
            'selectedProjectId' => $selected->id,
            'changeRequest' => null,
            'revision' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(ChangeRequestRequest $request): RedirectResponse
    {
        $request->validate(['project_id' => ['required', 'integer']]);

        $project = Project::query()->forWorkspace($this->workspace())->findOrFail($request->integer('project_id'));
        $this->authorize('update', $project);

        $changeRequest = $this->service->createDraft($project, $request->revisionData(), $this->user());

        if ($request->boolean('send_now')) {
            return $this->sendAndRedirect($changeRequest, $request->boolean('notify_client', true));
        }

        $this->toast('Draft saved.');

        return to_route('change-requests.show', $changeRequest);
    }

    public function show(ChangeRequest $changeRequest): Response
    {
        $this->authorize('view', $changeRequest);

        $workspace = $this->workspace();
        $changeRequest->load(['project.client', 'creator']);

        $revisions = $changeRequest->revisions()->with(['decision', 'scopeItems'])->get();
        $current = $revisions->firstWhere('id', $changeRequest->current_revision_id);
        $payment = $changeRequest->currentPayment();

        $events = AuditEvent::query()
            ->where('entity_type', $changeRequest->getMorphClass())
            ->where('entity_id', $changeRequest->id)
            ->orderBy('id')
            ->get()
            ->map(fn (AuditEvent $e) => [
                'id' => $e->id,
                'event' => Presenters::eventLabel($e->event),
                'actor' => $e->actor_label ?? ucfirst($e->actor_type),
                'at' => Presenters::time($e->occurred_at, $workspace->timezone),
                'at_utc' => $e->occurred_at->copy()->utc()->toIso8601String(),
                'hash' => substr($e->hash, 0, 12),
            ]);

        $status = $changeRequest->status;

        return Inertia::render('change-requests/Show', [
            'changeRequest' => Presenters::changeRequestRow($changeRequest, $workspace->timezone) + [
                'public_id' => $changeRequest->public_id,
                'recipient_name' => $changeRequest->recipient_name,
                'recipient_email' => $changeRequest->recipient_email,
                'created_by' => $changeRequest->creator?->name,
                'first_viewed_at' => Presenters::time($changeRequest->first_viewed_at, $workspace->timezone),
                'decided_at' => Presenters::time($changeRequest->decided_at, $workspace->timezone),
                'completed_at' => Presenters::time($changeRequest->completed_at, $workspace->timezone),
                'reminders_muted' => $changeRequest->reminders_muted_at !== null,
            ],
            'revision' => $current ? Presenters::revision($current, $workspace->timezone) : null,
            'revisions' => $revisions->map(fn (ChangeRequestRevision $r) => Presenters::revision($r, $workspace->timezone))->values(),
            'comments' => $changeRequest->comments()->get()->map(fn (ChangeComment $c) => Presenters::comment($c, $workspace->timezone)),
            'payment' => $payment ? [
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'amount' => $payment->amount(),
                'reference' => $payment->reference,
                'external_url' => $payment->external_url,
                'marked_sent_at' => Presenters::time($payment->marked_sent_at, $workspace->timezone),
                'confirmed_at' => Presenters::time($payment->confirmed_at, $workspace->timezone),
            ] : null,
            'events' => $events,
            'proofPacks' => $changeRequest->proofPacks()->get()->map(fn ($p) => [
                'id' => $p->id,
                'version' => $p->version,
                'status' => $p->status,
                'checksum' => $p->checksum,
                'generated_at' => Presenters::time($p->generated_at, $workspace->timezone),
                'download_url' => $p->isReady() ? route('proof-packs.download', $p) : null,
            ]),
            'can' => [
                'edit' => $status === ChangeRequestStatus::Draft && $current !== null && ! $current->isLocked(),
                'send' => $status->canTransitionTo(ChangeRequestStatus::Sent),
                'revoke' => $status->canTransitionTo(ChangeRequestStatus::Revoked),
                'revise' => $status->canRevise() && ! ($status === ChangeRequestStatus::Draft && $current !== null && ! $current->isLocked()),
                'start' => $status === ChangeRequestStatus::ReadyToStart,
                'complete' => in_array($status, [ChangeRequestStatus::ReadyToStart, ChangeRequestStatus::InProgress], true),
                'confirm_payment' => $payment !== null && in_array($payment->status->value, ['pending', 'marked_sent'], true),
                'proof_pack' => $current?->isLocked() ?? false,
                'proof_pack_plan' => $this->plans->hasFeature($workspace, 'proof_packs'),
                'delete' => $status === ChangeRequestStatus::Draft && $changeRequest->sent_at === null,
            ],
            'sentLink' => session('sent_link'),
        ]);
    }

    public function edit(ChangeRequest $changeRequest): Response|RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $revision = $changeRequest->currentRevision;

        if ($changeRequest->status !== ChangeRequestStatus::Draft || $revision === null || $revision->isLocked()) {
            $this->toast('Sent requests are locked. Start a new revision to make changes.', 'info');

            return to_route('change-requests.show', $changeRequest);
        }

        $project = $changeRequest->project->load('client');
        $revision->load('scopeItems');

        return Inertia::render('change-requests/Edit', [
            'mode' => 'edit',
            'projects' => [$this->projectOption($project)],
            'selectedProjectId' => $project->id,
            'changeRequest' => [
                'id' => $changeRequest->id,
                'reference' => $changeRequest->reference,
                'recipient_name' => $changeRequest->recipient_name,
                'recipient_email' => $changeRequest->recipient_email,
            ],
            'revision' => Presenters::revision($revision, $this->workspace()->timezone),
            ...$this->formOptions(),
        ]);
    }

    public function update(ChangeRequestRequest $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $this->service->updateDraft($changeRequest, $request->revisionData(), $this->user());

        if ($request->boolean('send_now')) {
            return $this->sendAndRedirect($changeRequest, $request->boolean('notify_client', true));
        }

        $this->toast('Draft saved.');

        return to_route('change-requests.show', $changeRequest);
    }

    public function destroy(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('delete', $changeRequest);

        abort_unless($changeRequest->status === ChangeRequestStatus::Draft && $changeRequest->sent_at === null, 409, 'Only drafts that were never sent can be deleted.');

        app(AuditTrailService::class)->record($changeRequest, 'change_request.deleted', ActorType::User, $this->user(), ['reference' => $changeRequest->reference]);
        $changeRequest->delete();
        $this->toast('Draft deleted.');

        return to_route('change-requests.index');
    }

    public function send(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        return $this->sendAndRedirect($changeRequest, $request->boolean('notify_client', true));
    }

    public function revoke(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $this->service->revoke($changeRequest, $this->user());
        $this->toast('Link revoked. The client can no longer act on it.');

        return back();
    }

    public function revise(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        if (! $changeRequest->status->canRevise()) {
            $this->toast('This request can no longer be revised.', 'error');

            return back();
        }

        $this->service->revise($changeRequest, $this->user());
        $this->toast('New revision started. The previous version and its decision are preserved.');

        return to_route('change-requests.edit', $changeRequest);
    }

    public function comment(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.config('security.comment_max_length', 5000)],
            'internal' => ['boolean'],
        ]);

        $this->service->ownerComment($changeRequest, $this->user(), $data['body'], (bool) ($data['internal'] ?? false));
        $this->toast(($data['internal'] ?? false) ? 'Note added.' : 'Reply sent.');

        return back();
    }

    public function confirmPayment(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $data = $request->validate(['reference' => ['nullable', 'string', 'max:120']]);
        $this->service->confirmPayment($changeRequest, $this->user(), $data['reference'] ?? null);
        $this->toast('Payment confirmed.');

        return back();
    }

    public function start(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $this->service->start($changeRequest, $this->user());
        $this->toast('Marked as in progress.');

        return back();
    }

    public function complete(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest);

        $this->service->complete($changeRequest, $this->user());
        $this->toast('Marked as completed. Generate a Proof Pack to keep the record.');

        return back();
    }

    public function preview(ChangeRequest $changeRequest): View
    {
        $this->authorize('view', $changeRequest);

        $revision = $changeRequest->currentRevision()->with('scopeItems')->firstOrFail();

        return view('client.show', [
            'changeRequest' => $changeRequest,
            'revision' => $revision,
            'workspace' => $changeRequest->workspace,
            'project' => $changeRequest->project,
            'comments' => $changeRequest->comments()->where('visibility', 'shared')->get(),
            'decision' => $revision->decision,
            'payment' => $changeRequest->currentPayment(),
            'canDecide' => false,
            'preview' => true,
            'token' => null,
            'showBranding' => ! $this->plans->hasFeature($changeRequest->workspace, 'remove_branding'),
        ]);
    }

    private function sendAndRedirect(ChangeRequest $changeRequest, bool $notifyClient): RedirectResponse
    {
        $result = $this->service->send($changeRequest, $this->user(), $notifyClient);

        $this->toast($notifyClient ? 'Sent to '.$changeRequest->recipient_email.'.' : 'Link ready to copy.');

        // The raw link is shown once; only its hash is stored.
        return to_route('change-requests.show', $changeRequest)->with('sent_link', $result['url']);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectOption(Project $project): array
    {
        $baseline = $project->lockedBaseline() ?? $project->currentBaseline();
        $baseline?->load('items');
        $recipient = $project->client->recipient();

        return [
            'id' => $project->id,
            'title' => $project->title,
            'client' => $project->client->displayName(),
            'currency' => $project->currency,
            'recipient_name' => $recipient['name'],
            'recipient_email' => $recipient['email'],
            'baseline' => Presenters::baseline($baseline, $this->workspace()->timezone),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $workspace = $this->workspace();

        return [
            'currencies' => Money::SUPPORTED_CURRENCIES,
            'paymentRules' => collect(PaymentRule::cases())->map(fn (PaymentRule $r) => ['value' => $r->value, 'label' => $r->label()]),
            'timelineTypes' => collect(TimelineImpact::cases())->map(fn (TimelineImpact $t) => $t->value),
            'templates' => Template::query()->availableTo($workspace)->where('type', 'change_request')->orderBy('name')->get()
                ->map(fn (Template $t) => ['id' => $t->id, 'name' => $t->name, 'system' => $t->isSystem(), 'content' => $t->content_json]),
            'defaults' => [
                'payment_url' => $workspace->default_payment_url,
                'payment_instructions' => $workspace->default_payment_instructions,
                'expiry_days' => $workspace->default_expiry_days,
            ],
        ];
    }
}
