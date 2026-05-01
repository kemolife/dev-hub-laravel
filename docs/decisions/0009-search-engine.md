# ADR-0009: Search Engine — Meilisearch via Laravel Scout

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii
**Related:** PRODUCT.md (depth over engagement), ROADMAP.md (feature 06 — Search)

---

## Context

DevHub needs full-text search across posts. The product principle is "depth over engagement-bait" — good search helps users find quality content without needing a personalisation algorithm. The search must:

1. Handle full-text matching across title, excerpt, body, and author username
2. Support filtering by author
3. Return only published posts (drafts/archived must be excluded)
4. Be fast enough to feel instant (<100ms typical)
5. Run self-hosted in development (no external services for local iteration)
6. Not add operational complexity that a solo developer can't manage

We're also tracking every search query (anonymously or by user) for future product decisions: which queries return zero results, what are users searching for that we don't have content for.

---

## Decision

Use **Meilisearch** as the search backend, accessed via **Laravel Scout v11**.

Index settings (filterable/sortable attributes) are declared in `config/scout.php` under `meilisearch.index-settings` — Scout syncs them automatically during `scout:sync-index-settings`. No custom Artisan command is needed.

The `Post` model uses:
- `Searchable` trait from Scout
- `shouldBeSearchable()` — only `PostStatus::Published` posts are indexed
- `toSearchableArray()` — indexes title, excerpt, body (truncated to 5000 chars), author username, status, published_at, view_count
- `searchableAs()` — returns `{prefix}posts`

Index updates are queued (`SCOUT_QUEUE=true`) to keep HTTP response times fast.

Search queries (with result count and optional user ID) are recorded in `search_queries` via `RecordSearchQueryAction`, called with `defer()` so it runs after the response is sent.

For tests, `SCOUT_DRIVER=null` is set in `phpunit.xml`, so the null engine returns empty results without hitting Meilisearch. Tests cover the empty-query fallback path (which goes through Eloquent, not Scout), query recording, and input validation.

---

## Consequences

**Positive:**
- Meilisearch is open-source, self-hosted, and has first-class Scout support
- Typo tolerance and relevance ranking out of the box — no custom scoring needed
- `SCOUT_DRIVER=null` in tests means zero infrastructure needed in CI
- `shouldBeSearchable()` guarantees draft/archived content is never indexed
- `defer()` keeps search responses fast — query recording is post-response work
- Query tracking table is the foundation for future search analytics and "zero results" monitoring
- Scout's `after_commit` support means index updates don't race with transactions

**Negative:**
- Meilisearch must be running locally during development (`brew services start meilisearch` or Docker)
- Index can get out of sync if Meilisearch is down when posts are published — `scout:import` re-syncs
- Null driver in tests means search relevance ranking is not tested — only the HTTP interface and Eloquent fallback are covered

---

## Alternatives Considered

**1. PostgreSQL full-text search (`tsvector` / `GIN` index).**
Rejected. Requires raw SQL or complex query builder expressions (`DB::raw()`), which violates ADR rule #9 without a dedicated ADR. Also lacks typo tolerance, has worse multilingual support, and can't be swapped for a managed service later without a migration. Would couple search to our DB load.

**2. Algolia.**
Rejected. SaaS-only, paid beyond the free tier, and adds a hard external dependency. For a portfolio project that's also learning infrastructure, self-hosted is more educational. Algolia would be the right call if this were a funded product prioritising ops simplicity.

**3. Typesense.**
Close call. Typesense is faster for some workloads and has a strong Scout driver. Rejected because Meilisearch has simpler setup (no schema-first configuration required), better documentation for Scout integration, and is explicitly called out in the Laravel docs as the recommended self-hosted option.

**4. No dedicated search — just `LIKE` queries.**
Rejected. `LIKE '%term%'` can't use indexes on leading wildcards, doesn't support typo tolerance, and won't scale past a few thousand posts. Fine for an MVP but we're explicitly demonstrating senior-level patterns here.

---

## How We'll Know We Got It Wrong

- Search feels slow (>300ms p95) despite `SCOUT_QUEUE=true` — revisit index settings or switch to a managed engine
- Meilisearch memory usage becomes a concern on the production server — evaluate Typesense or Algolia
- Index sync issues become a recurring operational problem — add a scheduled `scout:import` and monitoring
- We need personalised search ranking (e.g., based on followed tags) — re-evaluate whether the `null` test driver gap is a problem at that point
