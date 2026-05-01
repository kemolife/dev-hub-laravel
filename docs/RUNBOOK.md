# Runbook

Operational procedures for DevHub. When something goes wrong at 3am, this is what you read first.

**Last updated:** 2026-04-30
**On-call:** Vitalii (solo)

---

## Incident Response

### 1. Acknowledge the alert

- Sentry / UptimeRobot → Slack `#alerts`
- Note the time the incident started
- If user-facing impact: post to `/status` page within 15 minutes

### 2. Assess severity

| Level | Definition | Response time |
|---|---|---|
| **Sev 1** | Site down, can't login, payments broken | Immediate, drop everything |
| **Sev 2** | Major feature broken, data at risk | Within 1 hour |
| **Sev 3** | Minor feature degraded, workaround exists | Within 24 hours |
| **Sev 4** | Cosmetic, low impact | Next business day |

### 3. Mitigate first, fix later

Priority is restoring service. Root-cause analysis comes after.

### 4. Post-incident

For Sev 1 and Sev 2, write a post-mortem within 48 hours:
- What happened
- Timeline
- Why it happened
- What we did to recover
- What we'll change to prevent recurrence

Store in `docs/incidents/YYYY-MM-DD-short-description.md`.

---

## Common Procedures

### Site is down (5xx errors)

1. Check `/up` — if it fails, app server problem
2. Check Sentry — what's the most recent error?
3. Check Forge → server status — is the server up? CPU/memory?
4. Check Horizon — are queues backed up?
5. Check Postgres — connections maxed? slow queries?
6. Check Redis — memory usage, evictions?

If recent deploy: **roll back first, investigate after**. See [DEPLOYMENT.md](./DEPLOYMENT.md#rolling-back).

### Queue backed up

```bash
# How deep?
php artisan horizon:status
# Inspect via dashboard
# https://devhub.app/horizon

# If a specific queue is backed up
# - identify the slow job
# - either fix the job, or scale workers
```

Scale workers temporarily by editing `config/horizon.php` and re-deploying:

```php
'production' => [
    'supervisor-1' => [
        'maxProcesses' => 20, // was 10
    ],
],
```

### Database slow / locked

1. Check pg_stat_activity for long-running queries:
   ```sql
   SELECT pid, now() - query_start AS duration, query, state
   FROM pg_stat_activity
   WHERE state != 'idle'
   ORDER BY duration DESC;
   ```
2. Identify N+1 patterns via Sentry performance traces
3. If a single query is the culprit, kill it: `SELECT pg_cancel_backend(pid)`
4. Long-term fix: add an index, fix the N+1, or add caching — write an ADR

### Redis memory high / evictions

- Check `INFO memory` and `INFO stats`
- Identify largest keys: `redis-cli --bigkeys`
- If sessions are bloating: investigate (compromised users? bot signup?)
- If cache: review TTL strategy, consider larger Redis or sharding

### Meilisearch out of sync

If search results are stale or wrong:

```bash
# Re-index a model
php artisan scout:flush "App\Models\Post"
php artisan scout:import "App\Models\Post"

# Or use our wrapper
php artisan scout:reindex
```

### Stuck Horizon workers

```bash
# Restart all workers
php artisan horizon:terminate
# Supervisor will respawn them with latest code
```

### Failed webhooks (outgoing)

- Filament → Webhook Endpoints — check `last_failure_at`, `failure_count`
- If endpoint is dead, disable it and notify owner via email
- If transient, retry: `php artisan webhooks:retry {endpoint_id}`

---

## Backups

### What's backed up

| Item | Method | Frequency | Retention |
|---|---|---|---|
| Postgres | `spatie/laravel-backup` daily dump → S3 | Daily 03:00 UTC | 30 days daily, 12 months monthly |
| User uploads (S3) | S3 versioning enabled | On every write | 90 days |
| Meilisearch index | Snapshot via API | Weekly | 4 weeks |
| Configuration | Git (this repo) + env in 1Password | Continuous | Forever |

### Restoring from backup

**DB restore:**

```bash
# 1. Put site in maintenance mode
php artisan down --secret="emergency-XYZ"

# 2. Download latest backup from S3
aws s3 cp s3://devhub-backups/db/2026-04-30.sql.gz ./

# 3. Decompress
gunzip 2026-04-30.sql.gz

# 4. Restore (DESTRUCTIVE — sanity check first)
psql -h $DB_HOST -U $DB_USER -d devhub_restore < 2026-04-30.sql

# 5. Verify on devhub_restore, then swap
# 6. php artisan up
```

**File restore (S3):**

```bash
# S3 versioning means you can restore individual objects
aws s3api list-object-versions --bucket devhub-prod --prefix path/to/file
aws s3api copy-object --bucket devhub-prod \
  --copy-source "devhub-prod/path/to/file?versionId=XYZ" \
  --key path/to/file
```

### Backup verification

**Test restore quarterly.** A backup that's never been restored is not a backup.

Procedure:
1. Spin up a temporary VPS
2. Restore latest backup
3. Run `php artisan migrate:status` and `php artisan tinker` — confirm data sane
4. Document in `docs/backup-tests/YYYY-QN.md`
5. Tear down VPS

---

## Routine Operations

### Rotate API keys / secrets

Quarterly, or immediately if compromise suspected.

1. Generate new value (e.g., new Postmark token)
2. Add to Forge env (don't remove old yet)
3. Deploy
4. Verify new key works in production
5. Revoke old key in third-party
6. Document rotation in `docs/secret-rotation-log.md`

### Add a new admin user

Manually via tinker (not exposed via UI):

```bash
php artisan tinker
>>> $user = User::where('email', 'newadmin@example.com')->first();
>>> $user->role = \App\Enums\Role::Admin;
>>> $user->save();
>>> exit
```

Audit log entry must be created — use the action class:

```bash
php artisan user:promote newadmin@example.com --role=admin
```

### Suspend a user

Via Filament admin panel: Users → search → Suspend, with reason and duration.

For emergency suspension (skip UI):

```bash
php artisan tinker
>>> $u = User::where('email', '...')->first();
>>> $u->update([
>>>   'suspended_at' => now(),
>>>   'suspended_until' => now()->addDays(7),
>>>   'suspension_reason' => 'spam',
>>> ]);
```

### Handle GDPR data request

EU residents have right to access, rectify, and delete. Procedure:

1. Verify identity (must come from registered email)
2. Within 30 days: respond
3. **Access request**: trigger account export job, deliver via signed URL
4. **Deletion request**: run `php artisan user:purge {user_id}` — soft-deletes, anonymizes posts/comments to "Deleted user", removes PII. Hard delete after 30-day grace period via cron.
5. Log the request in `docs/gdpr-log.md` (no PII, just date + type + completion date)

---

## Scaling Triggers

Pre-defined thresholds. When hit, take action — don't wait for outages.

| Metric | Threshold | Action |
|---|---|---|
| Server CPU sustained | >70% for 1h | Scale up VPS |
| Postgres connections | >80% of max | Add pgbouncer, then scale up |
| Redis memory | >75% | Scale up Redis |
| p95 response time | >500ms for 1h | Investigate, optimize hot paths |
| Queue depth | >1000 for 15min | Scale workers |
| Concurrent users | >500 | Plan horizontal scaling (load balancer + 2+ app servers) |

Horizontal scaling plan: documented when first threshold hits, not before.

---

## Useful One-Liners

```bash
# Tail production logs
ssh devhub@prod "tail -f /home/forge/devhub.app/storage/logs/laravel-$(date +%Y-%m-%d).log"

# Production tinker (use with care)
ssh devhub@prod "cd /home/forge/devhub.app && php artisan tinker"

# Cache clear (if config changes need to take effect immediately)
php artisan config:clear && php artisan cache:clear

# Check what's queued
php artisan horizon:status

# Pause queues (e.g., during maintenance)
php artisan horizon:pause

# Resume
php artisan horizon:continue
```

---

## Search Index (Meilisearch)

### Re-index all published posts

Run when: Meilisearch was down for a period, after a schema change to `toSearchableArray()`, or after restoring from backup.

```bash
# Flush the existing index and re-import all published posts
php artisan scout:flush "App\Models\Post"
php artisan scout:import "App\Models\Post"
```

Both commands only touch documents where `shouldBeSearchable()` returns true (i.e., published posts only).

### Sync index settings after config changes

If you update `filterableAttributes`, `sortableAttributes`, or `searchableAttributes` in `config/scout.php`:

```bash
php artisan scout:sync-index-settings
```

### Check Meilisearch health

```bash
curl http://127.0.0.1:7700/health
# Expected: {"status":"available"}
```

### Meilisearch won't start

```bash
# macOS (Homebrew)
brew services restart meilisearch

# Docker
docker compose restart meilisearch
```

If the index is corrupted, remove the `data.ms/` directory and re-import:

```bash
rm -rf /usr/local/var/meilisearch/data.ms/
brew services restart meilisearch
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Post"
```

---

## Contact / Escalation

- **Solo project**: Vitalii is the only on-call
- **Vendor support**:
  - Forge: support@forge.laravel.com
  - Hetzner: support@hetzner.com (open ticket via console)
  - Sentry: support@sentry.io
  - Mollie: support@mollie.com
- **Slack channels**:
  - `#alerts` — automated alerts
  - `#incidents` — active incident comms
