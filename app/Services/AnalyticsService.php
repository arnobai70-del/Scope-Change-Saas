<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * First-party product analytics. Only opaque IDs and numeric / category
 * values are accepted: client text, emails and payment references must
 * never be sent as properties.
 */
class AnalyticsService
{
    public const EVENTS = [
        'marketing_view', 'signup_started', 'signup_completed', 'email_verified', 'onboarding_completed',
        'client_created', 'project_created', 'scope_locked', 'change_request_created', 'change_request_sent',
        'client_link_viewed', 'client_questioned', 'client_approved', 'client_declined', 'payment_link_clicked',
        'payment_marked_sent', 'payment_confirmed', 'proof_pack_generated', 'upgrade_viewed', 'checkout_started',
        'subscription_activated', 'subscription_canceled', 'subscription_payment_failed', 'workspace_exported',
        'account_deletion_requested', 'free_tool_used',
    ];

    /** Property keys allowed in analytics. Anything else is dropped. */
    private const ALLOWED_PROPERTIES = [
        'plan', 'currency', 'price_minor', 'revision_no', 'payment_rule', 'tool', 'page', 'interval',
        'change_request_id', 'project_id', 'status', 'source', 'response_seconds',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function track(string $event, ?int $workspaceId = null, ?int $userId = null, array $properties = []): void
    {
        if (! in_array($event, self::EVENTS, true)) {
            throw new InvalidArgumentException("Unknown analytics event [{$event}].");
        }

        AnalyticsEvent::query()->create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'event' => $event,
            'properties' => $this->sanitize($properties),
            'occurred_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, int|float|bool|string|null>
     */
    public function sanitize(array $properties): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if (! in_array($key, self::ALLOWED_PROPERTIES, true)) {
                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $clean[$key] = $value;
            } elseif (is_string($value) && preg_match('/^[A-Za-z0-9_\-]{1,40}$/', $value)) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
