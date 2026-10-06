<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $workspace = $this->workspace();

        if ($workspace->onboarded_at !== null) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/Wizard', [
            'workspace' => $workspace->only(['name', 'currency', 'timezone', 'service_type', 'default_payment_url', 'default_payment_instructions']),
            'currencies' => Money::SUPPORTED_CURRENCIES,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(Request $request, AnalyticsService $analytics): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'currency' => ['required', Rule::in(Money::SUPPORTED_CURRENCIES)],
            'timezone' => ['required', 'timezone:all'],
            'service_type' => ['nullable', 'string', 'max:60'],
            'default_payment_url' => ['nullable', 'url:https', 'max:2048'],
            'default_payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $workspace = $this->workspace();
        $this->authorize('manage', $workspace);

        $workspace->fill($data);
        $workspace->onboarded_at = Carbon::now();
        $workspace->save();

        $this->user()->forceFill(['timezone' => $data['timezone']])->save();

        $analytics->track('onboarding_completed', $workspace->id, $this->user()->id, ['currency' => $data['currency']]);

        return to_route('clients.index', ['new' => 1]);
    }
}
