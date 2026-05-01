# ADR-0010: Notification Preference Granularity — Per-Type, Per-Channel

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii
**Related:** PRODUCT.md (calm, user-respecting product), feature 07 (notifications)

---

## Context

DevHub needs to send notifications (comment replies, mentions, new followers, weekly digest) across multiple channels (database, mail). Users should have meaningful control over what they receive and how.

Three models were considered:

1. **Global on/off** — one boolean per user. Simple but too coarse. Can't disable emails while keeping in-app notifications.
2. **Per-type on/off** — one row per notification type. Allows disabling entire categories but not channel-specific control.
3. **Per-type, per-channel** — one row per `(user_id, type, channel)` combination. Full control without magic.

The difference between options 2 and 3 matters in practice: a user might want `new_comment_on_post` in-app (live bell icon) but not by email — option 2 would silence both or neither.

---

## Decision

Store preferences as rows in `notification_preferences` with a `(user_id, type, channel)` unique constraint.

- `type` maps to `NotificationType` enum values
- `channel` maps to `NotificationChannel` enum values
- `enabled` boolean with DB default `true` (absent = enabled, only store overrides)
- `digest` boolean: when true for mail channel, the regular per-event email is skipped; the weekly digest job picks it up instead

The `RespectsNotificationPreferences` trait is used by all notification classes to resolve channels at dispatch time by querying the preferences table. Defaults are defined in `NotificationType::defaultChannels()`.

---

## Consequences

**Positive:**
- Complete user control with no magic string lookups
- Enum-backed — impossible to store invalid type/channel combinations
- Absent preferences default to enabled — no migration needed when new types are added
- Digest mode is a first-class preference, not a hack

**Negative:**
- One DB query per notification dispatch to check preferences (acceptable — notifications are already async/queued)
- More rows than a global flag, but the table will stay small (O(users × types × channels))
- The `index` endpoint must synthesize defaults for unset preferences — slightly more complex controller logic

---

## Alternatives Considered

- **JSON blob on users table** — simple to read/write but no indexing, harder to query for digest recipients, no enum safety
- **Horizon + per-channel queues** — rejected because we don't use Horizon (see ADR-0019); Horizon adds operational overhead that isn't justified at current scale
- **Notification flags on users table columns** — rejected as it would pollute the users table and require a migration per new notification type

---

## How We'll Know We Got It Wrong

- If preference checks are showing up as slow queries in the query log, we need to cache them per-request (e.g., `once()` memoization)
- If users are confused by the granularity, collapse to per-type on the API surface without changing the DB schema
- If the preferences table exceeds 1M rows before 100k users, revisit the "only store overrides" approach
