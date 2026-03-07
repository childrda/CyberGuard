# CyberGuard – Domain architecture and event flow

## Domain architecture diagram

```mermaid
flowchart TB
    subgraph tenants["Tenants (e.g. staff / student)"]
        T1[Tenant A: domain, credentials, webhook secret, remediation_policy]
        T2[Tenant B]
    end

    subgraph addon["Gmail Add-on (per tenant)"]
        UI[Report Phish / Report Spam / Mark Safe]
        UI --> WH[POST /api/webhook/report]
    end

    subgraph backend["CyberGuard Backend"]
        WH --> Verify[Verify HMAC, resolve tenant]
        Verify --> RM[ReportedMessage + correlation_id]
        RM --> Match[Match PhishingMessage]
        RM --> SP[ShieldPointsService.award]
        RM --> AL[AuditLog]
    end

    subgraph admin["Admin UI (tenant-scoped)"]
        Switcher[Tenant switcher]
        Nav[Dashboard | Reports | Remediation | Campaigns | Leaderboard | Audit Logs | Settings]
        Reports[Reports: confirm real / false positive]
        Remed[Remediation: approve → run]
        Reports --> Remed
    end

    subgraph remediation["Remediation job"]
        Job[ProcessRemediationJob]
        Job --> ListUsers[List domain users]
        ListUsers --> Items[RemediationJobItem per mailbox]
        Items --> MAL[MailboxActionLog per action]
        MAL --> Status[removed | partially_failed | failed]
    end

    WH --> backend
    admin --> Remed
    Remed --> Job
    tenants --> Verify
    tenants --> Job
```

## Multi-tenant / multi-domain

- **Tenants** represent separate Google Workspace tenants or sub-environments (e.g. staff vs student domains).
- Each tenant has its own: **domain**, **Google credentials**, **webhook secret**, **addon_config**, **campaign_settings**, **reporting_rules**, **remediation_policy**, **audit scope** (all records are tenant-scoped).
- **Tenant switcher** in the admin left sidebar sets the current tenant (session + `app('current_tenant_id')`). All list/create operations are scoped to the current tenant via the `BelongsToTenant` global scope.
- **Webhook** resolves tenant from `X-Tenant-Domain` header, or from the reporter email domain, and verifies signature using that tenant’s `webhook_secret`.

## Event flow (one reported phish)

```
[User] Report in Gmail add-on
    → POST /api/webhook/report (body + X-Phish-Signature, optional X-Tenant-Domain)
    → Resolve tenant by domain
    → Verify HMAC with tenant webhook_secret
    → Create ReportedMessage (tenant_id, correlation_id)
    → Match to PhishingMessage (simulation) if possible
    → Create PhishingReport, optionally PhishingEvent
    → Award shield points (reported_phish) for tenant/user
    → Response: ok, reported_message_id, correlation_id

[Analyst] Review in Admin → Reports
    → Open report → Confirm real phishing or Mark false positive
    → Audit log: report_confirmed_real (tenant_id, correlation_id)

[Analyst] Remediation (if confirmed real)
    → Approve for removal (optional dry run) → Create RemediationJob (status: approved_for_removal)
    → Run remediation → Dispatch ProcessRemediationJob
    → Job: list domain users (Directory API), for each user search by Message-ID, trash (or log if dry run)
    → Create RemediationJobItem per mailbox; create MailboxActionLog per action
    → Update job status: removed | partially_failed | failed
    → Audit log: report_removed_* with correlation
```

## Correlation ID

- **ReportedMessage** gets a UUID `correlation_id` when created.
- **RemediationJob** stores the same `correlation_id` when created from that report.
- **AuditLog** can store `correlation_id` for report/review/remediation events.
- Filtering audit log by `correlation_id` shows the full path: report → review → remediation job → mailbox actions → outcome.

## Remediation workflow statuses

| Status | Description |
|--------|-------------|
| pending_review | Report confirmed as real phish; not yet approved for removal |
| approved_for_removal | Analyst approved; job created, not yet run |
| removal_in_progress | Job queued / running |
| removed | All items processed successfully |
| partially_failed | Some mailboxes failed |
| failed | Job failed |

## Policy modes (tenant.remediation_policy)

- **report_only** – No removal; only log and review.
- **analyst_approval_required** – Removal only after analyst approves (create job → run).
- **auto_remove_confirmed_phish** – (Future) Automatically create and run removal after confirm real.

## Safety and controls

- **Explicit approval**: Mailbox-wide removal requires “Approve for removal” then “Run remediation” unless tenant policy is auto (future).
- **Dry run**: When approving, analyst can check “Dry run”; job then only logs intended actions and does not trash.
- **Rollback-safe**: MailboxActionLog records every action (attempted, result, actor, API summary) before/after execution.
- **RBAC**: Only roles with `updateStatus` / `removeFromMailbox` (e.g. analyst, campaign_admin, superadmin) can approve or run remediation.

## Shield points

- **shield_points_ledger**: Each row = one points event (user_identifier, tenant_id, event_type, points_delta, reason, campaign_id, reported_message_id, created_at).
- Events (examples): `reported_phish` (+10), `simulation_reported` (+5), etc.
- **Leaderboard**: Monthly sum of points_delta by user_identifier per tenant.

## Domain architecture (high level)

```
[Gmail Add-on] (per tenant / domain)
    → POST /api/webhook/report
        → [ReportWebhookController] verify signature (tenant secret), resolve tenant
        → ReportedMessage (tenant_id, correlation_id)
        → ShieldPointsService.award()

[Admin UI] (tenant-scoped via middleware + global scope)
    → Dashboard, Reports, Remediation, Campaigns, Leaderboard, Audit Logs, Settings
    → Tenant switcher → session + app('current_tenant_id')
    → Reports: confirm real / false positive → Remediation: approve → run
    → ProcessRemediationJob: GmailRemovalService (tenant credentials), RemediationJobItem, MailboxActionLog
```
