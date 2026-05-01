# Performance Budget

What "fast enough" means for DevHub, and how we keep it that way.

**Last updated:** 2026-04-30

---

## Why This Document Exists

Performance is a feature. Per [PRODUCT.md](./PRODUCT.md), DevHub is positioned as a calm reading experience — slow pages break that promise faster than any feature could.

This doc defines:
- Concrete budgets per route type
- How we measure
- How we enforce (tests + alerts)
- What we do when budgets are blown

---

## Budgets

### Response Time (server-side, measured at app layer)

| Route type | p50 | p95 | p99 | Notes |
|---|---|---|---|---|
| Post show (cached) | <50ms | <150ms | <300ms | Hot path; full-page cache for guests |
| Post show (uncached) | <150ms | <400ms | <800ms | Authenticated views, fresh content |
| Post list / feed | <100ms | <300ms | <600ms | Paginated, cached aggregates |
| Search | <100ms | <300ms | <600ms | Meilisearch handles most of this |
| API: GET /posts | <100ms | <250ms | <500ms | API consumers sensitive to latency |
| API: POST /posts | <200ms | <500ms | <1000ms | Includes write + index queue |
| Auth flows (login, register) | <200ms | <500ms | <1000ms | Includes hashing |

### Frontend (Lighthouse, mobile, slow 3G)

| Metric | Target |
|---|---|
| First Contentful Paint | <1.5s |
| Largest Contentful Paint | <2.5s |
| Time to Interactive | <3.5s |
| Cumulative Layout Shift | <0.1 |
| Performance score | >90 |
| SEO score | >95 |
| Accessibility score | >95 |

### Database

| Metric | Budget |
|---|---|
| Queries per request (post show) | ≤4 |
| Queries per request (post list) | ≤6 |
| Queries per request (search results) | ≤3 |
| Queries per request (API endpoints) | ≤8 |
| Slow query threshold | 100ms (anything slower logged) |

### Background Jobs

| Job | Target completion |
|---|---|
| Email send | <5s |
| Notification fan-out | <10s |
| Search index update | <30s after publish |
| OG image generation | <60s after publish |
| Weekly digest | <5min total for all users |
| Webhook delivery (per attempt) | <10s timeout |

---

## How We Measure

### Production

- **Sentry Performance**: tracks p50/p95/p99 per route, alerts on regression
- **Inspector.dev**: APM with database query insights
- **Custom dashboard** (Filament): rolls up key metrics weekly
- **Real User Monitoring**: lightweight client-side timing posted to `/api/internal/timing` (privacy-respecting)

### Development

- **Laravel Telescope** (local + staging only): query count, duration per request
- **Pest performance tests** (CI): assert query count and timing for hot paths
- **Lighthouse CI** (on every PR): catches frontend regressions

---

## Enforcement: Performance Tests

Performance budgets are enforced as code. Example test:

```php
it('post show makes no more than 4 queries', function () {
    $post = Post::factory()->published()->create();

    DB::enableQueryLog();
    $response = $this->get(route('posts.show', $post));
    $queries = DB::getQueryLog();

    expect($response)->toBeOk();
    expect(count($queries))->toBeLessThanOrEqual(4);
});

it('post show responds in under 200ms', function () {
    $post = Post::factory()->published()->create();

    $start = microtime(true);
    $this->get(route('posts.show', $post));
    $duration = (microtime(true) - $start) * 1000;

    expect($duration)->toBeLessThan(200);
});
```

These tests are **mandatory** for hot paths. CI fails if budgets are exceeded.

---

## Caching Strategy

| Cache layer | What | TTL | Invalidation |
|---|---|---|---|
| Full-page (guest) | Rendered post show pages | 1h | On post update |
| Fragment | Comment thread HTML | 5min | On new comment |
| Eloquent | Computed counts (followers, reactions) | denormalized columns | On event |
| Query | Tag list, popular posts | 15min | TTL only |
| HTTP | Static assets | 1y | Vite asset hashing |

**Default stance:** invalidate on event, not on TTL, when freshness matters.

---

## Common Performance Anti-Patterns (and how we avoid them)

| Anti-pattern | Detection | Mitigation |
|---|---|---|
| N+1 queries | Telescope, Larastan, performance tests | Eager loading, `withCount`, denormalized counters |
| Loading full post body in lists | Code review | Select only needed columns; have `excerpt` field |
| Markdown re-render on every read | Code review | Render on save, store `body_html` |
| Counting on every request | Code review | Denormalize counts, update via observer |
| Synchronous external API calls | Code review | Always queue (mail, webhooks, OG gen, indexing) |
| Unbounded query results | Code review | Always paginate; cap max `per_page` |
| Missing indexes | Slow query log, EXPLAIN ANALYZE | Add via migration with ADR if non-obvious |

---

## When Budgets Are Blown

Process when an alert fires:

1. **Acknowledge** within 15 min during business hours, 1h otherwise
2. **Triage** — is this a bug, a load issue, or a long-overdue optimization?
3. **Mitigate** — add caching, scale workers, throttle if necessary
4. **Fix root cause** — write the optimization with tests
5. **Update budgets** if the original was unrealistic (with rationale)

For chronic issues, a budget should not just be "raised" — write an ADR explaining why, and what the new system constraints are.

---

## Frontend Performance

DevHub is server-rendered (Livewire). This gives us a head start on performance, but we still pay attention to:

- **CSS**: Tailwind's purge keeps shipped CSS small (<30KB gzipped)
- **JS**: Livewire core + Alpine + Echo ≈ 50KB gzipped. No SPA framework.
- **Images**: WebP/AVIF with fallback, lazy-loaded below the fold
- **Fonts**: System font stack by default; one custom font for branding, subset and self-hosted
- **Third-party scripts**: zero by default. Each addition requires an ADR.

---

## Open Questions

- At what scale does Octane stop being enough? Probably 1000+ concurrent users; revisit then.
- Do we need a CDN for HTML (not just assets)? Probably yes once we have global readers; defer until that's real.
- Is full-page cache for guests worth the cache invalidation complexity? Measured impact at v0.1 launch will tell us.

---

## See Also

- [ARCHITECTURE.md](./ARCHITECTURE.md) — overall system design
- [METRICS.md](./METRICS.md) — what we track for product, contrasted with this doc's operational focus
- ADR-0006 (planned) — Cache invalidation strategy
