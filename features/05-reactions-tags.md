# Prompt 05 — Reactions & Tags

Two small features that demonstrate two important Eloquent patterns.

## Concepts demonstrated

- Polymorphic many-to-many (reactions) with extra pivot data
- Standard many-to-many with rich pivot model (post_tag with weight)
- Aggregate caching strategies (`reactions_count`, `withCount`)
- Idempotent toggle operations
- Tag normalization

## Part A: Reactions

1. **Migration `reactions`**
   - id, user_id, reactable_type, reactable_id, type (enum: like, insightful, fire, heart, mind_blown), created_at
   - Unique index on (user_id, reactable_type, reactable_id, type)

2. **Model: Reaction**
   - `reactable()` morphTo
   - `ReactionType` enum with display label + emoji + color

3. **Action: `ToggleReactionAction(user, reactable, type)`**
   - Idempotent: if exists, remove; else create
   - Updates denormalized counter on parent (`reactions_count`) atomically
   - Fires `ReactionToggled` event for notification fan-out

4. **Trait: `HasReactions`**
   - Add to Post and Comment
   - Provides `reactions()`, `reactionCounts()` (cached, grouped by type), `reactedBy(user, type?)`

5. **Livewire**
   - `ReactionBar` component showing 5 reaction types with counts, highlights user's reactions
   - Click toggles, optimistic UI

## Part B: Tags

1. **Migrations**
   - `tags`: id, name, slug (unique), description, color, posts_count (denormalized), created_at
   - `post_tag`: post_id, tag_id, weight (default 1.0), added_by_user_id, created_at — composite PK

2. **Model: Tag**
   - Auto-slug on save
   - Scope `popular()` ordered by posts_count
   - `posts()` belongsToMany with `withPivot('weight', 'added_by_user_id')->withTimestamps()`

3. **Tag normalization**
   - `app/Support/TagNormalizer.php` — lowercase, strip special chars, collapse whitespace ("React.js" and "reactjs" both normalize the same way — decision: ADR it)
   - Used when attaching tags to posts

4. **Action: `SyncPostTagsAction(post, tagNames[])`**
   - Normalizes names, finds-or-creates tags
   - Syncs to post, updates `posts_count` on affected tags
   - Limit max 5 tags per post

5. **Livewire**
   - `TagInput` component with typeahead (suggests from existing tags), max 5
   - `/tags` index page with popular tags
   - `/tags/{slug}` showing posts with that tag

## Product thinking

- Show reaction counts but only show *who* reacted on hover (privacy)
- Tag suggestions based on post content (call simple keyword matcher — leave hook for AI later)
- Trending tags on homepage (last 7 days)

## Tests

- Toggle reaction is idempotent (calling twice returns to original state)
- Counter stays consistent under concurrent toggles (use database transaction + lock)
- Tag normalization handles unicode, emojis, very long names
- Max 5 tags enforced

## Definition of Done

- ADR: "Denormalized counters vs withCount on every query" with performance reasoning
- ADR: "Tag normalization rules"
- `composer check` clean
