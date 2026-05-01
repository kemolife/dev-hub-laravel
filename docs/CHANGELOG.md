# Changelog

All notable changes to DevHub are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each entry should answer: **what changed** and **why it matters to the user**. Implementation details belong in commits, not here.

---

## [Unreleased]

### Added
- Real-time broadcasting via Laravel Reverb: new comments on a post broadcast on `posts.{id}` channel; clients can subscribe without polling
- Private per-user notification channel (`users.{id}.notifications`) broadcasts when a new notification is created; only the owning user can subscribe
- Presence channel (`posts.{id}.viewers`) tracks who is currently reading a post
- Reactions: authenticated users can toggle 5 reaction types (Like, Insightful, Fire, Heart, Mind Blown) on posts via `POST /api/v1/posts/{slug}/reactions`
- Tags: posts can have up to 5 normalized tags; slugs auto-generated and deduplicated
- Public tag listing and lookup via `GET /api/v1/tags` and `GET /api/v1/tags/{slug}`
- `reactions_count` and `tags` fields now included in all post API responses
- Full-text search endpoint (`GET /api/v1/search`) powered by Meilisearch via Laravel Scout. Supports filtering by author; empty queries return recent published posts. Search queries are tracked (with result count and optional user attribution) for analytics.
- Multi-channel notifications: comment replies, mentions, and new-follower events delivered to in-app inbox and by email
- Per-type, per-channel notification preferences — users can silence email for any notification type while keeping in-app alerts
- Digest mode: opt into a weekly summary email instead of per-event emails (ADR-0022)
- `GET /api/v1/notifications` — paginated list of a user's notifications
- `POST /api/v1/notifications/{id}/read` — mark a single notification as read
- `POST /api/v1/notifications/read-all` — mark all notifications as read
- `DELETE /api/v1/notifications/{id}` — delete a notification
- `GET /api/v1/notification-preferences` — list all preferences with defaults for unconfigured types
- `PUT /api/v1/notification-preferences` — upsert preferences in bulk (ADR-0010)
- Filament admin panel at `/admin` — accessible to Admin and Moderator roles only
- Audit log: all admin actions are recorded in `audit_logs` with actor, action, before/after state, IP, and user agent
- User suspension: admins can suspend users with an optional expiry; suspended users receive 403 on write operations
- Reports: authenticated users can report posts or comments via `POST /api/v1/reports/{type}/{id}` (rate-limited to 5 per hour)

### Changed
- _

### Deprecated
- _

### Removed
- _

### Fixed
- _

### Security
- _

---

## [0.1.0] — 2026-06-XX (planned)

First milestone: core publishing.

### Added
- User accounts with email + GitHub + Google sign-in
- Two-factor authentication
- Markdown post editor with autosave
- Drafts and publishing workflow
- Threaded comments with edit window
- Tags with normalization
- Full-text search via Meilisearch

---

## How to Maintain This File

- Update the `[Unreleased]` section as you ship features
- On release, move `[Unreleased]` content under a new version heading with date
- Keep entries user-facing — "Added markdown editor with live preview" not "refactored MarkdownCast class"
- Group changes under the standard headings (Added/Changed/Deprecated/Removed/Fixed/Security)
- Link to relevant ADRs or issues where useful: `Added bookmarks (#123, ADR-0014)`

## Versioning

- **MAJOR** (1.0.0): breaking API changes, data migrations users must run, removed features
- **MINOR** (0.X.0): new features, backwards-compatible
- **PATCH** (0.0.X): bug fixes, dependency updates

Until v1.0, minor versions may include breaking changes — but they'll be clearly noted.
