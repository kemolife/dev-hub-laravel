# ADR-0020: Denormalized Counters for Reactions and Post Counts

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub needs to display reaction counts on posts and tag post counts efficiently. The naive approach is to issue a `COUNT(*)` query on the `reactions` table every time a post is displayed. For a listing of 15 posts, this means 15 extra queries or a complex `withCount()` subquery on every request.

Two competing values are in tension: query simplicity vs. write complexity.

## Decision

We will maintain denormalized counter columns:
- `posts.reactions_count` — incremented/decremented by `ToggleReactionAction`
- `tags.posts_count` — recomputed by `SyncPostTagsAction` after each tag sync

Cache (`reaction_counts` key, 5-minute TTL) is maintained by the `HasReactions` trait's `reactionCounts()` method for the per-type breakdown, which is less frequently needed than the aggregate.

## Consequences

**Positive:**
- Post listing queries stay simple: no join or subquery required for the count
- Reading is fast — count is just a column value
- Consistent with Laravel's `withCount()` pattern but without the extra query cost

**Negative:**
- Write path is more complex: both the reaction row and the counter must be updated atomically
- Counter can drift if a reaction is deleted outside the action (e.g., via direct DB manipulation in tests or migrations)
- `tags.posts_count` is recalculated on every sync (not incremental) which is safe but O(n) per sync

**Mitigations:**
- `ToggleReactionAction` is the only write path for reactions — encapsulation protects consistency
- `SyncPostTagsAction` uses a real `COUNT` to recompute rather than increment/decrement to avoid drift

## Alternatives Considered

**1. `withCount()` on every request.**
Rejected. Adds a correlated subquery to every post query. On a listing page with 15 posts, this is negligible, but it grows as traffic scales and the extra query surface makes caching harder.

**2. Redis counters only (no DB column).**
Rejected. Makes the count unavailable in raw SQL queries, complicates migrations, and loses consistency if Redis is flushed.

**3. Event-driven counter update via a queued listener.**
Rejected for now. Adds eventual consistency lag — the count could be wrong for seconds/minutes after a reaction. Given reactions are a real-time interaction, users expect immediate feedback.

## How We'll Know We Got It Wrong

- If `reactions_count` is frequently out of sync with actual row counts (detected by a periodic reconciliation job)
- If tag `posts_count` causes write contention under high post creation load
