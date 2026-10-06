<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PlanLimitReached extends RuntimeException
{
    public function __construct(public readonly string $limit, string $message)
    {
        parent::__construct($message);
    }

    public function render(Request $request): Response|RedirectResponse
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $this->getMessage(), 'limit' => $this->limit], 402);
        }

        return back()->withErrors(['plan' => $this->getMessage()])->with('upgrade', $this->limit);
    }
}
