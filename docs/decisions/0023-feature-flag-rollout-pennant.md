# ADR-0023: Feature Flag Rollout Strategy via Laravel Pennant

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub needs a way to ship code to production incrementally — enabling features for a subset of users, running A/B tests, and protecting unfinished work behind a toggle. The choices are: use a third-party SaaS (LaunchDarkly, Unleash), build our own, or use Laravel's first-party solution.

See also the proposed ADR-0017 in the index (draft, never implemented) — this ADR supersedes it.

## Decision

We will use **Laravel Pennant** (first-party, ships with Laravel 13) for all feature flags. Feature definitions live in `AppServiceProvider::configureFeatureFlags()`. Flags are scoped to `User` models by default. Storage uses Pennant's `database` driver (a single `features` table).

Initial flags and their defaults:

| Flag | Default | Rationale |
|---|---|---|
| `new-editor` | `false` | Unreleased; opt-in only |
| `ai-summaries` | `false` | Requires AI backend, off until ready |
| `recommendations` | `true` | Core discovery feature, on for all users |
| `public-roadmap` | `true` | Open by default, can be toggled off |

The `GET /api/v1/features` endpoint returns the list of active flag names for the current user (or guest).

## Consequences

**Positive:**
- Zero additional SaaS dependency or cost.
- Pennant integrates with Sanctum/Eloquent user scoping out of the box.
- Per-user overrides are supported (`Feature::for($user)->activate(...)`) without code changes.
- Database-backed: flag state survives deployments, is auditable, and can be edited via Tinker or a future admin UI.

**Negative:**
- No built-in targeting rules (% rollout, user segments) without custom resolver logic — must write PHP to express those.
- No dashboard UI for non-developers to toggle flags (would require building one or using Telescope).
- Slightly more ceremony than a simple config file for purely boolean global toggles.

## Alternatives Considered

**1. `config/features.php` with simple boolean values.**
Rejected. Cannot vary per user, cannot be changed without a deployment.

**2. LaunchDarkly / Unleash (SaaS).**
Rejected. Adds external dependency and cost. Overkill for a solo portfolio project. Pennant covers 90% of the same surface with zero overhead.

**3. Gate::define() repurposed as feature flags.**
Rejected. Gates are for authorization, not feature availability. Mixing the two concepts muddies policy logic and hides intent.

## How We'll Know We Got It Wrong

- We need percentage-based rollouts or user-segment targeting that Pennant cannot express cleanly.
- A non-developer team member needs to toggle flags without a deployment — at that point, build a simple admin UI or evaluate Pennant's `array` driver with a DB-backed admin page.
