<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).


</laravel-boost-guidelines>

---

# DevHub — Project Context

DevHub is a Laravel 13 portfolio project — a calmer alternative to dev.to for developers who want depth over engagement-bait. See `docs/PRODUCT.md` for full product vision.

**Deliberate learning + portfolio project.** Goal: demonstrating senior-level Laravel patterns and product engineering thinking. Quality of decisions and documentation matters as much as code.

---

## Architectural Rules (Non-Negotiable)

Enforced. Any violation must be refactored before merge.

1. **`declare(strict_types=1);` in every PHP file** — Pint enforces this
2. **Controllers orchestrate, never decide** — business logic lives in `app/Actions/`
3. **Models hold data + relations only** — no business methods on Eloquent models
4. **Form Requests validate AND authorize** — never validate in controllers
5. **API responses always go through API Resources** — no raw `->toArray()`
6. **All external I/O is queued** — never `Mail::send()` synchronously
7. **New columns nullable in migrations** — zero-downtime deploys
8. **Use enums for status/role/type** — never magic strings
9. **No `DB::raw()` without an ADR** — forces deliberate choice
10. **Polymorphic + nested data uses adjacency list + materialized path** (see ADR-0007)
11. **Actions accept DTOs, never plain arrays** — every action takes a typed `readonly` DTO from `app/Data/{Domain}/`. Form Requests expose `toData()` that constructs the DTO from `$this->validated()`.

---

## File Organization

```
app/
├── Actions/{Domain}/         ← business logic, single-responsibility
├── Data/{Domain}/            ← DTOs (PHP readonly classes — input to actions, output from actions)
├── Enums/                    ← PHP enums
├── Events/                   ← domain events (past tense: PostPublished)
├── Http/
│   ├── Controllers/          ← thin orchestration
│   ├── Middleware/
│   ├── Requests/             ← validation + authorization, return DTOs
│   └── Resources/            ← API response shaping
├── Jobs/                     ← queued work (imperative: DeliverWebhookJob)
├── Listeners/                ← event reactions (descriptive: SendNewPostNotifications)
├── Mail/                     ← Mailables
├── Models/                   ← Eloquent only
├── Notifications/            ← multi-channel
├── Observers/                ← Eloquent observers
├── Policies/                 ← authorization
├── Providers/
└── Support/                  ← value objects, helpers, domain primitives
```

When in doubt: new business logic → `app/Actions/{Domain}/`.

---

## Naming Conventions

- **Actions**: imperative verb. `PublishPostAction` ✓ — `PostPublisher` ✗
- **Events**: past tense. `PostPublished` ✓ — `PublishPost` ✗
- **Listeners**: describe what they do. `SendNewPostNotifications` ✓ — `PostPublishedListener` ✗
- **Jobs**: imperative verb. `DeliverWebhookJob` ✓ — `WebhookJob` ✗
- **Tests**: full sentence. `it('publishes a draft when title and body are present')`
- **Input DTOs**: noun + `Data`. `LoginData`, `RegisterData` ✓ — `LoginInput` ✗
- **Output DTOs**: describe what they carry. `AuthTokenData`, `TwoFactorRequiredData` ✓

---

## DTO Pattern

All Actions receive and return typed `readonly` classes from `app/Data/{Domain}/`.

**Input DTO** (Form Request → Action):
```php
// app/Data/Auth/LoginData.php
readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'api',
    ) {}

    /** @param array{email: string, password: string, device_name?: string} $data */
    public static function from(array $data): self
    {
        return new self(email: $data['email'], password: $data['password'], deviceName: $data['device_name'] ?? 'api');
    }
}

// app/Http/Requests/Auth/LoginRequest.php
public function toData(): LoginData
{
    return LoginData::from($this->validated());
}

// Controller
$result = $this->loginAction->execute($request->toData());
```

**Output DTO** (Action → Controller):
```php
// app/Data/Auth/AuthTokenData.php
readonly class AuthTokenData
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public User $user,
    ) {}
}
```

Rules:
- Every `readonly` class in `app/Data/` gets `declare(strict_types=1)`
- Input DTOs have a static `from(array $data): self` factory
- Output DTOs are plain constructors — no factory needed
- Never pass `$request->validated()` or `$request->all()` directly to an action

---

## Documentation Discipline

The `docs/` folder is part of the deliverable, not optional. Update relevant docs as you build:

| When you... | Update... |
|---|---|
| Make an architectural decision | New ADR in `docs/decisions/` |
| Ship a user-facing change | `docs/CHANGELOG.md` (Unreleased section) |
| Change deployment, env vars, infra | `docs/DEPLOYMENT.md` |
| Add an operational procedure | `docs/RUNBOOK.md` |
| Change performance characteristics | `docs/PERFORMANCE.md` |
| Add/change a security-relevant feature | `docs/SECURITY.md` |
| Add a feature listed in roadmap | Mark ✅ in `docs/ROADMAP.md` |
| Build something not yet planned | Either add to roadmap first, or stop and ask |

### When to write an ADR

Required when the decision:
- Is hard to reverse (DB choice, API versioning scheme)
- Affects multiple parts of the system
- Trades off competing values (privacy vs personalization)
- Will be questioned later
- Was a close call between two reasonable options

NOT required for:
- Conventional choices already established in this CLAUDE.md
- Implementation details inside a single class
- Style/formatting (handled by Pint)
- Reversible experiments behind feature flags

### ADR format

Copy `docs/decisions/0001-record-architecture-decisions.md` as template. Sections: Context, Decision, Consequences (positive + negative), Alternatives Considered, How We'll Know We Got It Wrong.

Filename: `NNNN-kebab-case-title.md` — next available number, zero-padded.

After creating an ADR, update index in `docs/decisions/README.md`.

---

## Testing Standards

- Hot paths get **performance tests** (query count + duration assertions)
- **Use fakes** for `Mail::fake()`, `Notification::fake()`, `Bus::fake()`, `Event::fake()`
- **Test what you write, trust the framework** — don't test that `belongsTo` works
- **Coverage target**: 80% on critical paths

---

## Workflow Per Feature

1. Read prompt carefully; check `docs/ROADMAP.md` to confirm it's planned
2. Skim relevant existing ADRs for context
3. Write any new ADR before/during implementation
4. Migration → Model → Action → Form Request/Resource → Controller → Frontend → Tests
5. Run `composer check` (Pint, Larastan, PHPUnit) before declaring done
6. Update `docs/CHANGELOG.md` (Unreleased section)
7. Update other docs per table above
8. Output summary: what changed, ADRs added, docs updated, next suggested steps

---

## What I Don't Want

- ❌ Add packages without checking if Laravel ships the capability natively
- ❌ Create generic / boilerplate code "just in case" — YAGNI
- ❌ Skip tests because "it's a small change"
- ❌ Put business logic in controllers, models, or views
- ❌ Suggest re-architecting things outside the current feature's scope (note ideas in `docs/ROADMAP.md` Researching section instead)
- ❌ Write defensive `try/catch` that swallows exceptions
- ❌ Generate marketing copy or filler text — ask for specifics
- ❌ Reference external libraries or syntax without verifying against the version we're on (Laravel v13, PHPUnit v12)

## What I Do Want

- ✅ Ask clarifying questions if the prompt is ambiguous before writing code
- ✅ Suggest a different approach if you see a better one — explain the trade-off
- ✅ Point out when something violates the rules above and offer the right alternative
- ✅ Write ADRs proactively when you make a non-obvious choice
- ✅ Output diffs and summaries that are skim-friendly
- ✅ Default to small, reviewable commits over big ones

---

## Reference Documents

- `docs/PRODUCT.md` — product vision and principles
- `docs/ROADMAP.md` — what's in scope, what's out
- `docs/ARCHITECTURE.md` — system design
- `docs/CONTRIBUTING.md` — code conventions in detail
- `docs/decisions/` — every architectural decision
