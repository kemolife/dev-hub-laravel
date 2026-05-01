# ADR-0021: Tag Normalization Strategy

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

Tags are user-provided strings. Without normalization, "Laravel", "laravel", "LARAVEL", and "laravel " would create four separate tags for the same concept. This fragments the tag space and makes search and filtering unreliable.

We need a canonical form for tags so that:
1. Duplicate tags are unified regardless of how a user types them
2. Tags are URL-safe (used as route slugs)
3. The normalization is consistent across the entire codebase

## Decision

We will normalize all tags via `App\Support\TagNormalizer::normalize()` before persisting to the `tags` table. The normalization rules are:

1. Lowercase with `mb_strtolower()` — UTF-8 safe
2. Strip characters that are not `[a-z0-9\s-]` — removes punctuation and special chars
3. Collapse whitespace runs to a single dash
4. Collapse dash runs to a single dash
5. Truncate to 50 characters

Tags are stored by slug: `firstOrCreate(['slug' => $slug], ['name' => $originalName])`. If the tag already exists (same slug), the original human-readable name from the first creation is preserved.

Maximum 5 tags per post — enforced at both the validation layer and the action layer (via `->take(5)` on the normalized collection).

## Consequences

**Positive:**
- Tag space stays clean — "React.js" and "react js" unify under "reactjs" and "react-js" respectively
- Slugs are inherently URL-safe — no encoding needed in routes
- Simple, no external dependency, easy to unit test

**Negative:**
- Lossy: "C#" normalizes to "c" (hash stripped), "C++" normalizes to "c". Some tech names need display aliases
- Special chars in tech names (# for C#, + for C++) are silently dropped, which may confuse users
- The first user to create a tag "owns" its display name (subsequent uses reuse that name)

**Mitigations:**
- Users can see the actual normalized slug in the API response before trusting what's stored
- A future admin UI could allow renaming canonical tag names without changing slugs
- If "c-sharp" becomes a common workaround, it can be seeded as the canonical name

## Alternatives Considered

**1. Preserve original casing in the canonical name, normalize only the slug.**
Accepted partially — we do store the original `name` alongside the `slug`. But the slug is the identity key.

**2. Allow Unicode characters in tags.**
Rejected. Routes would require percent-encoding, and consistency across search and URL would be harder to guarantee.

**3. Use a curated allowlist / taxonomy.**
Rejected for now. Too much maintenance overhead for a v0.1 feature. Could be revisited when the tag count grows.

## How We'll Know We Got It Wrong

- If user feedback shows they can't tag with important tech names (C#, C++, .NET, etc.)
- If the tag space becomes fragmented despite normalization (e.g., many near-duplicate slugs appearing)
