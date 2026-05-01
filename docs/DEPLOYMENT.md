# Deployment

How DevHub is deployed, rolled back, and configured per environment.

**Last updated:** 2026-04-30

---

## Environments

| Env | URL | Branch | Purpose |
|---|---|---|---|
| Local | http://devhub.test | feature/* | Development via Sail |
| Staging | https://staging.devhub.app | `develop` | Pre-prod verification |
| Production | https://devhub.app | `main` | Live |

## Deploy Pipeline

```
Push to main / develop
      │
      ▼
GitHub Actions:
  - Run composer check (Pint, Larastan, Pest)
  - Build assets (npm run build)
  - Tag image / artifact
      │
      ▼
On success:
  - Forge webhook triggered
  - Server pulls latest, runs deploy script
      │
      ▼
Deploy script:
  - composer install --no-dev --optimize-autoloader
  - php artisan migrate --force
  - php artisan config:cache
  - php artisan route:cache
  - php artisan view:cache
  - php artisan event:cache
  - php artisan octane:reload
  - php artisan horizon:terminate (re-spawns with new code)
      │
      ▼
Post-deploy verification:
  - Hit /up health endpoint
  - Check Sentry for new errors in last 5 min
  - Smoke test critical paths (login, post show, search)
```

## Rolling Back

If a deploy goes bad:

1. **Forge → Site → Deployments → previous successful → Redeploy**
2. Watch Sentry for error rate dropping
3. If migration was part of bad deploy: revert via `php artisan migrate:rollback --step=N` — but only after confirming no production data depends on the new schema. **Never run rollback without checking.**
4. Post-incident: write a runbook entry in [RUNBOOK.md](./RUNBOOK.md) if the rollback procedure revealed a gap.

## Environment Variables

Managed via Forge env editor (production) and `.env` (local). Never commit `.env` to git.

### Required

```
APP_NAME=DevHub
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://devhub.app

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=...
DB_DATABASE=devhub
DB_USERNAME=...
DB_PASSWORD=...

REDIS_HOST=...
REDIS_PASSWORD=...
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...

MAIL_MAILER=postmark
POSTMARK_TOKEN=...

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=...
MEILISEARCH_KEY=...

SENTRY_LARAVEL_DSN=...
SENTRY_TRACES_SAMPLE_RATE=0.1

CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=nl_NL
MOLLIE_KEY=...

GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=devhub-prod
AWS_ENDPOINT=https://...
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Optional

```
TELESCOPE_ENABLED=false  # never true in prod
HORIZON_PREFIX=horizon:
OCTANE_SERVER=frankenphp
```

## Server Configuration

Production runs on a single Hetzner CPX31 (4 vCPU, 8GB RAM) for v1. Scaling plan documented in [RUNBOOK.md](./RUNBOOK.md).

Services on the box:
- nginx (reverse proxy, TLS termination via Let's Encrypt)
- FrankenPHP (Octane runtime)
- Horizon (queue workers, supervised by systemd)
- Reverb (WebSocket server, supervised by systemd)
- Cron (Laravel scheduler via `* * * * * php artisan schedule:run`)

External services:
- Postgres: managed (Hetzner)
- Redis: managed (Hetzner)
- Meilisearch: self-hosted on a separate VPS
- Object storage: Hetzner S3-compatible

## Zero-Downtime Considerations

- Migrations must be backwards-compatible during deploy (add columns nullable, deprecate before remove)
- Long-running migrations run manually outside deploy window
- Asset versioning via Vite ensures no stale-asset issues
- Octane reload, not stop/start, preserves connections

## Maintenance Mode

For deploys that require downtime (rare, typically major schema changes):

```bash
php artisan down --secret="emergency-bypass-token-XYZ" \
  --render="errors::503" \
  --refresh=15
# do the work
php artisan up
```

The secret allows admin access during downtime via `/emergency-bypass-token-XYZ`.

## Initial Server Setup

For setting up a new server from scratch, see `infra/setup-checklist.md` (TODO — create when scaling beyond single server).

## Backups

See [RUNBOOK.md](./RUNBOOK.md#backups) for backup strategy and restore procedures.

## TLS / Certificates

Managed by Let's Encrypt via Forge auto-renewal. Verify renewal monthly via:

```bash
certbot certificates
```
