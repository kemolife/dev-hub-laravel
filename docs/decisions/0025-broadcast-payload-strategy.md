# ADR-0022: Broadcast Payload Strategy: Minimal Payload, Frontend Re-fetches

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

When broadcasting events over WebSockets, we must decide how much data to include in the payload. Two extremes exist: a full serialized resource (all fields needed to render the UI), or a minimal payload (just enough IDs/hints for the frontend to re-fetch via the existing REST API).

This decision affects payload size, data freshness, consistency with the API layer, and coupling between the broadcast system and the API resource layer.

## Decision

We will broadcast **minimal payloads** — only the primary key(s) and a small amount of metadata needed for routing decisions. The frontend re-fetches full data from the REST API when it receives a broadcast event.

Example for `CommentPosted`:
```json
{
  "comment_id": 42,
  "author": "vitalii",
  "created_at": "2026-05-01T12:00:00Z"
}
```

The frontend receives this, uses `comment_id` to call `GET /api/v1/posts/{post}/comments`, and renders fresh data.

## Consequences

**Positive:**
- Single source of truth: API resources define the shape of data in one place, not duplicated in `broadcastWith()`.
- Broadcast payload is always valid regardless of evolving API Resource changes.
- No risk of broadcasting stale, partial, or authorization-bypassing data.
- Smaller payloads reduce WebSocket bandwidth.
- Frontend cache invalidation is explicit: the re-fetch updates the cache naturally.

**Negative:**
- One extra HTTP round-trip per broadcast event (re-fetch after receiving the event).
- Slightly higher latency for the user to see fully-rendered new content.
- Frontend must manage loading states between receiving the event and completing the re-fetch.

## Alternatives Considered

**1. Full payload broadcast (serialize entire API Resource into `broadcastWith()`)**
Rejected. Duplicates the API Resource shape into every broadcast event. Any API change must be mirrored in the event class. Risk of broadcasting fields that should be authorization-gated.

**2. Partial payload (subset of API Resource fields)**
Rejected. Still requires duplication and introduces inconsistency: the same resource looks different via REST vs WebSocket. Frontend must handle two different shapes.

## How We'll Know We Got It Wrong

- Broadcast events frequently trigger re-fetches that return 404 or stale data (indicating a timing problem that warrants embedding critical fields).
- Profiling shows the extra round-trip is the dominant latency contributor in real-time UX.
- Frontend code becomes littered with complex re-fetch-on-broadcast logic that a full payload would have avoided.
