<?php

namespace App\Services;

use App\Enums\ChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\ChangeRequest;
use App\Models\ClientDecision;
use App\Models\Workspace;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Value metrics for the dashboard and reports. Amounts are always grouped
 * by currency; currencies are never mixed or converted.
 */
class MetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(Workspace $workspace): array
    {
        $monthStart = Carbon::now($workspace->timezone)->startOfMonth()->utc();

        return [
            'pending_value' => $this->sumByCurrency($this->query($workspace)->whereIn('status', ChangeRequestStatus::awaitingClientValues())),
            'pending_count' => $this->query($workspace)->whereIn('status', ChangeRequestStatus::awaitingClientValues())->count(),
            'approved_this_month' => $this->approvedSince($workspace, $monthStart),
            'revenue_protected' => $this->sumByCurrency($this->query($workspace)->whereIn('status', ChangeRequestStatus::approvedFamilyValues())),
            'outstanding_value' => $this->outstanding($workspace),
            'avg_response_hours' => $this->averageResponseHours($workspace, Carbon::now()->subDays(90)),
            'counts' => $this->statusCounts($workspace),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function report(Workspace $workspace, int $months = 6): array
    {
        $series = [];
        $now = Carbon::now($workspace->timezone);

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = $now->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $decisions = $this->decisionsBetween($workspace, $start->copy()->utc(), $end->copy()->utc());
            $approved = $decisions->filter(fn (ClientDecision $d) => $d->decision->value === 'approved');

            $series[] = [
                'month' => $start->format('M Y'),
                'approved' => $approved->count(),
                'declined' => $decisions->count() - $approved->count(),
                'value' => $this->decisionValue($approved, $workspace->currency),
            ];
        }

        $decided = $this->decisionsBetween($workspace, Carbon::now()->subDays(365), Carbon::now());
        $approvedCount = $decided->filter(fn (ClientDecision $d) => $d->decision->value === 'approved')->count();

        return [
            'series' => $series,
            'currency' => $workspace->currency,
            'approval_rate' => $decided->count() > 0 ? round($approvedCount / $decided->count() * 100, 1) : null,
            'median_response_hours' => $this->medianResponseHours($workspace),
            'revenue_protected' => $this->sumByCurrency($this->query($workspace)->whereIn('status', ChangeRequestStatus::approvedFamilyValues())),
            'counts' => $this->statusCounts($workspace),
        ];
    }

    /**
     * @return Builder<ChangeRequest>
     */
    private function query(Workspace $workspace): Builder
    {
        return ChangeRequest::query()->forWorkspace($workspace);
    }

    /**
     * @param  Builder<ChangeRequest>  $query
     * @return list<array{amount: int, currency: string, decimal: string, formatted: string}>
     */
    private function sumByCurrency(Builder $query): array
    {
        $rows = $query
            ->join('change_request_revisions as r', 'r.id', '=', 'change_requests.current_revision_id')
            ->selectRaw('r.currency as currency, SUM(r.price_minor) as total')
            ->groupBy('r.currency')
            ->get();

        return array_values($rows->map(function ($row) {
            $currency = (string) $row->getAttribute('currency');

            return Money::isSupportedCurrency($currency) ? (new Money((int) $row->getAttribute('total'), $currency))->jsonSerialize() : null;
        })->filter()->all());
    }

    /**
     * @return array{count: int, value: list<array{amount: int, currency: string, decimal: string, formatted: string}>}
     */
    private function approvedSince(Workspace $workspace, Carbon $since): array
    {
        $decisions = $this->decisionsBetween($workspace, $since, Carbon::now())
            ->filter(fn (ClientDecision $d) => $d->decision->value === 'approved');

        $totals = [];
        foreach ($decisions as $decision) {
            $currency = $decision->revision->currency;
            $totals[$currency] = ($totals[$currency] ?? 0) + $decision->revision->price_minor;
        }

        return [
            'count' => $decisions->count(),
            'value' => array_values(collect($totals)->map(fn (int $amount, string $currency) => (new Money($amount, $currency))->jsonSerialize())->all()),
        ];
    }

    /**
     * Approved work whose payment is still outstanding.
     *
     * @return list<array{amount: int, currency: string, decimal: string, formatted: string}>
     */
    private function outstanding(Workspace $workspace): array
    {
        $rows = ChangeRequest::query()
            ->forWorkspace($workspace)
            ->join('payment_records as p', function ($join) {
                $join->on('p.change_request_id', '=', 'change_requests.id')
                    ->on('p.revision_id', '=', 'change_requests.current_revision_id');
            })
            ->whereIn('p.status', [PaymentStatus::Pending->value, PaymentStatus::MarkedSent->value])
            ->selectRaw('p.currency as currency, SUM(p.amount_minor) as total')
            ->groupBy('p.currency')
            ->get();

        return array_values($rows->map(fn ($row) => (new Money((int) $row->getAttribute('total'), (string) $row->getAttribute('currency')))->jsonSerialize())->all());
    }

    /**
     * @return Collection<int, ClientDecision>
     */
    private function decisionsBetween(Workspace $workspace, Carbon $from, Carbon $to): Collection
    {
        return ClientDecision::query()
            ->whereBetween('decided_at', [$from, $to])
            ->whereHas('changeRequest', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->with(['revision', 'changeRequest'])
            ->get();
    }

    /**
     * @param  Collection<int, ClientDecision>  $decisions
     */
    private function decisionValue(Collection $decisions, string $currency): int
    {
        return (int) $decisions->filter(fn (ClientDecision $d) => $d->revision->currency === $currency)
            ->sum(fn (ClientDecision $d) => $d->revision->price_minor);
    }

    private function averageResponseHours(Workspace $workspace, Carbon $since): ?float
    {
        $hours = $this->responseHours($workspace, $since);

        return $hours->isEmpty() ? null : round((float) $hours->avg(), 1);
    }

    private function medianResponseHours(Workspace $workspace): ?float
    {
        $hours = $this->responseHours($workspace, Carbon::now()->subDays(365))->sort()->values();
        $count = $hours->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        return round($count % 2 ? (float) $hours[$middle] : ((float) $hours[$middle - 1] + (float) $hours[$middle]) / 2, 1);
    }

    /**
     * @return Collection<int, float>
     */
    private function responseHours(Workspace $workspace, Carbon $since): Collection
    {
        return $this->decisionsBetween($workspace, $since, Carbon::now())
            ->filter(fn (ClientDecision $d) => $d->changeRequest->sent_at !== null)
            ->map(fn (ClientDecision $d) => $d->changeRequest->sent_at->diffInMinutes($d->decided_at, true) / 60)
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(Workspace $workspace): array
    {
        /** @var array<string, int> $counts */
        $counts = $this->query($workspace)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $counts;
    }
}
