# Architecture

High-level overview of how DevHub is built. For detailed decisions, see [decisions/](./decisions/).

**Last updated:** 2026-04-30

---

## System Diagram (logical)

```
                    ┌─────────────┐
                    │   Browser   │
                    └──────┬──────┘
                           │ HTTPS
                  ┌────────▼────────┐
                  │   Nginx / TLS   │
                  └────────┬────────┘
                           │
                  ┌────────▼────────┐
                  │  Laravel (PHP)  │ ← Octane (FrankenPHP)
                  │   - Web routes  │
                  │   - API routes  │
                  │   - Livewire    │
                  └─┬──────┬──────┬─┘
        ┌───────────┘      │      └───────────────┐
        │                  │                      │
   ┌────▼────┐       ┌─────▼─────┐         ┌──────▼──────┐
   │ Postgres│       │   Redis   │         │ Meilisearch │
   │ (data)  │       │ (cache,   │         │ (search)    │
   │         │       │  queue,   │         │             │
   │         │       │  session) │         │             │
   └─────────┘       └─────┬─────┘         └─────────────┘
                           │
                  ┌────────▼────────┐
                  │     Reverb      │ ← WebSocket server
                  │ (broadcasting)  │
                  └─────────────────┘

  Background workers (Horizon):
   ┌─────────────────────────────────────────────┐
   │  Email send / Webhook deliver / Indexing /  │
   │  OG image gen / Digest jobs / Notifications │
   └─────────────────────────────────────────────┘
```

## Stack Summary

| Layer | Choice | Why |
|---|---|---|
| Framework | Laravel 11 | The point of this project. ADR-0002 |
| Language | PHP 8.3 with strict types | Modern, typed, fast |
| UI | Livewire 3 + Volt | Server-rendered, low JS, fits "calm" positioning. ADR-0003 |
| Database | Postgres 16 | JSONB, FTS as fallback, proper enums. ADR-0004 |
| Cache / queue / session | Redis 7 | Standard, well-known, fast |
| Search | Meilisearch | Typo-tolerant, fast, simple ops. ADR-0009 |
| Real-time | Laravel Reverb | First-party, no Pusher dependency. ADR-0011 |
| Queue management | Horizon | Native to Laravel queues, dashboard included |
| Auth | Fortify (backend) + custom Livewire UI | Flexible, full control over UX |
| Billing | Cashier (Mollie) | EU-friendly payment provider. ADR-0015 |
| Admin | Filament 3 | Fast to build internal tools, good Laravel integration |
| Error monitoring | Sentry | Industry standard |
| Email (transactional) | Postmark | Reliable, simple |
| Hosting | Forge + Hetzner VPS | Cost-effective, full control |
| Object storage | Hetzner Object Storage (S3-compatible) | EU residency, cheap |

## Application Layout

```
app/
├── Actions/              ← Business logic, single-responsibility classes
│   ├── Posts/
│   ├── Comments/
│   ├── Auth/
│   └── Billing/
├── Data/                 ← DTOs (spatie/laravel-data)
├── Enums/                ← PHP enums (PostStatus, ReactionType, Role, etc.)
├── Events/               ← Domain events (PostPublished, CommentPosted, etc.)
├── Http/
│   ├── Controllers/      ← Thin orchestration
│   ├── Middleware/
│   ├── Requests/         ← Validation + authorization
│   └── Resources/        ← API response shaping
├── Jobs/                 ← Queued work
├── Listeners/            ← Event reactions (notifications, indexing, etc.)
├── Livewire/             ← UI components
├── Mail/                 ← Mailables
├── Models/               ← Eloquent models, relations, scopes only
├── Notifications/        ← Multi-channel notifications
├── Observers/            ← Eloquent observers
├── Policies/             ← Authorization
├── Providers/
└── Support/              ← Value objects, helpers, domain primitives
    ├── ReadingTime.php
    ├── MentionParser.php
    └── PlanLimits.php
```

## Key Design Principles

1. **Controllers orchestrate, don't decide.** All business logic lives in Actions or domain services.
2. **Models hold data + relations.** No business logic in Eloquent models.
3. **Events for cross-cutting concerns.** Publishing a post fires `PostPublished`; listeners handle indexing, notifications, OG image generation independently.
4. **Form Requests authorize and validate.** Controllers receive validated DTOs.
5. **API Resources for all JSON output.** No raw `->toArray()` in responses.
6. **Soft delete by default** for user-generated content. Hard delete is a deliberate exception.
7. **Cache by tag, invalidate on event.** Avoid TTL-only caching where freshness matters.

## Request Lifecycle (typical write)

```
HTTP Request
  → Route
  → Middleware (auth, rate limit, request ID)
  → Controller
  → Form Request (validate + authorize)
  → DTO
  → Action class
  → Database transaction
  → Domain event dispatched
    → Listener: write to search index (queued)
    → Listener: send notifications (queued)
    → Listener: invalidate cache
    → Listener: broadcast over WebSocket
  → API Resource
  → JSON response
```

Each step has a single responsibility. Failures in one listener don't break the request.

## Data Model (high level)

Core entities:
- **User** — accounts, profiles, settings
- **Post** — content, with markdown source + cached HTML
- **Comment** — polymorphic on commentable, nested via parent_id + path
- **Reaction** — polymorphic on reactable, typed enum
- **Tag** — many-to-many with Post via `post_tag` (with weight)
- **Follow** — directed graph between users
- **Notification** — Laravel default + per-user preferences
- **Subscription** — Cashier-managed billing state

See `database/migrations/` for full schema. ER diagram in `docs/diagrams/erd.png` (TODO).

## Frontend Strategy

Livewire 3 + Volt for interactive components. Alpine.js for purely client-side behavior (dropdowns, transitions). Tailwind for styling. No SPA framework.

Why: this matches the "calm" positioning — minimal JS, fast first paint, server-rendered for SEO. Inertia + Vue/React was considered (ADR-0003) and rejected for v1.

## Environments

| Env | URL | Purpose |
|---|---|---|
| Local | http://devhub.test | Sail-based development |
| Staging | https://staging.devhub.app | Mirror of prod, real services, fake data |
| Production | https://devhub.app | Live |

Staging and production use identical infra; staging has a `noindex` meta tag and a banner.

## Deployment

See [DEPLOYMENT.md](./DEPLOYMENT.md).

## Operations

See [RUNBOOK.md](./RUNBOOK.md).

## Performance Targets

See [PERFORMANCE.md](./PERFORMANCE.md).

## Security Posture

See [SECURITY.md](./SECURITY.md).
