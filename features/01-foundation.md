# Prompt 01 — Foundation

Set up a fresh Laravel 11 project named "DevHub" — a developer community platform. This prompt establishes the project skeleton, conventions, and tooling that every later feature will build on.

## Goals

- Production-ready Laravel project structure
- Strict typing and code style enforced from day one
- Docker dev environment for parity
- Documentation structure in place
- CI pipeline that actually runs

## Tasks

1. **Initialize project**
   - Laravel 11 with Livewire 3 + Volt starter kit (we'll use Livewire for the UI)
   - PostgreSQL as primary DB, Redis for cache/queues/sessions
   - Use Laravel Sail for local Docker setup with services: app, pgsql, redis, meilisearch, mailpit

2. **Code quality tooling**
   - Install Pest 3 + Pest plugins (laravel, faker, livewire)
   - Install Larastan (level 8) and Laravel Pint with strict preset
   - Install rector/rector with Laravel set
   - Add a `composer.json` script `composer check` that runs pint, larastan, and pest in sequence

3. **Project conventions**
   - Add `declare(strict_types=1);` to every PHP file (configure Pint to enforce)
   - Create `app/Actions/`, `app/Support/`, `app/Data/` directories with README explaining each
   - Install `spatie/laravel-data` for DTOs
   - Install `lorisleiva/laravel-actions` (or document why you chose plain invokable classes instead)

4. **Documentation skeleton**
   - Create `docs/decisions/` folder with `0001-record-architecture-decisions.md` (the meta-ADR)
   - Create `docs/PRODUCT.md` describing target user (developers who want a calmer alternative to dev.to), problem, and non-goals
   - Create `docs/ROADMAP.md` with shipped/in-progress/planned sections
   - Create `docs/CHANGELOG.md` following Keep a Changelog format
   - Create `docs/METRICS.md` listing what we'll track once features ship (signups, posts/week, DAU, etc.)

5. **CI/CD**
   - GitHub Actions workflow that runs `composer check` on PR
   - Separate workflow for deploy (can be a placeholder for now)

6. **Health check + structured logging**
   - `/up` health endpoint (Laravel 13 has this) — extend it to check DB and Redis
   - Configure Monolog to use JSON formatter in production
   - Add request ID middleware that adds a UUID to every log line for that request

## Definition of Done

- `composer check` passes with zero issues
- `php artisan test` runs (even if there are only smoke tests)
- README has setup instructions a new dev can follow in under 10 minutes
- ADR exists explaining: why Postgres over MySQL, why Livewire over Inertia for this project, why Pest over PHPUnit
- Project boots via `./vendor/bin/sail up` with no errors
