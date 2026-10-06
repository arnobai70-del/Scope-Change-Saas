<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\ScopeRequest;
use App\Models\Project;
use App\Services\ScopeService;
use Illuminate\Http\RedirectResponse;

class ScopeController extends Controller
{
    public function __construct(private readonly ScopeService $scope) {}

    public function update(ScopeRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        /** @var array{title: string, summary?: string|null, items: list<array{type: string, title: string, detail?: string|null}>} $data */
        $data = $request->validated();
        $wasLocked = $project->currentBaseline()?->isLocked() ?? false;

        $baseline = $this->scope->save($project, $data, $this->user());

        $this->toast($wasLocked ? "Saved as scope version {$baseline->version}. Lock it when the client agrees." : 'Scope saved.');

        return to_route('projects.show', ['project' => $project, 'tab' => 'scope']);
    }

    public function lock(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $baseline = $this->scope->lock($project, $this->user());
        $this->toast("Scope version {$baseline->version} locked.");

        return to_route('projects.show', ['project' => $project, 'tab' => 'scope']);
    }
}
