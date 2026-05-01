# Prompt 03 — Posts (Core Content)

Build the posting system — the heart of DevHub. This is where Eloquent mastery and the Action pattern really show.

## Concepts demonstrated

- Form Requests, Action classes, API Resources
- Custom Eloquent casts (Markdown cast: stores raw, returns rendered)
- Accessors/mutators with the modern attribute syntax
- Slug generation via Observer
- Soft deletes + global scope
- Local query scopes (`->published()`, `->draft()`, `->trending()`)
- Model events for cache invalidation
- Polymorphic relations setup (so comments/reactions can attach later)
- Draft autosave (product feature)
- Reading time calculation (value object)

## Tasks

1. **Migrations**
   - `posts`: id (uuid or bigint+public_id), user_id, title, slug, excerpt, body_markdown, body_html (cached render), reading_time_seconds, status (enum: draft/published/archived), published_at, view_count, created_at, updated_at, deleted_at
   - Indexes on slug (unique), user_id, status+published_at, deleted_at

2. **Model: Post**
   - Use the `Status` PHP enum, cast it
   - Custom `MarkdownCast` for `body_markdown` that, on set, also writes `body_html` (using league/commonmark)
   - Accessor for `reading_time` returning a `ReadingTime` value object (in `app/Support/`)
   - Scopes: `published()`, `draft()`, `trending()` (last 7 days, weighted by views + reactions later)
   - Relationships: `user()`, `comments()` (morphMany via polymorphic, set up now), `reactions()` (morphMany), `tags()` (belongsToMany with pivot)

3. **Slug + observer**
   - `PostObserver` generates slug from title on `creating`, ensures uniqueness with suffix
   - On `updating` if title changes and post is still draft, regenerate slug; if published, keep slug stable
   - Invalidate relevant caches on save/delete

4. **Actions (in `app/Actions/Posts/`)**
   - `CreateDraftAction` — minimal data, returns Post
   - `UpdatePostAction` — handles status transitions, validates draft → published only allowed if title + body present
   - `PublishPostAction` — sets published_at, fires `PostPublished` event
   - `ArchivePostAction`
   - Each action takes a DTO (spatie/laravel-data) not raw arrays

5. **Form Requests**
   - `StorePostRequest`, `UpdatePostRequest` — return DTOs via `toData()` method
   - Authorize via `PostPolicy`

6. **PostPolicy**
   - `view`: published OR owner OR admin
   - `update`, `delete`: owner OR admin
   - `publish`: owner only

7. **Controllers (thin)**
   - `PostController` (web) for index/show
   - `PostManagementController` for create/edit/store/update/destroy
   - Both delegate to actions

8. **Livewire components**
   - `PostEditor` — markdown editor with live preview, autosave every 10s via debounced wire:model
   - `PostList` — paginated, filterable by tag/author
   - `PostShow` — renders post, increments view_count via queued job (debounced per user/IP)

9. **Events**
   - `PostPublished` event — listeners will be added in later prompts (notifications, search indexing, OG image generation)

10. **Product features baked in**
    - Draft autosave with "Saved 3 seconds ago" indicator
    - Word count + reading time shown live in editor
    - Empty state on `/posts` when none exist (with CTA)
    - SEO: meta tags, canonical URLs, OpenGraph tags, JSON-LD structured data

## Tests to write

- Feature: full publish flow, draft → published, slug stability
- Feature: policy enforcement (other user can't edit)
- Unit: MarkdownCast renders correctly, ReadingTime calculation
- Unit: Each Action class with mocked dependencies
- Feature: autosave endpoint creates/updates draft

## Definition of Done

- ADR: "Why Action classes over fat services" with concrete example
- ADR: "Markdown rendering strategy: render on save vs render on read"
- A real post with code blocks renders beautifully on the show page
- `composer check` clean
