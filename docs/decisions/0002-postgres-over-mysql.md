# ADR-0002: Postgres over MySQL

**Date:** 2026-04-30
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub needs a relational database. The Laravel ecosystem supports MySQL, MariaDB, Postgres, SQLite, and SQL Server natively. We need to pick one for development and production.

Selection criteria:
- First-class JSON support (we'll use JSONB for things like notification preferences, audit log diffs, feature flags)
- Strong type system (real enums, not just CHECK constraints)
- Full-text search as a fallback if Meilisearch is unavailable
- Recursive CTEs (useful for the comment tree, even if we don't use them in v1)
- Reliable at scale; well-supported by Laravel + Forge + Hetzner

## Decision

We will use **PostgreSQL 16** as the primary database in all environments.

## Consequences

**Positive:**
- JSONB columns with proper indexing for flexible structured data
- Native enum types (vs string + check constraint in MySQL)
- Better adherence to SQL standards
- Excellent full-text search capabilities (a useful safety net)
- Recursive CTEs available
- Better concurrency model (MVCC, no metadata locks on DDL)
- All tooling (Forge, Sail, Hetzner) supports it equally well

**Negative:**
- Slightly less "default" choice in PHP world, so some package authors test MySQL first
- Some Laravel packages assume MySQL idioms (rare but happens)
- pgsql extension required (handled by Sail)

## Alternatives Considered

**MySQL 8 / MariaDB.** The Laravel "default." Excellent, well-supported. Rejected primarily because Postgres's type system and JSONB give us more without significant downside.

**SQLite.** Fine for dev, not appropriate for production at any meaningful scale.

**Managed cloud DB (RDS, etc.).** Out of budget for v1; revisit when revenue allows.

## References

- Postgres JSONB docs
- Laravel Postgres support documentation
