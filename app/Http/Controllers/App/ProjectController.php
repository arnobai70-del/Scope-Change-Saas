<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\ProjectRequest;
use App\Models\AuditEvent;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Project;
use App\Services\AnalyticsService;
use App\Services\AuditTrailService;
use App\Services\UsageLimitService;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly AnalyticsService $analytics,
        private readonly UsageLimitService $usage,
    ) {}

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');

        $projects = Project::query()
            ->forWorkspace($this->workspace())
            ->with('client')
            ->withCount('changeRequests')
            ->when($status === 'open', fn ($q) => $q->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::OnHold->value]))
            ->when($status !== 'open' && ProjectStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Project $p) => Presenters::project($p) + ['change_requests_count' => $p->getAttribute('change_requests_count')]);

        return Inertia::render('projects/Index', [
            'projects' => $projects,
            'filters' => ['status' => $status],
            'clients' => $this->clientOptions(),
            'currencies' => Money::SUPPORTED_CURRENCIES,
            'defaultCurrency' => $this->workspace()->currency,
            'openCreate' => $request->boolean('new'),
            'presetClientId' => $request->integer('client_id') ?: null,
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->usage->ensureCanCreateProject($workspace);

        $project = DB::transaction(function () use ($request, $workspace) {
            $project = new Project($request->projectData());
            $project->workspace_id = $workspace->id;
            $project->status ??= ProjectStatus::Active;
            $project->save();

            $this->audit->record($project, 'project.created', ActorType::User, $this->user());

            return $project;
        });

        $this->analytics->track('project_created', $workspace->id, $this->user()->id, ['project_id' => $project->id]);
        $this->toast('Project created. Add the agreed scope next.');

        return to_route('projects.show', ['project' => $project, 'tab' => 'scope']);
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $workspace = $this->workspace();
        $project->load('client');
        $current = $project->currentBaseline()?->load('items');
        $locked = $project->lockedBaseline();

        $changeRequests = $project->changeRequests()
            ->with('currentRevision')
            ->latest()
            ->get();

        $approved = $changeRequests->filter(fn (ChangeRequest $cr) => $cr->status->isApprovedFamily());
        $approvedTotals = [];
        $timelineDays = 0;
        foreach ($approved as $cr) {
            $revision = $cr->currentRevision;
            if ($revision === null) {
                continue;
            }
            $approvedTotals[$revision->currency] = ($approvedTotals[$revision->currency] ?? 0) + $revision->price_minor;
            $timeline = $revision->timeline_json;
            $timelineDays += match ($timeline['type']) {
                'days' => (int) ($timeline['value'] ?? 0),
                'weeks' => (int) ($timeline['value'] ?? 0) * 7,
                default => 0,
            };
        }

        $activity = AuditEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->where('entity_type', $project->getMorphClass())->where('entity_id', $project->id))
                ->orWhere(fn ($w) => $w->where('entity_type', (new ChangeRequest)->getMorphClass())->whereIn('entity_id', $changeRequests->pluck('id'))))
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'event' => Presenters::eventLabel($e->event),
                'actor' => $e->actor_label ?? ucfirst($e->actor_type),
                'at' => Presenters::time($e->occurred_at, $workspace->timezone),
            ]);

        return Inertia::render('projects/Show', [
            'project' => Presenters::project($project),
            'baseline' => Presenters::baseline($current, $workspace->timezone),
            'lockedBaseline' => $locked && $locked->id !== $current?->id ? Presenters::baseline($locked->load('items'), $workspace->timezone) : null,
            'baselineVersions' => $project->baselines()->get(['id', 'version', 'locked_at', 'content_hash'])->map(fn ($b) => [
                'version' => $b->version,
                'locked_at' => Presenters::time($b->locked_at, $workspace->timezone),
                'content_hash' => $b->content_hash,
            ]),
            'changeRequests' => $changeRequests->map(fn (ChangeRequest $cr) => Presenters::changeRequestRow($cr->setRelation('project', $project), $workspace->timezone)),
            'summary' => [
                'approved_totals' => collect($approvedTotals)->map(fn ($amount, $currency) => new Money($amount, $currency))->values(),
                'timeline_days' => $timelineDays,
                'approved_count' => $approved->count(),
            ],
            'activity' => $activity,
            'clients' => $this->clientOptions(),
            'currencies' => Money::SUPPORTED_CURRENCIES,
            'tab' => (string) $request->query('tab', 'overview'),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->projectData();
        $reopening = isset($data['status']) && $project->status === ProjectStatus::Completed
            && ProjectStatus::from($data['status'])->countsTowardsLimit();

        if ($reopening) {
            $this->usage->ensureCanCreateProject($this->workspace());
        }

        $project->fill($data)->save();
        $this->audit->record($project, 'project.updated', ActorType::User, $this->user());
        $this->toast('Project updated.');

        return back();
    }

    public function archive(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        if ($project->status === ProjectStatus::Archived) {
            $this->usage->ensureCanCreateProject($this->workspace());
            $project->status = ProjectStatus::Active;
            $project->archived_at = null;
        } else {
            $project->status = ProjectStatus::Archived;
            $project->archived_at = Carbon::now();
        }

        $project->save();
        $this->audit->record($project, $project->archived_at ? 'project.archived' : 'project.restored', ActorType::User, $this->user());
        $this->toast($project->archived_at ? 'Project archived.' : 'Project restored.');

        return back();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function clientOptions(): array
    {
        return array_values(Client::query()
            ->forWorkspace($this->workspace())
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => ['id' => $c->id, 'name' => $c->displayName()])
            ->all());
    }
}
