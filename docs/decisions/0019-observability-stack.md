# ADR-0019: Observability Stack — Sentry + Telescope (no Horizon)

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub needs production-ready observability: error tracking, request debugging, slow query detection, and structured health monitoring. The choices made here affect developer experience, operational cost, and how quickly incidents are diagnosed.

Three tools were evaluated:

1. **Sentry** — hosted error and performance monitoring
2. **Telescope** — Laravel-native request/query/job inspector (dev only)
3. **Horizon** — Laravel queue dashboard and worker manager

The project's queue driver is RabbitMQ (not Redis). Horizon is Redis-only and will not process jobs on a RabbitMQ-backed queue.

---

## Decision

### Sentry

Install `sentry/sentry-laravel` as a production dependency. Configure via `SENTRY_LARAVEL_DSN` (empty in local dev means no-op). Performance tracing sample rate controlled by `SENTRY_TRACES_SAMPLE_RATE` — recommended 1.0 in local, 0.1 in production. Sensitive PII (`send_default_pii`) is off by default.

### Telescope

Install `laravel/telescope` as a `--dev` (dev-only) Composer dependency. Restrict recording to `local` and `testing` environments via the filter in `TelescopeServiceProvider`. In non-local environments, dashboard access is gated to the `admin` gate (which maps to `User::isAdmin()`). This prevents the Telescope database tables from growing in production while keeping the dashboard available for admin debugging on staging.

### Horizon

**Skipped.** Horizon requires a Redis queue driver. DevHub uses RabbitMQ. Installing Horizon would add dead code — the worker processes would never run and the metrics would be empty. This limitation is documented here. If the queue driver ever migrates to Redis, Horizon should be added at that point.

### Health Check Endpoint

Add `GET /health` (not under `/api/v1`) returning:

```json
{
  "status": "ok",
  "timestamp": "2026-05-01T12:00:00+00:00",
  "checks": {
    "database": "ok",
    "cache": "ok"
  }
}
```

Always returns HTTP 200 — load balancers should not flip on transient blips. The JSON body carries per-component status. The existing `/up` endpoint (Laravel built-in) remains for the framework's own health checks.

### Logging

- `slack` channel: level `critical`, uses `LOG_SLACK_WEBHOOK_URL`
- `production` stack: combines `daily` + `slack`
- Set `LOG_STACK=production` in production `.env` to activate

### Slow Query Logging

Register a `DB::listen()` listener in `AppServiceProvider` (local and testing only) that logs queries over 100 ms to the `daily` channel. This surfaces N+1 and missing-index problems during development without any runtime overhead in production.

---

## Consequences

**Positive:**
- Sentry catches unhandled exceptions in production before users report them
- Telescope dramatically speeds up debugging loops during local development
- Health endpoint satisfies load balancer and uptime monitor requirements
- Slow query logging surfaces performance regressions immediately
- Critical log alerts via Slack close the loop between deploy and monitoring

**Negative:**
- Telescope adds migration tables to the database (pruned via `telescope:prune` scheduled command — add to scheduler when needed)
- Sentry DSN must be kept secret and rotated if leaked
- No queue-level visibility without Horizon (RabbitMQ management UI is the fallback)

---

## Alternatives Considered

**1. Laravel Pulse instead of Telescope.**
Pulse is a first-party real-time monitoring dashboard (request latency, exceptions, queues, users). It's production-safe and stores data in Redis. Rejected for now because Telescope has broader debugging coverage (query inspection, mail previews, dumps) which is more valuable on a dev-phase project. Pulse is worth adding once the app is in production under real load. See `docs/ROADMAP.md`.

**2. Inspector.dev / New Relic APM.**
Third-party hosted APM with code-level performance tracing. Rejected: adds a paid subscription and proprietary SDK. Sentry covers the error monitoring requirement; APM can be revisited when load profile justifies it.

**3. Horizon with Redis queue migration.**
Migrating the queue driver to Redis purely to get Horizon would change the production infrastructure. RabbitMQ was chosen deliberately (ADR not yet written) for its routing capabilities. Not worth the trade-off.

---

## How We'll Know We Got It Wrong

- Sentry reports are noisy or missing real errors → tune `ignore_exceptions` and `traces_sample_rate`
- Telescope fills the DB on a staging environment → add `telescope:prune` to the scheduler
- Health endpoint returns `ok` when the DB is actually down → review the `getPdo()` check and consider a lightweight `SELECT 1` query
- Slow query logger floods the `daily` log → raise the threshold from 100 ms or add rate limiting in the listener
