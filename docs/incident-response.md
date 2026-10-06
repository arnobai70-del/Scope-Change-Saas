# Incident response

## Severity

| Level | Examples                                                                                    | Response               |
| ----- | ------------------------------------------------------------------------------------------- | ---------------------- |
| SEV1  | Data exposure across workspaces, approval links usable after revoke, billing double charges | Immediately, all hands |
| SEV2  | Client portal or app down, queue stalled, webhooks failing                                  | Within 1 hour          |
| SEV3  | Single feature broken, reminders delayed                                                    | Next business day      |

## First steps

1. Acknowledge and assign an incident lead.
2. Post status on `/status` and, for SEV1 and SEV2, email affected workspace owners.
3. Preserve evidence: logs, `audit_events`, `webhook_events`. Do not delete rows.

## Playbooks

**Suspected token leak**: revoke links for affected change requests (Change request, Revoke link, or `ApprovalLinkService::revokeAll`), then resend. Old tokens stop working immediately because only hashes are stored.

**Suspected cross-tenant access**: suspend the affected workspace from Admin, Workspaces. Review `audit_events` for the entity ids. Every policy denies with 404; check for raw queries that bypass `forWorkspace`.

**Audit chain broken**: run `AuditTrailService::verifyChain()` for the entity. A false result means a row was modified outside the application; compare with backups.

**Webhook failures**: Admin, Webhooks lists failed events with their error. Fix the cause, then Retry. Duplicates are safe: processing is idempotent.

**Email bounces**: hard bounces and complaints are added to `email_suppressions` by `/webhooks/mail`. Remove an address only on the user's request.

**Compromised admin account**: set the user status to suspended (sessions are deleted), rotate `APP_KEY` only if session forging is suspected, and review the admin audit log.

## After the incident

Write a short post-mortem within five business days: timeline, root cause, impact, what went well, and follow-up actions with owners.
