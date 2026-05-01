# Contributing

How to work in the DevHub codebase. Whether you're future-me at 2am or a new contributor, this is the orientation.

**Last updated:** 2026-04-30

---

## Before You Code

1. Read [PRODUCT.md](./PRODUCT.md) — understand the *why*
2. Check [ROADMAP.md](./ROADMAP.md) — is the thing you want to build planned, researching, or explicitly cut?
3. Skim relevant ADRs in [decisions/](./decisions/) — there might already be a documented direction

## Local Setup

```bash
# Clone
git clone git@github.com:vitalii/devhub.git
cd devhub

# Install PHP deps
composer install

# Install JS deps
npm install

# Copy env
cp .env.example .env
php artisan key:generate

# Start Sail (Docker)
./vendor/bin/sail up -d

# Run migrations + seeders
sail artisan migrate --seed

# Build assets in watch mode
sail npm run dev
```

Visit `http://localhost` — you should see the homepage.

Default seeded admin: `admin@devhub.test` / `password` (local only, never in prod)

## Project Conventions

### Code Style

- **PSR-12** enforced via Pint
- **Strict types** declared in every PHP file: `declare(strict_types=1);`
- **Larastan level 8** — no untyped arrays, no mixed without reason
- **Run `composer check`** before pushing — it runs Pint, Larastan, and Pest

### Architectural Rules

These are **enforced**, not suggestions. PRs violating them will be asked to refactor.

| Rule | Why |
|---|---|
| Controllers orchestrate, never decide | Business logic belongs in Actions |
| Models hold data + relations only | No `$post->publish()` business methods |
| Form Requests validate AND authorize | Don't validate in controllers |
| API responses go through Resources | No raw `->toArray()` |
| All external I/O is queued | Never `Mail::send()` synchronously |
| New columns nullable in migrations | Zero-downtime deploys |
| No raw `DB::raw()` without ADR | Forces deliberate choice |

### File Organization

See [ARCHITECTURE.md](./ARCHITECTURE.md#application-layout). When in doubt:
- New business logic → `app/Actions/{Domain}/`
- New value object → `app/Support/`
- New DTO → `app/Data/`
- New domain event → `app/Events/`

### Naming

- Actions: imperative verb. `PublishPostAction`, not `PostPublisher`
- Events: past tense. `PostPublished`, not `PublishPost`
- Listeners: describe what they do. `SendNewPostNotifications`, not `PostPublishedListener`
- Jobs: imperative verb. `DeliverWebhookJob`, not `WebhookJob`
- Tests: full sentence. `it('publishes a draft when title and body are present')`

---

## Workflow

### Branches

- `main` → production
- `develop` → staging
- `feature/{short-description}` → your work

### Commits

Conventional Commits format:

```
feat(posts): add markdown autosave
fix(auth): respect timezone in 2FA challenge expiry
docs: update PRODUCT.md with bookmark feature decision
chore(deps): bump laravel/cashier-mollie to 2.x
refactor(comments): extract MentionParser to Support namespace
```

Types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `perf`, `style`

### Pull Requests

PR checklist (template auto-applied):

- [ ] Tests added (Pest)
- [ ] `composer check` passes
- [ ] CHANGELOG updated (if user-facing)
- [ ] ADR added (if architecturally significant)
- [ ] Screenshots/GIFs (if UI change)
- [ ] Docs updated (if changing setup/deployment)

PR description should answer:

1. What changed?
2. Why?
3. What was considered and rejected?
4. How did you test it?

Solo project tip: review your own PRs the next morning before merging. You'll catch things you missed.

---

## Testing

### Stack

- **Pest 3** for everything
- **Pest plugins**: laravel, faker, livewire, type-coverage
- **DB**: in-memory SQLite for unit/feature tests by default; Postgres for integration tests that need it

### What to Test

| Code | Test type |
|---|---|
| Action class | Unit + feature |
| Form Request | Feature (HTTP-level) |
| Policy | Unit |
| Livewire component | Feature with Livewire helpers |
| API endpoint | Feature, including auth + abilities |
| Event listener | Feature with `Event::fake()` |
| Notification | Feature with `Notification::fake()` |
| Hot path performance | Performance test (query count + duration) |

### What NOT to Test

- Eloquent's own behavior (relationships work, casts work — trust the framework)
- Third-party packages (assume they have their own tests)
- Trivial getters/setters

### Running Tests

```bash
# All tests
sail artisan test

# Or directly
sail bin/pest

# Watch mode
sail bin/pest --watch

# Specific file
sail bin/pest tests/Feature/PostPublishingTest.php

# With coverage
sail bin/pest --coverage --min=80
```

---

## Writing ADRs

When in doubt, write one. Cost of writing: 20 minutes. Cost of *not* having it 6 months later: hours of re-litigation.

See [decisions/0001-record-architecture-decisions.md](./decisions/0001-record-architecture-decisions.md) for format.

Bar for an ADR:
- Hard to reverse (DB choice, API versioning)
- Affects multiple parts of the system
- Trades off competing values
- Anyone might question it later
- Was a close call

---

## Common Patterns

### Adding a new feature

1. ADR if architecturally significant
2. Migration (with rollback)
3. Model + relations + tests
4. Action class + tests
5. Form Request + Resource (if API-exposed)
6. Controller (thin)
7. Livewire component or Blade view
8. Tests (feature + unit + performance for hot paths)
9. Update CHANGELOG
10. Update relevant docs

### Adding a new background job

1. Create Job class
2. Implement `ShouldQueue`, set timeout + tries
3. Tag the job for Horizon
4. Test with `Bus::fake()` for dispatch, real instantiation for logic
5. Document in [RUNBOOK.md](./RUNBOOK.md) if operationally significant

### Adding a new notification

1. Create Notification class
2. Define channels via `via()` — respect user preferences
3. Define `toMail`, `toDatabase`, `toBroadcast` as needed
4. Add type to user notification preferences with sane defaults
5. Test all channels with `Notification::fake()`

---

## Anti-Patterns to Avoid

| Don't | Do |
|---|---|
| `$user->posts()->get()->count()` | `$user->posts()->count()` or denormalized counter |
| `User::all()` then filter in PHP | Filter in query |
| Logic in Blade templates | Move to Livewire / view models |
| Repeated query in a loop | Eager load |
| `Mail::send()` in controller | Queue it |
| `try { ... } catch (\Exception $e) { /* swallow */ }` | Catch specific exceptions, log, rethrow or handle |
| `auth()->user()->id` everywhere | Inject via Form Request authorize / policies |
| Magic strings for status / role | Use enums |
| `$post->update($request->all())` | Validated DTO via Form Request |

---

## Performance

Hot paths have performance tests. If you're touching one, run them locally:

```bash
sail bin/pest tests/Performance/
```

See [PERFORMANCE.md](./PERFORMANCE.md) for budgets.

---

## Getting Help

- Re-read relevant ADRs
- Check Laravel docs (specifically the version we're on, not the latest)
- Look for similar patterns in the existing codebase
- If stuck, write a draft PR with `[WIP]` and ask for early feedback

---

## Solo-Project Tips

You're (currently) the only contributor. The rituals above feel heavy for one person. They're not — they're how present-you helps future-you.

- Write the PR description even when you're merging your own work
- Write the ADR even when no one else will read it (you, in 6 months, are someone else)
- Run `composer check` before merging — past-you is not above making mistakes

The discipline pays compound interest.
