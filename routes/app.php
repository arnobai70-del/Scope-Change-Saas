<?php

use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\ChangeRequestController;
use App\Http\Controllers\App\ClientController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DataController;
use App\Http\Controllers\App\InvitationController;
use App\Http\Controllers\App\NotificationController;
use App\Http\Controllers\App\OnboardingController;
use App\Http\Controllers\App\ProjectController;
use App\Http\Controllers\App\ProofPackController;
use App\Http\Controllers\App\ReportController;
use App\Http\Controllers\App\ScopeController;
use App\Http\Controllers\App\TeamController;
use App\Http\Controllers\App\TemplateController;
use App\Http\Controllers\App\WorkspaceSettingsController;
use App\Http\Controllers\App\WorkspaceSwitchController;
use App\Http\Middleware\EnsureWorkspace;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;

/*
| Authenticated application (tenant scoped).
*/
Route::get('/dashboard', RedirectController::class)->defaults('destination', '/app/dashboard')->defaults('status', 302);

Route::middleware(['auth', 'verified'])->get('/invitations/{token}', InvitationController::class)
    ->middleware('throttle:10,1')
    ->name('invitations.accept');

Route::middleware(['auth', 'verified', EnsureWorkspace::class])->prefix('app')->group(function () {
    Route::get('/', RedirectController::class)->defaults('destination', '/app/dashboard')->defaults('status', 302);
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/workspaces/{workspace}/switch', WorkspaceSwitchController::class)->name('workspaces.switch');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::post('/clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
    Route::post('/clients/{client}/contacts', [ClientController::class, 'storeContact'])->name('clients.contacts.store');
    Route::delete('/clients/{client}/contacts/{contact}', [ClientController::class, 'destroyContact'])->name('clients.contacts.destroy');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::put('/projects/{project}/scope', [ScopeController::class, 'update'])->name('projects.scope.update');
    Route::post('/projects/{project}/scope/lock', [ScopeController::class, 'lock'])->name('projects.scope.lock');

    Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
    Route::get('/change-requests/create', [ChangeRequestController::class, 'create'])->name('change-requests.create');
    Route::post('/change-requests', [ChangeRequestController::class, 'store'])->name('change-requests.store');
    Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');
    Route::get('/change-requests/{changeRequest}/edit', [ChangeRequestController::class, 'edit'])->name('change-requests.edit');
    Route::put('/change-requests/{changeRequest}', [ChangeRequestController::class, 'update'])->name('change-requests.update');
    Route::delete('/change-requests/{changeRequest}', [ChangeRequestController::class, 'destroy'])->name('change-requests.destroy');
    Route::get('/change-requests/{changeRequest}/preview', [ChangeRequestController::class, 'preview'])->name('change-requests.preview');
    Route::post('/change-requests/{changeRequest}/send', [ChangeRequestController::class, 'send'])->middleware('throttle:20,1')->name('change-requests.send');
    Route::post('/change-requests/{changeRequest}/revoke', [ChangeRequestController::class, 'revoke'])->name('change-requests.revoke');
    Route::post('/change-requests/{changeRequest}/revise', [ChangeRequestController::class, 'revise'])->name('change-requests.revise');
    Route::post('/change-requests/{changeRequest}/comments', [ChangeRequestController::class, 'comment'])->name('change-requests.comment');
    Route::post('/change-requests/{changeRequest}/payment/confirm', [ChangeRequestController::class, 'confirmPayment'])->name('change-requests.payment.confirm');
    Route::post('/change-requests/{changeRequest}/start', [ChangeRequestController::class, 'start'])->name('change-requests.start');
    Route::post('/change-requests/{changeRequest}/complete', [ChangeRequestController::class, 'complete'])->name('change-requests.complete');
    Route::post('/change-requests/{changeRequest}/proof-pack', [ProofPackController::class, 'store'])->middleware('throttle:10,1')->name('change-requests.proof-pack');

    Route::get('/proof-packs', [ProofPackController::class, 'index'])->name('proof-packs.index');
    Route::get('/proof-packs/{proofPack}/download', [ProofPackController::class, 'download'])->name('proof-packs.download');

    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/reports', ReportController::class)->name('reports.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invitations', [TeamController::class, 'invite'])->middleware('throttle:20,1')->name('team.invite');
    Route::delete('/team/invitations/{invitation}', [TeamController::class, 'cancelInvitation'])->name('team.invitations.cancel');
    Route::put('/team/members/{member}', [TeamController::class, 'updateRole'])->name('team.members.update');
    Route::delete('/team/members/{member}', [TeamController::class, 'remove'])->name('team.members.remove');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

    Route::get('/settings', RedirectController::class)->defaults('destination', '/app/settings/workspace')->defaults('status', 302)->name('app.settings');
    Route::get('/settings/workspace', [WorkspaceSettingsController::class, 'edit'])->name('workspace-settings.edit');
    Route::put('/settings/workspace', [WorkspaceSettingsController::class, 'update'])->name('workspace-settings.update');
    Route::post('/settings/workspace/logo', [WorkspaceSettingsController::class, 'logo'])->name('workspace-settings.logo');
    Route::get('/settings/notifications', [WorkspaceSettingsController::class, 'notifications'])->name('notification-settings.edit');
    Route::put('/settings/notifications', [WorkspaceSettingsController::class, 'updateNotifications'])->name('notification-settings.update');
    Route::get('/settings/data', [DataController::class, 'index'])->name('data.index');
    Route::post('/settings/data/export', [DataController::class, 'export'])->middleware('throttle:5,10')->name('data.export');
    Route::get('/settings/data/exports/{export}', [DataController::class, 'download'])->name('data.download');
    Route::post('/settings/data/deletion', [DataController::class, 'requestDeletion'])->name('data.deletion');
});
