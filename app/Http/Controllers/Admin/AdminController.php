<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessProviderWebhook;
use App\Models\AnalyticsEvent;
use App\Models\AuditEvent;
use App\Models\EmailDelivery;
use App\Models\FeatureFlag;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Services\AuditTrailService;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Operations panel. Every mutating admin action is written to the audit
 * trail. Passwords, payment secrets and raw webhook payloads are never
 * shown here.
 */
class AdminController extends Controller
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function dashboard(PlanService $plans): Response
    {
        $active = Subscription::query()->whereIn('status', Subscription::ACTIVE_STATUSES)->get();
        $mrr = 0;
        foreach ($active as $subscription) {
            $definition = $plans->definition($subscription->plan);
            $mrr += $subscription->billing_interval === 'year'
                ? intdiv((int) $definition['yearly_minor'], 12)
                : (int) $definition['monthly_minor'];
        }

        $funnel = collect(['signup_completed', 'onboarding_completed', 'project_created', 'change_request_sent', 'client_approved', 'subscription_activated'])
            ->mapWithKeys(fn ($event) => [$event => AnalyticsEvent::query()->where('event', $event)->where('occurred_at', '>=', Carbon::now()->subDays(30))->count()]);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'active_users_30d' => User::query()->where('last_login_at', '>=', Carbon::now()->subDays(30))->count(),
                'workspaces' => Workspace::query()->count(),
                'trials' => Workspace::query()->where('trial_ends_at', '>', Carbon::now())->count(),
                'paid' => $active->count(),
                'mrr_usd_minor' => $mrr,
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_webhooks' => WebhookEvent::query()->where('status', 'failed')->count(),
                'email_failures_7d' => EmailDelivery::query()->whereIn('status', ['hard_bounce', 'complaint'])->where('updated_at', '>=', Carbon::now()->subDays(7))->count(),
                'open_tickets' => SupportTicket::query()->where('state', 'open')->count(),
            ],
            'funnel' => $funnel,
        ]);
    }

    public function users(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'verified' => $u->email_verified_at !== null,
                    'two_factor' => $u->two_factor_confirmed_at !== null,
                    'status' => $u->status,
                    'is_admin' => $u->is_admin,
                    'last_login_at' => $u->last_login_at?->toDayDateTimeString(),
                    'created_at' => $u->created_at?->toFormattedDateString(),
                ]),
            'filters' => ['q' => $search],
        ]);
    }

    public function updateUserStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        abort_if($user->id === $this->user()->id, 422, 'You cannot change your own status.');

        $user->forceFill(['status' => $data['status']])->save();

        if ($data['status'] === 'suspended') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $this->audit->record($user, 'admin.user_'.$data['status'], ActorType::Admin, $this->user(), ['reason' => $data['reason']]);
        $this->toast('User updated.');

        return back();
    }

    public function workspaces(Request $request, PlanService $plans): Response
    {
        $search = trim((string) $request->query('q', ''));

        return Inertia::render('admin/Workspaces', [
            'workspaces' => Workspace::query()
                ->with('owner')
                ->withCount(['members', 'projects', 'changeRequests'])
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (Workspace $w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'owner' => $w->owner->email,
                    'plan' => $plans->effectivePlan($w),
                    'members' => $w->getAttribute('members_count'),
                    'projects' => $w->getAttribute('projects_count'),
                    'change_requests' => $w->getAttribute('change_requests_count'),
                    'suspended' => $w->isSuspended(),
                    'suspension_reason' => $w->suspension_reason,
                    'created_at' => $w->created_at?->toFormattedDateString(),
                ]),
            'filters' => ['q' => $search],
        ]);
    }

    public function suspendWorkspace(Request $request, Workspace $workspace): RedirectResponse
    {
        $data = $request->validate(['suspend' => ['required', 'boolean'], 'reason' => ['required_if:suspend,true', 'nullable', 'string', 'max:500']]);

        $workspace->forceFill([
            'suspended_at' => $data['suspend'] ? Carbon::now() : null,
            'suspension_reason' => $data['suspend'] ? $data['reason'] : null,
        ])->save();

        $this->audit->record($workspace, $data['suspend'] ? 'admin.workspace_suspended' : 'admin.workspace_restored', ActorType::Admin, $this->user(), ['reason' => $data['reason'] ?? null]);
        $this->toast('Workspace updated.');

        return back();
    }

    public function webhooks(Request $request): Response
    {
        $status = (string) $request->query('status', 'failed');

        return Inertia::render('admin/Webhooks', [
            'events' => WebhookEvent::query()
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (WebhookEvent $e) => [
                    'id' => $e->id,
                    'provider' => $e->provider,
                    'external_id' => $e->external_id,
                    'event_type' => $e->event_type,
                    'status' => $e->status,
                    'attempts' => $e->attempts,
                    'error' => $e->error,
                    'payload_hash' => substr($e->payload_hash, 0, 16),
                    'created_at' => $e->created_at?->toDayDateTimeString(),
                ]),
            'filters' => ['status' => $status],
        ]);
    }

    public function retryWebhook(WebhookEvent $event): RedirectResponse
    {
        abort_if($event->status === 'processed', 422, 'Already processed.');

        $event->forceFill(['status' => 'pending'])->save();
        ProcessProviderWebhook::dispatch($event->id);
        $this->audit->record($event, 'admin.webhook_retried', ActorType::Admin, $this->user());
        $this->toast('Webhook queued for retry.');

        return back();
    }

    public function tickets(Request $request): Response
    {
        $state = (string) $request->query('state', 'open');

        return Inertia::render('admin/Tickets', [
            'tickets' => SupportTicket::query()
                ->when($state !== 'all', fn ($q) => $q->where('state', $state))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (SupportTicket $t) => $t->only(['id', 'name', 'email', 'subject', 'body', 'state', 'priority', 'internal_notes']) + [
                    'created_at' => $t->created_at?->toDayDateTimeString(),
                ]),
            'filters' => ['state' => $state],
        ]);
    }

    public function updateTicket(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'state' => ['required', Rule::in(['open', 'pending', 'resolved'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $ticket->forceFill($data + ['resolved_at' => $data['state'] === 'resolved' ? Carbon::now() : null])->save();
        $this->audit->record($ticket, 'admin.ticket_updated', ActorType::Admin, $this->user(), ['state' => $data['state']]);
        $this->toast('Ticket updated.');

        return back();
    }

    public function flags(): Response
    {
        $defaults = (array) config('features', []);
        $rows = FeatureFlag::query()->get()->keyBy('key');

        $flags = collect(array_keys($defaults))->merge($rows->keys())->unique()->values()->map(fn ($key) => [
            'key' => $key,
            'enabled' => $rows->has($key) ? (bool) $rows[$key]->enabled : (bool) ($defaults[$key] ?? false),
            'overridden' => $rows->has($key),
            'description' => $rows->get($key)?->description,
        ]);

        return Inertia::render('admin/Flags', ['flags' => $flags]);
    }

    public function toggleFlag(Request $request): RedirectResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'], 'enabled' => ['required', 'boolean']]);

        $flag = FeatureFlag::query()->updateOrCreate(['key' => $data['key']], ['enabled' => $data['enabled']]);
        $this->audit->record($flag, 'admin.flag_toggled', ActorType::Admin, $this->user(), $data);
        $this->toast('Feature flag updated.');

        return back();
    }

    public function audit(): Response
    {
        return Inertia::render('admin/Audit', [
            'events' => AuditEvent::query()
                ->where('actor_type', ActorType::Admin->value)
                ->latest('id')
                ->paginate(50)
                ->through(fn (AuditEvent $e) => [
                    'id' => $e->id,
                    'event' => $e->event,
                    'actor' => $e->actor_label,
                    'entity' => class_basename($e->entity_type).' #'.$e->entity_id,
                    'metadata' => $e->metadata_json,
                    'at' => $e->occurred_at->toDayDateTimeString(),
                ]),
        ]);
    }
}
