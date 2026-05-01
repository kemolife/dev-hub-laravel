# Architecture Decision Records

This folder contains every architecturally significant decision made in DevHub. See [ADR-0001](./0001-record-architecture-decisions.md) for why we keep these.

## Index

| # | Title | Status | Date |
|---|---|---|---|
| 0001 | [Record architecture decisions](./0001-record-architecture-decisions.md) | Accepted | 2026-04-30 |
| 0002 | [Postgres over MySQL](./0002-postgres-over-mysql.md) | Accepted | 2026-04-30 |
| 0003 | [Livewire over Inertia](./0003-livewire-over-inertia.md) | Accepted | 2026-04-30 |
| 0004 | [UUID strategy: public_id alongside auto-increment](./0004-uuid-strategy.md) | Proposed | TBD |
| 0005 | [Action classes for business logic](./0005-action-classes.md) | Proposed | TBD |
| 0006 | [Cache invalidation strategy](./0006-cache-invalidation.md) | Proposed | TBD |
| 0007 | [Comment tree storage: adjacency list with materialized path](./0007-comment-tree-storage.md) | Accepted | 2026-05-15 |
| 0008 | [Markdown rendering: render-on-save](./0008-markdown-render-strategy.md) | Proposed | TBD |
| 0009 | [Search engine: Meilisearch](./0009-search-engine.md) | Proposed | TBD |
| 0010 | [Notification preference granularity: per-type, per-channel](./0010-notification-preferences.md) | Accepted | 2026-05-01 |
| 0011 | [Real-time: Reverb over Pusher](./0011-realtime-reverb.md) | Proposed | TBD |
| 0012 | [API versioning strategy](./0012-api-versioning.md) | Proposed | TBD |
| 0013 | [Audit logging approach](./0013-audit-logging.md) | Proposed | TBD |
| 0014 | [Bookmarks design](./0014-bookmarks.md) | Proposed | TBD |
| 0015 | [Billing: Stripe over Mollie](./0015-billing-stripe-over-mollie.md) | Accepted | 2026-05-01 |
| 0016 | [Plan limits: config vs DB](./0016-plan-limits-config-vs-db.md) | Accepted | 2026-05-01 |
| 0017 | [Feature flags via Pennant](./0017-feature-flags.md) | Proposed | TBD |
| 0018 | [No third-party analytics](./0018-no-third-party-analytics.md) | Proposed | TBD |
| 0019 | [Observability stack: Sentry + Telescope (no Horizon)](./0019-observability-stack.md) | Accepted | 2026-05-01 |
| 0020 | [Weekly digest: scheduled job, not a separate queue](./0020-digest-implementation.md) | Accepted | 2026-05-01 |

## Status Legend

- **Proposed** — drafted, awaiting commitment
- **Accepted** — committed, in effect
- **Deprecated** — no longer applies but kept for historical context
- **Superseded** — replaced by a later ADR (linked in the body)

## How to Add One

1. Copy `0001-record-architecture-decisions.md` as a template
2. Number it (next available, zero-padded)
3. Use kebab-case for the filename
4. Set status to **Proposed** while drafting
5. Update this index
6. Open a PR — discussion happens there
7. On merge, set status to **Accepted**

## How to Reference One

In code, link the ADR in a class docblock when the implementation is non-obvious:

```php
/**
 * Comment tree uses adjacency list + materialized path.
 * See docs/decisions/0007-comment-tree-storage.md
 */
class Comment extends Model { ... }
```

In other docs, use a relative link: `[ADR-0007](./decisions/0007-comment-tree-storage.md)`.
