<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Services\UsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        $templates = Template::query()
            ->availableTo($this->workspace())
            ->orderByRaw('workspace_id IS NULL')
            ->orderBy('name')
            ->get()
            ->map(fn (Template $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'name' => $t->name,
                'system' => $t->isSystem(),
                'content' => $t->content_json,
            ]);

        return Inertia::render('templates/Index', ['templates' => $templates]);
    }

    public function store(Request $request, UsageLimitService $usage): RedirectResponse
    {
        $usage->ensureFeature($this->workspace(), 'advanced_templates', 'Custom templates are available on the Pro and Agency plans. System templates are free to use.');

        $data = $this->validated($request);

        $template = new Template($data);
        $template->workspace_id = $this->workspace()->id;
        $template->save();

        $this->toast('Template saved.');

        return back();
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->fill($this->validated($request))->save();
        $this->toast('Template updated.');

        return back();
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();
        $this->toast('Template deleted.');

        return back();
    }

    /**
     * @return array{type: string, name: string, content_json: array<string, string|null>}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:change_request,scope'],
            'name' => ['required', 'string', 'max:120'],
            'content.title' => ['nullable', 'string', 'max:160'],
            'content.description' => ['nullable', 'string', 'max:10000'],
            'content.scope_reason' => ['nullable', 'string', 'max:5000'],
            'content.terms_note' => ['nullable', 'string', 'max:3000'],
        ]);

        return [
            'type' => $data['type'],
            'name' => $data['name'],
            'content_json' => [
                'title' => $data['content']['title'] ?? null,
                'description' => $data['content']['description'] ?? null,
                'scope_reason' => $data['content']['scope_reason'] ?? null,
                'terms_note' => $data['content']['terms_note'] ?? null,
            ],
        ];
    }
}
