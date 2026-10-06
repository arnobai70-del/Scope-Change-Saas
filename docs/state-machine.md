# Change request state machine

Defined in `App\Enums\ChangeRequestStatus::allowedTransitions()` and enforced by `ChangeRequestService::transition()`, which throws `InvalidStateTransition` for anything else. Every transition writes an audit event with `from` and `to`.

| From                | Allowed next states                                                      |
| ------------------- | ------------------------------------------------------------------------ |
| Draft               | Sent                                                                     |
| Sent                | Viewed, Questioned, Approved, Declined, Expired, Revoked, Draft (revise) |
| Viewed              | Questioned, Approved, Declined, Expired, Revoked, Draft                  |
| Questioned          | Sent (answered), Viewed, Approved, Declined, Expired, Revoked, Draft     |
| Approved            | Payment pending, Ready to start, Draft                                   |
| Payment pending     | Payment marked sent, Payment confirmed, Draft                            |
| Payment marked sent | Payment confirmed, Payment pending                                       |
| Payment confirmed   | Ready to start                                                           |
| Ready to start      | In progress, Completed                                                   |
| In progress         | Completed                                                                |
| Completed           | Proof packed                                                             |
| Declined            | Draft                                                                    |
| Expired, Revoked    | Sent (resend), Draft                                                     |
| Proof packed        | (terminal)                                                               |

## Typical flows

**No payment condition**: Draft → Sent → Viewed → Approved → Ready to start → In progress → Completed → Proof packed.

**Payment before start**: … → Approved → Payment pending → (client marks sent) Payment marked sent → (owner confirms) Payment confirmed → Ready to start.

**Revision**: any sent state → Draft creates revision N+1. Existing links are revoked; the old revision and any decision on it are preserved.

## Client actions and guards

A client can decide only when all of these hold:

- the token hash matches an unrevoked, unexpired link;
- the link belongs to the change request's **current** revision;
- the change request is awaiting the client (Sent, Viewed, Questioned) and not past `expires_at`;
- the submitted `revision_id` equals the current revision;
- no decision exists yet for that revision (unique index).

Decisions are serialised with a cache lock per link, so double clicks produce one decision.
