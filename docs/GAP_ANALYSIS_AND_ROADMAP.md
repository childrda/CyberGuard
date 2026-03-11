# CyberGuard: Gap Analysis & Development Roadmap

This document compares the current CyberGuard codebase and database schema against the product vision in `ai_context.md` (CyberNut-style gamified phishing awareness platform). It identifies what is implemented, what is missing or partial, and proposes a **prioritized development roadmap**.

---

## 1. What Is Already Implemented

### 1.1 Phishing Campaigns
| Vision | Status | Notes |
|--------|--------|--------|
| Create and manage campaigns | ✅ | `PhishingCampaign`, `CampaignController`, templates, attack library |
| Schedule campaigns | ✅ | `scheduled_at`, `window_start`/`window_end`, `scheduled_for` per message |
| Stagger delivery / send window | ✅ | `send_window_start_minute`/`end_minute`, `randomize_send_times`, `throttle_per_minute`, `scheduled_for` on messages |
| Realistic templates | ✅ | `PhishingTemplate`, `PhishingAttack` (subject, body, difficulty, landing type) |
| Target users, groups | ✅ | `PhishingCampaignTarget`: `user`, `group`, `csv`; groups resolved via `GoogleGroupService` (Directory API) |
| Target organizational units | ⚠️ Partial | No OU target type in `resolveTargets()`; User has `ou` field but no OU-based targeting |

### 1.2 Email Reporting
| Vision | Status | Notes |
|--------|--------|--------|
| Report from mailbox | ✅ | Gmail add-on → `POST /api/webhook/report` |
| Reporting as positive action | ✅ | Points awarded on report (`ShieldPointsService.award`) |
| Correlate report to campaign/recipient | ✅ | Match by `message_id` or recipient+subject; `phishing_message_id` on `ReportedMessage`, `PhishingEvent` (reported) |
| Reporting visible in dashboards | ✅ | Admin dashboard top reporters, Reports list, Leaderboard |

### 1.3 Tracking and Outcomes
| Vision | Status | Notes |
|--------|--------|--------|
| delivered, opened, clicked, submitted_data, reported | ✅ | `PhishingEvent`: queued, sent, delivered, opened, clicked, submitted, reported, etc. |
| ignored | ⚠️ Implicit | No explicit "ignored" event; can be inferred from sent but no click/report |
| Unique tracking per campaign/recipient/message | ✅ | `tracking_token` per `PhishingMessage`; links use `/t/{token}` (click, open, submit) |
| Track link rewrites | ✅ | `Mailer::injectTrackingIntoBody` uses `tracking_token`; routes: `/t/{token}`, `/t/{token}/open`, `/t/{token}/submit`, `/t/{token}/capture` |

### 1.4 Training Experience
| Vision | Status | Notes |
|--------|--------|--------|
| Redirect to educational page after click/submit | ✅ | `TrackingController` → training or credential_capture landing |
| Template-specific training content | ✅ | `LandingPage` per template/attack; `TrainingViewController::show` uses it |
| Safe, non-shaming messaging | ✅ | Default content is educational; thanks page exists |
| **Record that user viewed training** | ❌ Missing | No `training_viewed` (or similar) event or points for completing training |

### 1.5 Gamification (Partial)
| Vision | Status | Notes |
|--------|--------|--------|
| Points for behavior | ✅ | `shield_points_ledger`, `ShieldPointsService`; award on `simulation_reported` (+10), `reported_phish` (+10) |
| Leaderboard | ✅ | Admin leaderboard by month, tenant-scoped via `BelongsToTenant` |
| **Badges / achievement levels** | ❌ Missing | No `badges` table or badge logic |
| **Streaks** | ❌ Missing | No streak calculation or storage |
| **Classroom/school challenges** | ❌ Missing | No challenges or competitions |
| **Individual progress dashboard** | ❌ Missing | No end-user “my points / my progress” view |
| **Classroom/school summaries** | ❌ Missing | No OU/classroom aggregation in UI |
| **Anonymized leaderboard option** | ❌ Missing | Leaderboard shows `user_identifier` (email); no anonymized mode |

### 1.6 Dashboards and Reporting
| Vision | Status | Notes |
|--------|--------|--------|
| Campaign results | ✅ | Dashboard: sent, delivered, clicks, reports, submissions, report rate; campaign activity |
| Admin metrics | ✅ | Top reporters, recent reports, remediation, audit log |
| **Individual progress** | ❌ Missing | No student/teacher self-service progress view |
| **Classroom performance** | ❌ Missing | No view by class/OU |
| **School / tenant-wide trends** | ⚠️ Partial | Dashboard is tenant-scoped but not “school” sub-levels |
| **Improvement over time** | ❌ Missing | No trend charts or period-over-period comparison |

### 1.7 Google Workspace Integration
| Vision | Status | Notes |
|--------|--------|--------|
| Mailbox reporting workflow | ✅ | Webhook + add-on; tenant by domain; HMAC verification |
| Secure API use | ✅ | Tenant-level `google_credentials_path`, `google_admin_user`; Gmail removal, Directory (groups) |
| **Syncing users, groups, OUs** | ⚠️ Partial | Groups resolved on-demand for campaigns; no scheduled sync of users/OU into app for targeting or dashboards |

### 1.8 Scoring Engine (Partial)
| Vision | Status | Notes |
|--------|--------|--------|
| Positive: report (+50), training (+100), streak (+200) | ⚠️ Wrong values | Currently +10 for report; no training completion points; no streak points |
| Negative: click (-10), submit (-25) | ❌ Missing | No negative points on click or submit |
| **Scoring period reset / recalculation** | ❌ Missing | No “semester/term” periods or reset; ledger is append-only |

### 1.9 Security and Safety
| Vision | Status | Notes |
|--------|--------|--------|
| Only send to approved tenant domains | ✅ | `DomainGuardService` + `allowed_target_domains` / campaign `allowed_domains` |
| Tenant isolation | ✅ | `tenant_id` on key tables; `BelongsToTenant`; `SetCurrentTenant`; platform admin can switch tenant |
| No storage of real passwords | ✅ | Capture records metadata only; `credential_capture_placeholder` |
| **Block high-risk domains (Gmail, Yahoo, etc.)** | ⚠️ Partial | Only allow-list; no explicit block list for consumer/government domains |
| Audit logging | ✅ | `AuditLog`, `AuditService`; correlation_id for report→remediation |

### 1.10 Multi-Tenant and Entities
| Vision | Status | Notes |
|--------|--------|--------|
| Tenant, User, Campaign, Template, Message, Event, ReportedMessage, AuditLog | ✅ | Present; naming aligns (PhishingCampaign, PhishingMessage, PhishingEvent, etc.) |
| ScoreRecord | ✅ | `shield_points_ledger` |
| TrainingModule | ✅ | `training_modules` table + `LandingPage`; training content via landing pages |
| **Badge** | ❌ Missing | No table or model |

---

## 2. Summary: Implemented vs Missing

**Strong areas:** Campaigns (create, schedule, stagger, templates, attacks), reporting from mailbox and correlation, event tracking (delivered/opened/clicked/submitted/reported), unique tracking tokens and link rewrites, training redirect and template-based landing pages, points ledger and admin leaderboard, tenant isolation, Google group resolution and webhook, remediation workflow, audit log.

**Gaps (high impact for “CyberNut-style” experience):**
- **Scoring engine:** Vision-aligned point values (+50 report, +100 training, -10 click, -25 submit), negative events, training-completion event, streaks, and scoring periods (e.g. semester reset).
- **Gamification:** Badges, streaks, challenges, individual progress dashboard, classroom/school views, optional anonymized leaderboard.
- **Training:** Record “training viewed” and award points; optionally highlight phishing indicators in training content.
- **Dashboards:** Individual (end-user) progress; classroom/school performance; improvement-over-time.
- **End-user experience:** No portal for students/teachers to see their points, badges, or history.
- **Google:** No sync of users/OUs for targeting and reporting (only on-demand group resolution).
- **Safety:** Explicit block list for high-risk external domains.

---

## 3. Prioritized Development Roadmap

The roadmap is ordered to maximize progress toward a gamified, educational platform while building on existing features. Priorities align with *ai_context.md*: gamification as core, reporting as primary workflow, safety for K–12, and rewarding improvement.

---

### Phase 1: Scoring Engine & Training Completion (Foundation)

**Goal:** Align scoring with the vision and record training completion so that gamification and dashboards have correct data.

1. **Scoring rules implementation**
   - Introduce a configurable or code-based scoring rules layer (e.g. `config/phishing.php` or `ScoringRule` model).
   - Set vision-aligned defaults: report simulation +50, report real (analyst confirmed) +50, training completed +100, streak (e.g. multi-month no-click) +200; click -10, submit -25.
   - Replace hardcoded +10 in `ReportWebhookController` and `ReportedMessageController` with rule-based points.
   - **Negative points:** In `TrackingController::click` and `TrackingController::submit`, after creating `PhishingEvent`, call `ShieldPointsService::award` with negative deltas for the recipient (identify by `PhishingMessage::recipient_email` and tenant).

2. **Training viewed event and points**
   - When user lands on training (e.g. `TrainingViewController::show` or “Continue” to thanks), record a `training_viewed` (or `training_completed`) event (e.g. new `PhishingEvent` type or dedicated table if you prefer).
   - Award +100 (or configured value) for “training completed” for that message/campaign, once per message (idempotent).

3. **Scoring period (optional in Phase 1)**
   - Add `scoring_period` (e.g. semester/term name or date range) to `shield_points_ledger` or a summary table so that “current term” leaderboards and resets can be supported later without schema churn.

**Deliverables:** Correct +/- points for report, click, submit, training; training completion visible to scoring; foundation for streaks and period-based resets.

---

### Phase 2: Gamification Core (Badges, Streaks, Leaderboard)

**Goal:** Add badges and streaks so the platform feels rewarding and encourages long-term behavior.

4. **Badges**
   - New migration: `badges` table (e.g. `tenant_id`, `name`, `slug`, `description`, `criteria_type`, `criteria_config` (JSON), `icon`/`image`, `order`).
   - New migration: `user_badges` or `badge_user` (e.g. `user_identifier`, `badge_id`, `tenant_id`, `awarded_at`).
   - Badge criteria examples: “First report”, “5 reports in a month”, “Training completed”, “No clicks for 30 days”, “Streak 3 months”.
   - Service: `BadgeService` that evaluates criteria against ledger/events and awards badges (idempotent). Run on point award and/or nightly job.
   - Expose in API or future user dashboard: badges earned by user.

5. **Streaks**
   - Define “streak” (e.g. “consecutive months with at least one report and no clicks” or “no clicks for N days”).
   - Store computed streak (e.g. `user_streaks` table or on a user/tenant summary): `user_identifier`, `tenant_id`, `streak_type`, `current_streak`, `last_updated`.
   - On each relevant event (report, click, etc.), or via scheduled job, recalculate and award streak bonus (e.g. +200 for multi-month).
   - Use streak in leaderboard or “my progress” (Phase 3).

6. **Leaderboard improvements**
   - Add optional **anonymized** mode: tenant setting (e.g. `leaderboard_anonymous`) so that for younger students, leaderboard shows “User #1”, “User #2” or similar instead of email.
   - Ensure leaderboard is strictly tenant-scoped and, if needed, filter by `scoring_period` when that exists.

**Deliverables:** Badges and streaks in DB and logic; optional anonymized leaderboard; ready for individual progress UI.

---

### Phase 3: Individual & Group Dashboards (End-User and Admin)

**Goal:** Let users see their own progress; let admins see classroom/school breakdowns.

7. **End-user progress portal**
   - Routes and auth: e.g. `/dashboard` or `/my-progress` for logged-in users (students/teachers) in a tenant.
   - Page: current period points, total points, badges earned, streak, recent activity (reports, training completed). Use `user_identifier` = email (or linked `User`) and tenant from session.
   - Optional: link from “Report Phish” confirmation or post-training page (“View your progress”).

8. **Classroom / school dashboards**
   - Use `User.department` / `User.ou` (or synced OU from Google) to aggregate: points and report rate by OU/class/school.
   - Admin views: “By classroom” and “By school” (if you have school_id or OU hierarchy). New Blade views and controller methods, tenant-scoped.
   - Data: reuse `ShieldPointsLedger` and `PhishingEvent`; join to users by email and group by `ou` or `department`.

9. **Improvement over time**
   - Simple “improvement” metrics: e.g. report rate last 30 days vs previous 30 days; click rate trend. Add to admin dashboard and optionally to end-user view.
   - Store or compute per period (e.g. per month) so you can show “improved” vs “needs attention” without heavy ad-hoc queries.

**Deliverables:** “My progress” for end users; classroom/school views for admins; basic improvement metrics.

---

### Phase 4: Safety, Targeting & Google Sync

**Goal:** Harden safety and improve targeting and reporting without changing core flows.

10. **Block high-risk domains**
    - In `DomainGuardService` (or config), add an explicit **block list**: Gmail, Yahoo, Outlook, government domains, etc. Reject any recipient whose domain is on the block list even if it were in an allow list (or never allow those domains).
    - Apply in `PhishingCampaignService::launchCampaign` and anywhere else recipients are validated.

11. **OU targeting**
    - Add target type `ou` (organizational unit) to `PhishingCampaignTarget`. Resolve OU members via Google Directory API (list users in OU) in a new method, e.g. `GoogleDirectoryService::listUsersInOu($tenant, $ouId)`, and use it in `PhishingCampaignService::resolveTargets`.

12. **Google user/OU sync (optional but valuable)**
    - Scheduled job: sync users (and optionally OUs) from Google Directory into `users` table or a `synced_users` table (tenant_id, email, name, ou, department). Use for: OU dropdown in campaign targeting, classroom dashboards, and consistent `user_identifier` resolution.
    - This is a larger feature; can start with “list users in OU” for targeting (step 11) and add full sync later.

**Deliverables:** Safer targeting; OU-based campaigns; optional user/OU sync for better UX and reporting.

---

### Phase 5: Polish & Engagement

**Goal:** Seasonal challenges, better training content, and period reset so the product feels complete and school-friendly.

13. **Scoring period reset / recalculation**
    - Admin UI: define scoring periods (e.g. “Fall 2025”, “Spring 2026”) with date range. Option to “start new period” (no deletion; new period name attached to new ledger entries).
    - Leaderboard and “my progress” filter by selected period. Optional: “Reset points for new term” that archives or zeroes display for a new period (business rule: e.g. keep ledger but show only period totals).

14. **Challenges**
    - Table: `challenges` (tenant_id, name, type: classroom/school/tenant, period, goal e.g. “Report rate > 80%”, “Most improved”). Link to leaderboard or a “Challenges” page. Can be Phase 5 or post-MVP.

15. **Training content quality**
    - Enrich landing/training pages to “highlight phishing indicators” (e.g. template fields that inject sender, link URL, red flags from the actual email). Requires passing minimal safe context (e.g. “suspicious link”, “sender not in org”) into the training view.
    - Keep experience positive and educational; avoid shaming.

**Deliverables:** Period-based scoring and resets; optional challenges; improved training content with indicators.

---

## 4. Roadmap Overview (Priority Order)

| Phase | Focus | Key deliverables |
|-------|--------|-------------------|
| **1** | Scoring & training | Vision-aligned +/- points; training viewed event + points; scoring period support |
| **2** | Gamification core | Badges, streaks, anonymized leaderboard option |
| **3** | Dashboards | End-user “my progress”; classroom/school views; improvement over time |
| **4** | Safety & targeting | Block high-risk domains; OU targeting; optional user/OU sync |
| **5** | Polish | Scoring period reset; challenges; training content with indicators |

---

## 5. Database and Entity Additions (Summary)

- **Already present:** tenants, users, roles, phishing_templates, phishing_campaigns, phishing_campaign_targets, phishing_messages, phishing_events, phishing_attacks, campaign_attack, reported_messages, phishing_reports, landing_pages, training_modules, shield_points_ledger, user_risk_scores, audit_logs, remediation tables, api_keys_webhook_secrets.
- **To add:** `badges`, `user_badges` (or pivot), optional `user_streaks` (or equivalent), optional `scoring_periods`, optional `challenges` / `challenge_participants`; extend `shield_points_ledger` or add summary table for `scoring_period` if needed.

---

## 6. References

- **Product vision:** `ai_context.md`
- **Architecture:** `docs/ARCHITECTURE.md`
- **API:** `docs/API.md`
- **Current scoring:** `App\Services\ShieldPointsService`; `ReportWebhookController`; `ReportedMessageController`; `TrackingController` (no points today for click/submit/training).

Implementing **Phase 1** and **Phase 2** will move CyberGuard closest to a CyberNut-style gamified phishing awareness platform with correct scoring, training recognition, badges, and streaks, while keeping the existing campaign and reporting workflows intact.
