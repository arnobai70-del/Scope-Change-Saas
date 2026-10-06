<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Support\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

abstract class Controller
{
    use AuthorizesRequests;

    protected function workspace(): Workspace
    {
        return app(TenantContext::class)->workspace();
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
