# Architecture

## Overview

A single Laravel 13 application serves three surfaces:

1. **Marketing site** (`routes/web.php`): Blade views, server-rendered for SEO, no JavaScript needed.
2. **Application** (`routes/app.php`, `/app/*`): Inertia + Vue 3 pages behind `auth`, `verified` and `EnsureWorkspace`.
3. **Client portal** (`routes/client.php`, `/c/*`): Blade views reached only through an opaque approval token. No login.

Webhooks live in `routes/webhooks.php` (CSRF-exempt, signature-verified) and the operations panel in `routes/admin.php`.

## Multi-tenancy

- Every tenant-owned table has a `workspace_id`.
- `App\Support\TenantContext` is a scoped singleton. `EnsureWorkspace` resolves the user's current workspace (falling back to the first membership, or creating one) and sets it for the request.
- Queries use `Model::forWorkspace($workspace)` (from `BelongsToWorkspace`).
- Policies extend `TenantPolicy` and deny as **404**, so another tenant's ids are indistinguishable from missing ones.
- Form requests validate foreign ids (client, scope items) against the current workspace.

## Domain model

```
Workspace ─┬─ Client ── Project ─┬─ ScopeBaseline (versioned, lockable) ── ScopeItem
           │                     └─ ChangeRequest ─┬─ ChangeRequestRevision (immutable once locked)
           │                                       │      └─ ClientDecision (unique per revision)
           │                                       ├─ ApprovalLink (hashed token, per revision)
           │                                       ├─ ChangeComment (shared or internal)
           │                                       ├─ PaymentRecord (unique per revision)
           │                                       ├─ ReminderLog (unique per kind)
           │                                       └─ ProofPack (versioned PDF)
           ├─ Subscription ── SubscriptionItem, Transaction
           └─ UsageCounter, Template, DataExport, DeletionRequest
AuditEvent (append-only, hash-chained per entity)
```

## Key rules

- **Money** is stored as integer minor units with an ISO 4217 code. `App\Support\Money` uses ICU fraction digits (JPY 0, USD 2, KWD 3) and rejects negative or over-precise input.
- **Immutability**: sending locks the current revision and stores a JSON snapshot plus its SHA-256. Updating or deleting a locked revision, locked scope baseline, client decision or audit event throws `LogicException`. Changes after sending create revision N+1.
- **Audit trail**: each event stores `previous_hash` and `hash = sha256(payload)` where the payload includes the previous hash. Metadata keys are sorted before hashing so the chain verifies on MySQL JSON columns. `AuditTrailService::verifyChain()` detects tampering.
- **Usage limits** are checked in services (`UsageLimitService`) and counted in `usage_counters`, which are never decremented, so deleting drafts does not reset the monthly allowance.
- **Notifications** are queued and dispatched after commit, skip suppressed addresses and respect per-user email preferences.
- **Analytics** are first-party (`analytics_events`), with a fixed event list and an allow-list of property keys. No client content is recorded.

## Frontend

- Pages live in `resources/js/pages`, grouped by area. Shared app components are in `resources/js/components/app`.
- Layouts are chosen in `resources/js/app.ts`: `auth/*` uses the auth layout, `settings/*` the settings layout, `onboarding/*` and `errors/*` are full-screen, everything else uses the sidebar layout.
- Shared Inertia props (`HandleInertiaRequests`): user, workspace (plan, role, trial), unread notification count and flash messages.
