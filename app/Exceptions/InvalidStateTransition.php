<?php

namespace App\Exceptions;

use App\Enums\ChangeRequestStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class InvalidStateTransition extends RuntimeException
{
    public function __construct(public readonly ChangeRequestStatus $from, public readonly ChangeRequestStatus $to, ?string $message = null)
    {
        parent::__construct($message ?? "This change request cannot move from {$from->label()} to {$to->label()}.");
    }

    public function render(Request $request): Response|RedirectResponse
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->withErrors(['status' => $this->getMessage()]);
    }
}
