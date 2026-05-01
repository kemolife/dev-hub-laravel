# Changelog

All notable changes to DevHub are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each entry should answer: **what changed** and **why it matters to the user**. Implementation details belong in commits, not here.

---

## [Unreleased]

### Added
- Full-text search endpoint (`GET /api/v1/search`) powered by Meilisearch via Laravel Scout. Supports filtering by author; empty queries return recent published posts. Search queries are tracked (with result count and optional user attribution) for analytics.

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
