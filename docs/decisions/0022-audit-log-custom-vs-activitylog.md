# ADR-0022: Custom Audit Log vs. spatie/laravel-activitylog

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

The admin and moderation feature requires an audit trail for administrative actions: user suspensions, report resolutions, content removals, and other moderation actions taken by admins and moderators. We need to choose between building a custom audit log or using the widely-adopted `spatie/laravel-activitylog` package.

## Decision

We will use a custom `audit_logs` table and a `LogsActivity` trait rather than pulling in `spatie/laravel-activitylog`.

Key aspects of our implementation:
- `audit_logs` table with: `user_id` (nullable FK, actor), `action` (string), `auditable_type/id` (morph), `before/after` (JSON), `ip_address`, `user_agent`, `created_at` (no updates)
- Logging is **explicit in actions**, not automatic via model observers
- The `LogsActivity` trait exposes `logActivity(string $action, array $before, array $after, ?User $actor)` — called deliberately at action sites

## Consequences

**Positive:**
- Zero extra dependency — simpler security surface, no package upgrade cycle to track
- Schema is exactly what we need; no unused columns from a general-purpose library
- Explicit logging prevents accidental performance degradation from surprise auto-logs on bulk operations
- Easy to understand and modify — no package internals to learn

**Negative:**
- More initial code to write vs. drop-in library
- No built-in cleanup / pruning commands — will need custom artisan command if table grows large
- No out-of-the-box UI widgets for displaying activity history (Filament resource handles this for us anyway)

## Alternatives Considered

**1. spatie/laravel-activitylog**
Mature, well-tested package with auto-logging via observers and a full query API. Rejected because: it adds automatic logging to every model save/delete by default (requires careful opt-out), introduces a package dependency, and most of its features (causer tracking, log name scoping) replicate what our simpler implementation already provides.

**2. No audit log**
Rejected. Admin actions on users and content have compliance, accountability, and debugging value. An audit trail is non-negotiable for a moderation system.

## How We'll Know We Got It Wrong

- If we find ourselves re-implementing large portions of activitylog (batch logging, cleanup commands, complex query scopes)
- If the audit_logs table grows to 100M+ rows without a pruning strategy
