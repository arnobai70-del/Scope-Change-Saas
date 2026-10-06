<?php

namespace App\Http\Controllers\App;

use App\Enums\ActorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\WorkspaceSettingsRequest;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\PlanService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function edit(PlanService $plans): Response
    {
        $workspace = $this->workspace();

        return Inertia::render('settings/Workspace', [
            'workspace' => $workspace->only([
                'name', 'currency', 'timezone', 'service_type', 'brand_color', 'default_expiry_days',
                'reminders_enabled', 'default_payment_url', 'default_payment_instructions',
            ]) + ['logo_url' => $workspace->logoUrl()],
            'currencies' => Money::SUPPORTED_CURRENCIES,
            'timezones' => timezone_identifiers_list(),
            'canManage' => $workspace->roleOf($this->user())?->canManageWorkspace() ?? false,
            'remindersOnPlan' => $plans->hasFeature($workspace, 'reminders'),
        ]);
    }

    public function update(WorkspaceSettingsRequest $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);

        $workspace->fill($request->validated())->save();
        $this->audit->record($workspace, 'workspace.settings_updated', ActorType::User, $this->user());
        $this->toast('Workspace settings saved.');

        return back();
    }

    public function logo(Request $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);

        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        if ($workspace->logo_path) {
            Storage::disk('public')->delete($workspace->logo_path);
        }

        $file = $request->file('logo');
        abort_unless($file instanceof UploadedFile, 422);

        $workspace->logo_path = $file->store("logos/{$workspace->id}", 'public') ?: null;
        $workspace->save();

        $this->toast('Logo updated.');

        return back();
    }

    public function notifications(): Response
    {
        return Inertia::render('settings/Notifications', [
            'preferences' => array_merge(User::NOTIFICATION_DEFAULTS, $this->user()->notification_preferences ?? []),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $keys = array_keys(User::NOTIFICATION_DEFAULTS);

        $data = $request->validate(collect($keys)->mapWithKeys(fn ($k) => [$k => ['required', 'boolean']])->all());

        $this->user()->forceFill(['notification_preferences' => array_map('boolval', array_intersect_key($data, array_flip($keys)))])->save();
        $this->toast('Notification preferences saved.');

        return back();
    }
}
