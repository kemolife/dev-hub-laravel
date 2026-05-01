# ADR-0022: Recommendation Approach — Rules-Based SQL

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub's personal feed needs to surface content relevant to each user. The question is how to rank and filter posts for the feed. Options range from simple chronological follow-based feeds to machine-learning recommendation engines.

Two competing values are in tension: relevance vs. implementation complexity.

## Decision

We will use a rules-based SQL approach for the personal feed:

- The feed shows posts from followed users in reverse chronological order (`published_at DESC`)
- No ranking signal beyond recency for now
- Implemented in `FeedController` as a straightforward `whereIn('user_id', $followingIds)` query with pagination

This is the "follow graph feed" pattern used by early Twitter, dev.to, and most developer-focused platforms. It is transparent, predictable, and fast to implement.

## Consequences

**Positive:**
- Zero infrastructure cost — no ML pipeline, no recommendation service
- Completely transparent to users: "you see posts from people you follow"
- Fast: single query with an index on `user_id` and `published_at`
- Easy to test and reason about
- Cacheable per-user without complex invalidation

**Negative:**
- No personalization beyond the follow graph
- Cold-start problem: new users with no follows see an empty feed
- Does not surface high-quality posts from non-followed users
- Cannot adapt to individual content preferences

**Mitigations for cold-start:**
- Suggest popular users/tags to follow during onboarding (future work)
- Show trending posts when follow list is empty (future work, ROADMAP.md)

## Alternatives Considered

**1. Collaborative filtering / ML recommendations.**
Rejected for now. Requires significant infrastructure (training pipeline, feature store, serving layer) and a critical mass of interaction data that DevHub does not yet have.

**2. Hybrid: follow graph + engagement score.**
Considered. Would add a ranking signal like `view_count * recency_decay`. Rejected to keep the initial implementation simple. Can be added as a query scope later without an ADR.

**3. Tag-based discovery feed.**
Considered as a complement. Not included in the initial feed but appropriate as a separate "Discover" endpoint in a future iteration.

## How We'll Know We Got It Wrong

- If engagement metrics show users with followers have significantly lower session depth than those without (suggesting the feed quality is poor)
- If the most common user complaint is "I can't find good content to read"
- If new user activation rate (follow ≥ 1 user within 24h) is below 30%
