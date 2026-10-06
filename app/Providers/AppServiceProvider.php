<?php

namespace App\Providers;

use App\Models;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
        $this->configureRateLimiting();
        $this->configureRoutes();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Models use Illuminate\Support\Carbon; services always copy() before
        // modifying a date, so mutability never leaks between callers.
        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureModels(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        // Stable short names in audit and notification tables, independent of class names.
        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'workspace' => Models\Workspace::class,
            'client' => Models\Client::class,
            'project' => Models\Project::class,
            'change_request' => Models\ChangeRequest::class,
            'proof_pack' => Models\ProofPack::class,
            'subscription' => Models\Subscription::class,
            'webhook_event' => Models\WebhookEvent::class,
            'support_ticket' => Models\SupportTicket::class,
            'feature_flag' => Models\FeatureFlag::class,
        ]);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('client-portal', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('client-actions', fn (Request $request) => [
            Limit::perMinute(10)->by('client-action:'.$request->ip()),
            Limit::perHour(30)->by('client-action-cr:'.$request->route('publicId')),
        ]);

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));
    }

    protected function configureRoutes(): void
    {
        Route::pattern('publicId', '[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}');
        Route::pattern('token', '[A-Za-z0-9]{48}');
    }
}
