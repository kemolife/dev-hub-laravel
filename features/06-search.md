# Prompt 06 — Search (Scout + Meilisearch)

Add full-text search with filters and facets.

## Concepts demonstrated

- Laravel Scout configuration
- Meilisearch driver
- Custom searchable arrays
- Filterable/sortable attributes
- Search-as-you-type UI
- Index lifecycle (queued indexing, re-indexing strategy)

## Tasks

1. **Install Scout + Meilisearch driver**
   - Configure Meilisearch service in Sail (already set up if you did prompt 01)
   - Set queue connection for Scout so indexing happens in background

2. **Searchable models**
   - Post: index id, title, excerpt, body_markdown (truncated), tags (array), author_username, published_at (timestamp), reactions_count, status
   - Make Post `Searchable`, override `toSearchableArray()`, override `searchableAs()` for index name with env prefix
   - `shouldBeSearchable()` returns true only when status === published

3. **Index settings**
   - Configure Meilisearch index settings via a custom Artisan command `search:configure`
   - Filterable: tags, author_username, status
   - Sortable: published_at, reactions_count
   - Searchable attributes order: title, tags, excerpt, body

4. **Search UI**
   - `/search?q=` page with Livewire component
   - Live search-as-you-type (debounced 300ms)
   - Filter sidebar: tag, author, date range
   - Result snippets with highlighted matches (Meilisearch returns `_formatted`)
   - Empty state with suggestions

5. **Re-index command**
   - `php artisan scout:reindex` (custom wrapper) that flushes and re-imports with progress bar
   - Document when to run it (after schema changes to searchable array)

## Product thinking

- Track search queries in a `search_queries` table (query, results_count, user_id?, created_at) — feeds future "popular searches" and "no-results" reports
- Show "Did you mean?" by re-querying with typo tolerance
- Save recent searches per user

## Tests

- Search returns published posts only
- Filter by tag works
- Empty query returns recent posts (or empty state, your call)
- Mock Meilisearch in tests, don't hit real instance

## Definition of Done

- ADR: "Why Meilisearch over Algolia/Typesense/Postgres FTS for this project"
- `docs/RUNBOOK.md` created with re-index instructions
- `composer check` clean
