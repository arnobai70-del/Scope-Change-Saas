<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureAdmin::class])->prefix('admin')->name('admin.')->controller(AdminController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/users', 'users')->name('users');
    Route::put('/users/{user}/status', 'updateUserStatus')->name('users.status');
    Route::get('/workspaces', 'workspaces')->name('workspaces');
    Route::put('/workspaces/{workspace}/suspension', 'suspendWorkspace')->name('workspaces.suspension');
    Route::get('/webhooks', 'webhooks')->name('webhooks');
    Route::post('/webhooks/{event}/retry', 'retryWebhook')->name('webhooks.retry');
    Route::get('/tickets', 'tickets')->name('tickets');
    Route::put('/tickets/{ticket}', 'updateTicket')->name('tickets.update');
    Route::get('/flags', 'flags')->name('flags');
    Route::put('/flags', 'toggleFlag')->name('flags.toggle');
    Route::get('/audit', 'audit')->name('audit');
});
