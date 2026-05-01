# ADR-0016: Plan Limits in Config File, Not Database

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub has three plans: Free, Pro, and Pro Annual. Each plan has associated limits (posts per month, API access, follower cap). These limits need to be readable at runtime to enforce feature gates.

Two storage approaches were considered:
1. **Config file** (`config/plans.php`) — static, version-controlled, deployed with code
2. **Database table** (`plans`) — dynamic, editable at runtime without a deploy

## Decision

We will store plan definitions in **`config/plans.php`**, not a database table.

## Consequences

**Positive:**
- Plan structure is visible in code review and git history — changes are auditable.
- No database query needed to read plan limits; they're available via `config('plans.pro')` in memory.
- Removing plans or changing limits requires a deploy, which is the right forcing function (you want a deploy for pricing changes — they affect revenue).
- Zero migration needed; no risk of out-of-sync database state between environments.
- Simplifies testing: no factories or seeders needed for plan data.
- Config files are compatible with `config:cache` for zero-overhead reads in production.

**Negative:**
- Adding a new plan or changing limits requires a code deploy and config cache clear, not a database update.
- If DevHub ever needs admin-editable plans at runtime (e.g., sales team customizing enterprise plans), this approach won't work without a migration to a DB approach.
- Feature flags per-plan (e.g., "enable feature X for this plan on this account only") require a hybrid approach.

## Alternatives Considered

**1. Database table with a `Plan` model**
Rejected for now. DevHub has exactly three plans with no runtime editing requirement. Adding a DB table, migration, model, factory, and seeder to serve three static records is YAGNI. If plans become dynamic, this is an easy migration: extract config values to a seeder and add a `Plan` model.

**2. Enum with hardcoded limits**
Rejected. PHP enum cases can't hold complex structured data cleanly (nested arrays). Config files are more readable and easier to change without recompiling or touching an enum file.

**3. Environment variables per plan limit**
Rejected. Env vars are for credentials and environment-specific settings, not business logic. Plan limits are the same across all environments.

## How We'll Know We Got It Wrong

- We need to edit plan limits more than once per quarter (sign of needing DB).
- We add an enterprise tier with per-account custom limits (sign of needing a DB + per-user overrides).
- The marketing or sales team asks for runtime plan configuration without code deploys.
