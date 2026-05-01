# Prompt 04 — Comments (Polymorphic & Nested)

Add threaded comments, reusable across content types.

## Concepts demonstrated

- True polymorphic relationships (`commentable_type`, `commentable_id`)
- Adjacency list for nesting (parent_id) — discuss alternatives in ADR
- Recursive eager loading without N+1
- Soft deletes with "deleted by author" vs "deleted by mod" distinction
- Rate limiting per user
- Mention parsing (@username) → notifications later

## Tasks

1. **Migration**
   - `comments`: id, user_id, commentable_type, commentable_id, parent_id (nullable, self-ref), body_markdown, body_html, edited_at, deleted_at, created_at, updated_at
   - Indexes: composite on (commentable_type, commentable_id), parent_id, user_id

2. **Model: Comment**
   - `commentable()` morphTo
   - `parent()` belongsTo self, `replies()` hasMany self
   - `descendants()` recursive — load entire subtree without N+1 (use a single query with CTE OR a nested-set library — pick one and ADR it)
   - Markdown cast (reuse from Post)
   - Scope `topLevel()` for parent_id null

3. **Actions**
   - `PostCommentAction(user, commentable, body, parent?)` — validates max nesting depth (e.g. 4), parses mentions, fires `CommentPosted` event
   - `EditCommentAction` — only allowed within 15 minutes OR by admin, sets `edited_at`
   - `DeleteCommentAction` — soft delete; if has replies, keep tombstone "[deleted]"; if no replies, hard delete

4. **Mention extraction**
   - `app/Support/MentionParser.php` — extracts @username from markdown, returns User collection
   - Used in `PostCommentAction` to attach mentions for later notification

5. **Livewire**
   - `CommentThread` component, takes commentable, renders nested
   - `CommentForm` for posting + replying (toggleable inline)
   - Optimistic UI: comment appears immediately with "Posting…" state

6. **Rate limiting**
   - Max 10 comments per minute per user
   - Max 50 comments per hour per user
   - First-time commenter (account < 24h) has stricter limits

7. **Policy**
   - `CommentPolicy`: edit/delete by owner within window, or moderator anytime

## Product thinking

- Edit window with countdown ("you can edit for 12 more minutes")
- Show "edited" indicator if `edited_at` set
- "Collapse thread" UX for long discussions
- Empty state: "Be the first to comment"

## Tests

- Polymorphic attachment to Post works
- Nesting respects max depth
- Rate limiting kicks in
- Soft delete with replies leaves tombstone
- Mention parser extracts correctly, ignores email-like patterns

## Definition of Done

- ADR: "Comment tree storage: adjacency list vs nested set vs closure table" with the decision
- ADR: "Edit window rationale and how moderators bypass it"
- `composer check` clean
