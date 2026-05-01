# Prompt 14 — Observability & Production Readiness

What makes the project actually deployable and debuggable. Senior signal.

## Concepts demonstrated

- Structured logging
- Telescope (dev only)
- Sentry / error monitoring
- Horizon for queues
- Performance monitoring
- Health checks
- Backup strategy
- Status page

## Tasks

1. **Structured logging**
   - JSON formatter in production
   - Request ID middleware (already in foundation)
   - Add user_id, route, duration to every log line via Monolog processor
   - Log levels used correctly (info for events, warning for recoverable, error for needs-attention)

2. **Error monitoring**
   - Sentry integration
   - Filter sensitive data (passwords, tokens) from error reports
   - Source maps uploaded on deploy
   - Slack alerting for new errors (not every occurrence)

3. **Telescope (local + staging only)**
   - Restrict access via gate to admin in non-prod environments
   - Configure entry pruning (don't fill the DB)

4. **Horizon**
   - Configure queue workers with proper memory limits, timeouts, retries
   - Tag jobs for easier filtering
   - Alerts when queue length exceeds threshold

5. **Health & metrics**
   - `/up` extended: checks DB, Redis, Meilisearch, queue worker heartbeat
   - `/metrics` endpoint (Prometheus format) behind admin auth: queue depth, request count, error rate
   - Or: integrate with a hosted APM (Inspector.dev or NewRelic)

6. **Status page**
   - Public `/status` page showing component health
   - Manual incident posting via Filament: "Searching is degraded"
   - Subscribe to incidents via email

7. **Backups**
   - `spatie/laravel-backup` configured: daily DB dump + storage to S3
   - Notify on failure (Slack + email)
   - Document restore procedure in `docs/RUNBOOK.md`

8. **Performance budget**
   - Add a Pest test that fails if a key endpoint makes >N queries (use `DB::listen` and assert)
   - Add a Pest test asserting response time under threshold for hot paths
   - Document the budget in `docs/PERFORMANCE.md`

9. **Deployment**
   - Forge/Ploi config example, OR Dockerfile + GitHub Actions deploy to a VPS
   - Zero-downtime deploys via php-fpm reload
   - Run migrations + cache:clear in deploy script
   - Document in `docs/DEPLOYMENT.md`

10. **Security review**
    - Run `composer audit` and document any accepted risks
    - HTTPS enforced, HSTS headers
    - CSP headers configured
    - CSRF on all state-changing routes
    - SQL injection: verify all dynamic queries use bindings (Larastan helps)
    - Mass assignment: every model has `$fillable` or `$guarded`

## Product thinking

- Status page builds trust; subscribe when there's an incident, not a marketing list
- Performance budget tests prevent regression — turning a culture into code
- Runbooks reduce 3am panic; future-you will thank present-you

## Tests

- Performance tests pass
- Health endpoint reflects real state (kill Redis, endpoint should fail)
- Sentry receives test event in staging

## Definition of Done

- ADR: "Observability stack: Sentry + Telescope + Horizon vs alternatives"
- `docs/RUNBOOK.md` covers: restore from backup, scale workers, rotate API keys, handle traffic spike
- `docs/DEPLOYMENT.md` covers: prod deploy, rollback, environment variables
- `composer check` clean

## After this prompt

Your project should be portfolio-ready. Take time to:
- Polish the README with screenshots, GIF demos, architecture diagram
- Write a blog post about 2-3 most interesting decisions
- Deploy a public demo with seeded interesting data
- Pin it to your GitHub profile

Done well, this project is interview material for years.
