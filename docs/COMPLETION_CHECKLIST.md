# Project Completion Checklist

This is the single list that decides when Scope Change is "done". It turns the master blueprint (Definition of Done, release gate and go-live checklist) into checkable items.

- `[x]` means built and covered in the code or tests as of 13 October 2026.
- `[ ]` means still to do. Items marked **(owner)** need the project owner (accounts, money, legal, real users) and cannot be finished in code.

The project counts as **complete** when every item in sections A to E is `[x]`. Section F (Phase 2 and 3) is the roadmap after launch and does not block completion.

## How an AI agent should use this file

1. Read `docs/AI_AGENT_GUIDE.md`, `README.md` and `docs/architecture.md` first.
2. Pick the first unchecked item that is not marked **(owner)**. Work on one item at a time.
3. Build it, add or update tests, and run every quality gate (section A). All must pass.
4. Tick the item here (`[ ]` to `[x]`) in the same commit, and add a short "Verify:" note if the item has none.
5. Never tick an item that is not really finished, and never delete an item. If an item no longer makes sense, leave it unticked and add a note explaining why.
6. Never weaken a test, skip a test or lower a gate to make something pass.

## A. Quality gates (must pass on every change)

- [x] `composer test` passes (Pint check, PHPStan level 7, PHPUnit). Verify: run it locally.
- [x] `npm run check` passes (lint and format). Verify: run it locally.
- [x] `npm run types:check` passes (vue-tsc). Verify: run it locally.
- [x] `npm run build` succeeds.
- [x] GitHub Actions `tests` workflow is green on `main`. Verify: `.github/workflows/tests.yml`, Actions tab.
- [x] Test suite runs on SQLite and MySQL 8.
- [ ] Dependabot also covers `composer` and `npm` (today `.github/dependabot.yml` only updates GitHub Actions), and updates are reviewed weekly.

## B. Core acceptance criteria (blueprint section 31.1)

- [x] A new verified user can create a workspace, client and project, lock the scope and send the first change request without admin help. Verify: onboarding flow, `tests/Feature`.
- [x] A client can approve, decline or ask a question from a mobile browser without creating an account. Verify: `/c/{publicId}/{token}`, client portal tests.
- [x] An approved revision cannot be silently edited; a commercial edit creates revision N+1.
- [x] Revoked, expired and superseded links cannot record a decision.
- [x] Workspace A cannot read or change Workspace B data by changing ids (denied as 404). Verify: tenant isolation tests.
- [x] Every commercial decision creates an audit event (hash-chained) and appears in the Proof Pack.
- [x] Reminders stop after a decision and are never sent twice (unique reminder log).
- [x] Duplicate or out-of-order Paddle webhooks cannot create duplicate subscription or transaction state.
- [x] Plan limits are enforced on the server, not only in the UI.
- [x] Proof Pack files are private and download only through an authorized route.
- [x] No secrets or client text in logs or analytics (analytics uses an allow-list of properties).
- [x] Automated tests cover the critical paths and CI passes.

## C. Non-functional acceptance (blueprint section 31.2)

- [x] Client approval page is server-rendered and light (Blade, no dashboard JavaScript).
- [x] Database timestamps in UTC, shown in the workspace timezone.
- [x] 404 and 403 pages do not reveal whether a private resource exists.
- [x] Queue, webhook and PDF failures are visible in the admin panel and can be retried.
- [ ] Accessibility pass: every form field labelled, full keyboard navigation, colour contrast checked with an automated tool (for example axe) on the main app pages and the client page.
- [ ] Mobile client page tested by hand on real iPhone and Android screen widths. **(owner)**

## D. Product features (MVP scope, blueprint section 3.2)

- [x] Sign up, email verification, password reset, optional 2FA (required for admins).
- [x] Workspace settings: timezone, base currency, logo and brand colour.
- [x] Clients and contacts.
- [x] Projects with versioned, lockable scope baselines (deliverables, exclusions, assumptions, revisions).
- [x] Change requests: price in minor units and 25 currencies, timeline impact, payment rule, expiry, templates.
- [x] Client approval page with approve, decline, question, receipt and external payment link.
- [x] State machine with enforced transitions. Verify: `docs/state-machine.md`.
- [x] Payment tracking: not required, before start, before handoff, custom; marked sent and confirmed.
- [x] Email and in-app notifications, reminders at 24 h, 72 h and 24 h before expiry, mute link.
- [x] PDF Proof Pack with checksum and deterministic content hash.
- [x] Dashboard and reports: pending, approved, declined, expired, revenue protected, approval rate, median time to decision.
- [x] Teams with owner, admin and member roles, invitations and seat limits.
- [x] Billing with Paddle (Free, Solo, Pro, Agency, 14-day Pro trial, customer portal).
- [x] Admin panel: control center, users, workspaces, webhooks, support tickets, feature flags, audit log.
- [x] Admin SEO and analytics page: meta tags, Open Graph image, Google Search Console verification, GA4 id, robots.txt and sitemap.xml.
- [x] Marketing site: home, features, how it works, pricing, segment pages, blog, templates, example approval, legal pages, security, status, subprocessors.
- [x] Free tools: change request generator and scope creep calculator.
- [x] "Powered by" link on client pages (referral loop).
- [x] Privacy: data export (JSON or CSV), deletion requests, IP retention pruning, email suppression list.
- [ ] Error monitoring service connected (for example Sentry or Flare) with alerts.
- [ ] Uptime monitoring on `/up` with alerts.

## E. Go-live (blueprint section 32)

- [ ] Final brand name and domain chosen, trademark search done. **(owner)**
- [ ] Production server set up per `docs/deployment.md` (PHP 8.4, MySQL 8.4, Redis, queue worker, scheduler cron). **(owner)**
- [ ] Domain, HTTPS, DNS and Cloudflare configured; `SECURITY_HSTS=true`. **(owner)**
- [ ] Email sending domain with SPF, DKIM and DMARC verified; bounce and complaint webhook set to `MAIL_WEBHOOK_SECRET`. **(owner)**
- [ ] Paddle live account approved, live price ids in `.env`, webhook secret set, one real low-value subscription tested and refunded. **(owner)**
- [ ] Payout method verified with business details. **(owner)**
- [ ] Privacy, Terms, Refund, DPA, Subprocessors and Security pages reviewed by a qualified lawyer. **(owner)**
- [ ] Cookie and analytics behaviour matches the privacy policy (cookie notice if GA4 is turned on for EU and UK visitors).
- [ ] Production admin created with `php artisan app:make-admin` and 2FA enabled. **(owner)**
- [ ] Backups enabled (database and private storage) and one restore tested. **(owner)**
- [ ] Real client approval pages confirmed `noindex` on the live domain.
- [ ] Onboarding emails tested end to end on the live domain.
- [ ] GA4 id and Search Console verification entered in Admin, SEO and analytics; sitemap submitted to Google Search Console. **(owner)**
- [ ] Support email and contact process ready. **(owner)**
- [ ] Incident response and rollback procedure rehearsed. Verify: `docs/incident-response.md`. **(owner)**
- [ ] 10 to 20 real beta users completed the full workflow. **(owner)**
- [ ] At least 3 permission-based testimonials or case examples. **(owner)**
- [ ] Launch content ready (Product Hunt, LinkedIn, directories). **(owner)**

## F. After launch (Phase 2 and 3, does not block completion)

Build these only after the decision gates in the blueprint (section 30.1) show real demand.

### Marketing and growth features

- [ ] In-app announcements: admin writes a "new feature" message that shows as a banner or notification to all users or chosen plans.
- [ ] Public changelog page (`/changelog`) linked from the app.
- [ ] Marketing email list with explicit opt-in, kept separate from transactional email, with one-click unsubscribe.
- [ ] Referral and affiliate program (tracked links, recurring commission, admin payout report).
- [ ] Embeddable widget: the change request generator or a "Get approval" badge that partner websites can paste as a script or iframe, linking back with UTM tags.
- [ ] UTM capture on signup so reports show which website, ad or campaign brought each customer.
- [ ] Comparison pages with verifiable claims only.

### Product features

- [ ] AI draft of a change request from pasted email or chat (draft only, never auto-send or auto-price).
- [ ] Rate card and line-item estimator.
- [ ] Gmail or Outlook forwarding inbox.
- [ ] Slack and Microsoft Teams notifications.
- [ ] Client e-signature option (after legal review).
- [ ] Custom domain and white-label for Agency plan.
- [ ] Proposal and SOW templates.
- [ ] Public API and outgoing webhooks for customers; Zapier or Make integration.
- [ ] Stripe Connect or direct payment collection (Phase 3).
- [ ] SSO, SAML and SCIM (Phase 3, enterprise).
- [ ] Native mobile apps (only if mobile web usage proves the need).
