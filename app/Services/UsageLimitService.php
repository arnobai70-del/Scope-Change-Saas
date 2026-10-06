<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Exceptions\PlanLimitReached;
use App\Models\UsageCounter;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Server-side enforcement of plan limits. The frontend only mirrors these
 * values for display; it is never trusted to enforce them.
 */
class UsageLimitService
{
    public const METRIC_CHANGE_REQUESTS = 'change_requests_created';

    public function __construct(private readonly PlanService $plans) {}

    public function activeProjectCount(Workspace $workspace): int
    {
        return $workspace->projects()
            ->whereIn('status', array_map(
                fn (ProjectStatus $s) => $s->value,
                array_filter(ProjectStatus::cases(), fn (ProjectStatus $s) => $s->countsTowardsLimit()),
            ))
            ->count();
    }

    public function changeRequestsThisMonth(Workspace $workspace): int
    {
        return (int) UsageCounter::query()
            ->where('workspace_id', $workspace->id)
            ->where('metric', self::METRIC_CHANGE_REQUESTS)
            ->where('period', $this->period())
            ->value('value');
    }

    public function seatCount(Workspace $workspace): int
    {
        return $workspace->members()->count()
            + $workspace->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())->count();
    }

    public function ensureCanCreateProject(Workspace $workspace, int $additional = 1): void
    {
        $limit = $this->plans->limit($workspace, 'active_projects');

        if ($limit !== null && $this->activeProjectCount($workspace) + $additional > $limit) {
            throw new PlanLimitReached('active_projects', "Your plan allows {$limit} active ".($limit === 1 ? 'project' : 'projects').'. Archive a project or upgrade to add more.');
        }
    }

    public function ensureCanCreateChangeRequest(Workspace $workspace): void
    {
        $limit = $this->plans->limit($workspace, 'change_requests_per_month');

        if ($limit !== null && $this->changeRequestsThisMonth($workspace) >= $limit) {
            throw new PlanLimitReached('change_requests_per_month', "Your plan allows {$limit} change requests per month. Upgrade for unlimited requests.");
        }
    }

    public function ensureCanAddSeat(Workspace $workspace): void
    {
        if (! $this->plans->hasFeature($workspace, 'team')) {
            throw new PlanLimitReached('team', 'Team members are available on the Agency plan.');
        }

        $limit = $this->plans->limit($workspace, 'seats');

        if ($limit !== null && $this->seatCount($workspace) >= $limit) {
            throw new PlanLimitReached('seats', "All {$limit} seats are in use. Add seats from Billing.");
        }
    }

    public function ensureFeature(Workspace $workspace, string $feature, string $message): void
    {
        if (! $this->plans->hasFeature($workspace, $feature)) {
            throw new PlanLimitReached($feature, $message);
        }
    }

    /**
     * Counters are never decremented, so deleting drafts cannot be used to
     * bypass the monthly allowance.
     */
    public function recordChangeRequestCreated(Workspace $workspace): void
    {
        $period = $this->period();

        DB::transaction(function () use ($workspace, $period) {
            $counter = UsageCounter::query()
                ->where('workspace_id', $workspace->id)
                ->where('metric', self::METRIC_CHANGE_REQUESTS)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                UsageCounter::query()->create([
                    'workspace_id' => $workspace->id,
                    'metric' => self::METRIC_CHANGE_REQUESTS,
                    'period' => $period,
                    'value' => 1,
                ]);

                return;
            }

            $counter->increment('value');
        });
    }

    /**
     * @return array<string, array{used: int, limit: int|null}>
     */
    public function summary(Workspace $workspace): array
    {
        return [
            'active_projects' => [
                'used' => $this->activeProjectCount($workspace),
                'limit' => $this->plans->limit($workspace, 'active_projects'),
            ],
            'change_requests_per_month' => [
                'used' => $this->changeRequestsThisMonth($workspace),
                'limit' => $this->plans->limit($workspace, 'change_requests_per_month'),
            ],
            'seats' => [
                'used' => $this->seatCount($workspace),
                'limit' => $this->plans->limit($workspace, 'seats'),
            ],
        ];
    }

    private function period(): string
    {
        return Carbon::now('UTC')->format('Y-m');
    }
}
