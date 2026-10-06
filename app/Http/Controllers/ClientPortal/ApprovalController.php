<?php

namespace App\Http\Controllers\ClientPortal;

use App\Enums\ActorType;
use App\Enums\ClientDecisionType;
use App\Http\Controllers\Controller;
use App\Models\ApprovalLink;
use App\Models\ChangeRequest;
use App\Services\AnalyticsService;
use App\Services\ApprovalLinkService;
use App\Services\AuditTrailService;
use App\Services\ChangeRequestService;
use App\Services\PlanService;
use App\Support\Idempotency;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, no-login client approval page. Every action requires the opaque
 * token from the emailed link; only its hash is stored server-side.
 */
class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalLinkService $links,
        private readonly ChangeRequestService $service,
        private readonly PlanService $plans,
    ) {}

    public function show(string $publicId, string $token): View|Response
    {
        $link = $this->resolve($publicId, $token);
        $changeRequest = $link->changeRequest;

        if ($this->canDecide($link)) {
            $this->service->recordView($link);
            $changeRequest->refresh();
        } elseif (! $this->hasDecision($link)) {
            return $this->inactive($changeRequest);
        }

        return $this->render($link, $token);
    }

    public function approve(Request $request, string $publicId, string $token): RedirectResponse
    {
        return $this->decide($request, $publicId, $token, ClientDecisionType::Approved);
    }

    public function decline(Request $request, string $publicId, string $token): RedirectResponse
    {
        return $this->decide($request, $publicId, $token, ClientDecisionType::Declined);
    }

    public function comment(Request $request, string $publicId, string $token): RedirectResponse
    {
        $link = $this->resolve($publicId, $token);

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:120'],
            'client_email' => ['required', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:'.config('security.comment_max_length', 5000)],
            'website' => ['prohibited'], // honeypot
        ]);

        $this->service->clientComment($link, $data['client_name'], $data['client_email'], strip_tags($data['body']));

        return redirect()->route('client.show', [$publicId, $token])
            ->with('status', 'Your question was sent. You will get a reply by email.');
    }

    public function paymentSent(Request $request, string $publicId, string $token): RedirectResponse
    {
        $link = $this->resolve($publicId, $token);
        $data = $request->validate(['reference' => ['nullable', 'string', 'max:120']]);

        $this->service->clientMarkPaymentSent($link, $data['reference'] ?? null);

        return redirect()->route('client.show', [$publicId, $token])
            ->with('status', 'Thanks. We let the sender know your payment is on its way.');
    }

    /**
     * Track the click and forward to the user's own payment page. The
     * platform never handles the client's money.
     */
    public function pay(string $publicId, string $token, AnalyticsService $analytics): RedirectResponse
    {
        $link = $this->resolve($publicId, $token);
        $payment = $link->changeRequest->currentPayment();

        abort_if($payment === null || blank($payment->external_url) || $link->revoked_at !== null, 404);

        $analytics->track('payment_link_clicked', $link->changeRequest->workspace_id, null, ['change_request_id' => $link->change_request_id]);

        return redirect()->away((string) $payment->external_url);
    }

    public function receipt(string $publicId, string $token): View|Response
    {
        $link = $this->resolve($publicId, $token);

        if (! $this->hasDecision($link)) {
            return redirect()->route('client.show', [$publicId, $token]);
        }

        return $this->render($link, $token, receipt: true);
    }

    public function muteReminders(Request $request, string $publicId, AuditTrailService $audit): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $changeRequest = ChangeRequest::query()->where('public_id', $publicId)->firstOrFail();

        if ($changeRequest->reminders_muted_at === null) {
            $changeRequest->forceFill(['reminders_muted_at' => Carbon::now()])->save();
            $audit->record($changeRequest, 'reminder.muted', ActorType::Client, $changeRequest->recipient_email);
        }

        return view('client.message', [
            'title' => 'Reminders stopped',
            'message' => 'You will not receive more reminders about '.$changeRequest->reference.'. The sender can still contact you directly.',
        ]);
    }

    private function decide(Request $request, string $publicId, string $token, ClientDecisionType $type): RedirectResponse
    {
        $link = $this->resolve($publicId, $token);

        $data = $request->validate([
            'revision_id' => ['required', 'integer'],
            'client_name' => ['required', 'string', 'max:120'],
            'client_email' => ['required', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => $type === ClientDecisionType::Approved ? ['accepted'] : ['nullable'],
            'website' => ['prohibited'],
        ], [
            'accept_terms.accepted' => 'Please confirm you approve this change and its price, timeline and payment terms.',
        ]);

        // Serialise double-clicks; the unique decision per revision is the final guard.
        Idempotency::run('decision:'.$link->id, fn () => $this->service->decide($link, (int) $data['revision_id'], $type, [
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'],
            'reason' => isset($data['reason']) ? strip_tags($data['reason']) : null,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]));

        return redirect()->route('client.receipt', [$publicId, $token]);
    }

    private function resolve(string $publicId, string $token): ApprovalLink
    {
        $link = $this->links->resolve($publicId, $token);

        abort_if($link === null, 404);

        if ($link->revoked_at !== null && ! $this->hasDecision($link)) {
            abort(410, 'This approval link is no longer active.');
        }

        return $link;
    }

    private function canDecide(ApprovalLink $link): bool
    {
        $cr = $link->changeRequest;

        return $link->isUsable()
            && $cr->status->awaitingClient()
            && ! $cr->isExpiredByDate()
            && $link->revision_id === $cr->current_revision_id;
    }

    private function hasDecision(ApprovalLink $link): bool
    {
        return $link->changeRequest->decisions()->where('revision_id', $link->revision_id)->exists();
    }

    private function inactive(ChangeRequest $changeRequest): Response
    {
        return response()->view('client.message', [
            'title' => 'This link is no longer active',
            'message' => 'The approval window for '.$changeRequest->reference.' has closed or the request was updated. Please ask '.$changeRequest->workspace->name.' to send a fresh link.',
        ], 410);
    }

    private function render(ApprovalLink $link, string $token, bool $receipt = false): View
    {
        $changeRequest = $link->changeRequest;
        $revision = $link->revision()->with('scopeItems')->firstOrFail();
        $workspace = $changeRequest->workspace;
        $decision = $revision->decision;
        $payment = $changeRequest->paymentRecords()->where('revision_id', $revision->id)->first();

        return view('client.show', [
            'changeRequest' => $changeRequest,
            'revision' => $revision,
            'workspace' => $workspace,
            'project' => $changeRequest->project,
            'comments' => $changeRequest->comments()->where('visibility', 'shared')->get(),
            'decision' => $decision,
            'payment' => $payment,
            'canDecide' => ! $receipt && $this->canDecide($link),
            'preview' => false,
            'receipt' => $receipt,
            'token' => $token,
            'showBranding' => ! $this->plans->hasFeature($workspace, 'remove_branding'),
            'printUrl' => URL::current(),
        ]);
    }
}
