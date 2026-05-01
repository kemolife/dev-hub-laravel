# ADR-0022: Onboarding Step Storage — JSON Column vs Dedicated Table

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub tracks user onboarding progress through a fixed set of steps (profile completed, first post published, first comment left, notification prefs set). We need to persist which steps a user has completed and when onboarding was fully finished.

There are two primary approaches: store completed step keys in a JSON column on the `users` table, or create a separate `user_onboarding_steps` table with one row per step completion.

## Decision

We will store onboarding steps as a JSON array in an `onboarding_steps` column on the `users` table, alongside an `onboarding_completed_at` timestamp.

## Consequences

**Positive:**
- No join required to load onboarding state — it comes "for free" with the user record.
- Simple to implement; no additional model, migration, or relation to manage.
- The step set is small and fixed — there is no operational need to query individual steps across users (e.g. "how many users completed step X") at this stage.
- `onboarding_completed_at` remains a first-class indexed timestamp for queries like "show users who haven't completed onboarding in 7 days."

**Negative:**
- Cannot efficiently query "which users have completed step X" without JSON operators or a full scan.
- No per-step timestamp (we don't know *when* each step was completed, only whether it was).
- If the step set grows significantly, the JSON column becomes unwieldy.

## Alternatives Considered

**1. Dedicated `user_onboarding_steps` table (user_id, step, completed_at).**
Rejected for now. Requires a join on every user load and adds a relation to maintain. The benefit (per-step timestamps, query flexibility) is not needed yet. This is the right refactor if we ever build analytics on step completion rates — at which point an ADR superseding this one should be written.

**2. Bitmask integer column.**
Rejected. Readable only with application-level decoding; hostile to debugging and migrations when steps are added or removed.

## How We'll Know We Got It Wrong

- A product requirement emerges to query "users who completed step X but not Y" efficiently.
- Per-step completion timestamps are needed for analytics or debugging.
- The onboarding step set grows beyond ~10 items.

At that point, migrate to a dedicated table.
