<?php

namespace App\Http\Controllers\App;

use App\Enums\ChangeRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\ChangeRequest;
use App\Services\MetricsService;
use App\Support\Presenters;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(MetricsService $metrics): Response
    {
        $workspace = $this->workspace();

        $queue = ChangeRequest::query()
            ->forWorkspace($workspace)
            ->where(function ($q) {
                $q->whereIn('status', [
                    ChangeRequestStatus::Questioned->value,
                    ChangeRequestStatus::PaymentMarkedSent->value,
                    ChangeRequestStatus::Sent->value,
                    ChangeRequestStatus::Viewed->value,
                    ChangeRequestStatus::Draft->value,
                    ChangeRequestStatus::PaymentPending->value,
                ]);
            })
            ->with(['currentRevision', 'project.client'])
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [ChangeRequestStatus::Questioned->value, ChangeRequestStatus::PaymentMarkedSent->value])
            ->orderBy('expires_at')
            ->limit(12)
            ->get()
            ->map(fn (ChangeRequest $cr) => Presenters::changeRequestRow($cr, $workspace->timezone) + [
                'action' => match ($cr->status) {
                    ChangeRequestStatus::Questioned => 'Answer question',
                    ChangeRequestStatus::PaymentMarkedSent => 'Confirm payment',
                    ChangeRequestStatus::Draft => 'Finish and send',
                    ChangeRequestStatus::PaymentPending => 'Waiting for payment',
                    default => $cr->expires_at !== null && $cr->expires_at->lt(Carbon::now()->addDays(2)) ? 'Expiring soon' : 'Waiting for client',
                },
            ]);

        $activity = AuditEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('entity_type', (new ChangeRequest)->getMorphClass())
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AuditEvent $e) => [
                'id' => $e->id,
                'event' => Presenters::eventLabel($e->event),
                'actor' => $e->actor_label ?? ucfirst($e->actor_type),
                'at' => $e->occurred_at->copy()->setTimezone($workspace->timezone)->diffForHumans(),
                'url' => route('change-requests.show', $e->entity_id),
            ]);

        return Inertia::render('Dashboard', [
            'metrics' => $metrics->dashboard($workspace),
            'queue' => $queue,
            'activity' => $activity,
            'hasProjects' => $workspace->projects()->exists(),
            'hasClients' => $workspace->clients()->exists(),
        ]);
    }
}
