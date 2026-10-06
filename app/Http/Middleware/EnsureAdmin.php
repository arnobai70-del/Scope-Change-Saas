<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->is_admin) {
            abort(404);
        }

        if (config('security.admin_requires_2fa') && $user->two_factor_confirmed_at === null) {
            return redirect()->route('security.edit')->with('status', 'Admins must enable two-factor authentication before using the admin panel.');
        }

        return $next($request);
    }
}
