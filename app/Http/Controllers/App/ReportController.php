<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use App\Services\PlanService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(MetricsService $metrics, PlanService $plans): Response
    {
        $workspace = $this->workspace();
        $full = $plans->hasFeature($workspace, 'analytics');

        return Inertia::render('reports/Index', [
            'report' => $metrics->report($workspace, $full ? 12 : 3),
            'fullAnalytics' => $full,
        ]);
    }
}
