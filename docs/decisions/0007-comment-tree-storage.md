# ADR-0007: Comment Tree Storage — Adjacency List with Materialized Path

**Date:** 2026-05-15
**Status:** Accepted
**Deciders:** Vitalii
**Related:** PRODUCT.md (calm engagement principle), prompt 04 (comments feature)

---

## Context

DevHub needs threaded comments on posts. Per PRODUCT.md, conversation depth matters more than reaction count, so the comment system needs to support nested replies cleanly without performance cliffs.

Expected traffic shape (educated guess from competitive analysis):
- Most posts: 0-20 comments
- Engaged posts: 50-200 comments
- Outlier posts: 500+ comments
- Nesting: most replies are 1-2 levels deep; rare threads go 4-5 deep
- Read pattern: when viewing a post, fetch the entire comment tree once, render with nesting
- Write pattern: append-mostly; edits within a 15-minute window; soft-delete is the norm

The naive "fetch all comments, build tree in PHP" approach works for small posts but becomes a problem at scale: N+1 if we fetch parent-by-parent, or large in-memory trees if we fetch everything.

We need a storage strategy that:
1. Fetches an entire thread for a post in one query
2. Preserves order within siblings (chronological)
3. Allows efficient soft-delete with tombstones (per PRODUCT.md: "[deleted]" placeholder if comment has replies)
4. Handles edits without breaking the tree
5. Keeps the schema simple enough to reason about during incidents

## Decision

We will use **adjacency list (parent_id) with a materialized path column** for ordering and efficient subtree retrieval.

### Schema

```sql
CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id),
    commentable_type VARCHAR(255) NOT NULL,
    commentable_id BIGINT NOT NULL,
    parent_id BIGINT NULL REFERENCES comments(id),
    path TEXT NOT NULL,  -- e.g., "00001/00042/00103"
    body_markdown TEXT NOT NULL,
    body_html TEXT NOT NULL,
    edited_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_comments_commentable ON comments (commentable_type, commentable_id);
CREATE INDEX idx_comments_path ON comments (commentable_type, commentable_id, path);
CREATE INDEX idx_comments_parent ON comments (parent_id);
```

### How it works

- `parent_id` is the source of truth for the tree structure
- `path` is computed on insert: `parent.path + '/' + zero_padded(this.id)`
- Top-level comments have path = zero_padded(id)
- Fetching all comments for a post: one query, ordered by path → naturally yields a depth-first traversal
- Building the tree in PHP is a simple linear pass since results arrive in tree order
- Max depth (4 levels) enforced in `PostCommentAction`, not at DB level

### Soft delete with tombstones

- `deleted_at` is set; `body_markdown` and `body_html` are nulled out
- Render layer shows "[deleted]" for any comment with `deleted_at` not null
- Children are unaffected — they keep their position in the tree

## Consequences

**Positive:**
- One query to fetch a thread; ordering is implicit in the path column
- Schema is simple — anyone reading it understands immediately
- Edits don't affect tree structure (path is stable once written)
- Soft delete with tombstones works naturally
- Eloquent relations (parent, replies) work without exotic libraries

**Negative:**
- `path` is denormalized — must be set correctly on insert
- Moving a subtree (re-parenting) requires updating all descendant paths. We don't support this in v1; if we ever do, it's an O(n) update per subtree.
- Path format limits us to ~5-byte IDs per level (40 byte path / 8 levels). Sufficient given our 4-level enforcement, but a constraint to remember.
- Path-based ordering is chronological-by-id, not by `created_at`. These match in normal flow; if we ever bulk-import historical comments, ordering may surprise.

**Operational notes:**
- An accidental NULL path would break ordering. We add a CHECK constraint and a Pest test that ensures path is always non-null after `PostCommentAction`.
- Index on (commentable_type, commentable_id, path) supports the hot read query directly.

## Alternatives Considered

**1. Pure adjacency list (parent_id only, no path).**
- Pro: simplest schema
- Con: requires either recursive CTE per fetch (Postgres supports this but adds complexity), or N queries to walk the tree, or fetch-all-and-build-in-PHP (works but ordering becomes our problem)
- Verdict: workable but the path column is a small addition that buys us a lot

**2. Nested set model (lft/rgt columns).**
- Pro: very fast subtree queries
- Con: every insert and delete requires updating lft/rgt across many rows. With our append-heavy pattern and frequent comments on hot posts, this means lots of contention and write amplification.
- Con: schema is famously confusing to onboard new developers to
- Verdict: optimized for the wrong workload (read-heavy with rare writes; ours is the opposite)

**3. Closure table (separate table mapping ancestor → descendant).**
- Pro: very flexible, fastest for "all descendants" queries
- Con: storage cost is O(depth × comments) — for a 200-comment thread at avg depth 2.5, that's 500 rows in the closure table for 200 comments
- Con: every insert writes multiple rows transactionally
- Verdict: overkill for our depth limits and comment volumes

**4. Document store (denormalized JSON tree per post).**
- Pro: trivial to fetch and render
- Con: editing a single comment in a 200-comment tree means rewriting the whole document
- Con: concurrent edit conflicts; loss of relational integrity
- Con: search across comments becomes painful
- Verdict: rejected for write contention and integrity reasons

**5. Use a package (`spatie/laravel-comments`, `kalnoy/nestedset`, etc.).**
- Pro: less code to write
- Con: ties us to package's update cadence, opinions
- Con: defeats part of the purpose of this portfolio project (demonstrating that we can model this ourselves)
- Verdict: re-evaluate after v1 if we hit limits with our hand-rolled approach

## How we'll know we got it wrong

Signals that this decision needs revisiting:
- Median comment-thread fetch time exceeds 100ms (path index isn't helping)
- We add a feature requiring subtree moves (e.g., merging threads)
- Path column overflows (would mean we exceeded depth assumptions)
- Comment table grows beyond 10M rows and queries degrade

If any of these trigger, we write ADR-XXXX superseding this one with the new approach.

## References

- Bill Karwin's "Models for Hierarchical Data" talk (the canonical comparison)
- Postgres docs on recursive CTEs
- prompt 04-comments.md for implementation details
