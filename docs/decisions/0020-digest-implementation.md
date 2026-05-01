# ADR-0020: Weekly Digest — Scheduled Job, Not a Separate Queue

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii
**Related:** ADR-0010 (notification preferences), ADR-0019 (observability — no Horizon), feature 07 (notifications)

---

## Context

Some users prefer a single weekly summary email over per-event emails (e.g., active readers who don't want 20 emails from a busy comment thread). The digest needs to:

1. Be opt-in per notification type/channel (controlled via the preferences table)
2. Collect unread notifications from the past 7 days
3. Send one mail per eligible user once a week

Two approaches were considered:

**A. Dedicated `digest = true` mail queue** — when a notification's mail channel has `digest = true`, dispatch a separate job to a `digest` queue. A cron collects queued jobs and batches them.

**B. Scheduled `SendWeeklyDigestJob`** — a single job dispatched weekly via `Schedule::job()`, which queries users with unread notifications and checks their `digest` preference before sending.

---

## Decision

Use approach B: `SendWeeklyDigestJob` runs every Sunday at 08:00 via the scheduler.

The job:
1. Chunks users with unread notifications (100 at a time, memory-safe)
2. Checks if the user has `weekly_digest / mail / enabled = true` (or no override, which defaults to enabled)
3. Sends `WeeklyDigestNotification` with all unread notifications from the past week
4. The notification sends only to the `mail` channel (no database record for the digest itself)

Per-event mail is suppressed for the `digest` users because `RespectsNotificationPreferences::via()` skips the mail channel when `digest = true` on the stored preference.

---

## Consequences

**Positive:**
- Simple: one scheduled job, no extra infrastructure
- Works with the existing sync/database queue setup — no Horizon needed
- Sunday 08:00 is user-visible (predictable for users who know they signed up for digests)
- The digest job is idempotent — re-running it within the same week sends the same notifications

**Negative:**
- The digest does not mark notifications as read — users may receive the same item in next week's digest if they don't visit the app (acceptable: digest is informational, not action-triggering)
- If a user has hundreds of unread notifications the digest email will be long; the current implementation truncates at 10 items
- `withoutOverlapping()` is not set — low risk because the job runs weekly and should finish in seconds at current scale. Re-evaluate if user base grows beyond 10k active users

---

## Alternatives Considered

- **Laravel Horizon dedicated queue** — rejected per ADR-0019; adds operational overhead
- **Dedicated `MailDigest` mailable with `afterCommit()`** — over-engineered for a weekly batch; no clear benefit over a scheduled job

---

## How We'll Know We Got It Wrong

- If users report duplicate digest emails after re-runs, add `withoutOverlapping()` to the schedule
- If digest emails time out because too many users have too many notifications, move to `Bus::batch()` with one job per user
- If users complain the Sunday 08:00 timing doesn't respect their timezone, add timezone support via `HasLocalePreference` on the User model
