# DevHub — Laravel Concepts & Product Thinking Showcase

A developer community platform (think dev.to + GitHub Discussions lite) built deliberately to demonstrate Laravel best practices AND product engineering skills.

## Build Philosophy

- **Vertical slices**: Each prompt builds one feature end-to-end (migration → model → service → controller → tests → UI)
- **Document the why**: Every feature gets an ADR in `/docs/decisions/`
- **Test as you go**: Don't accumulate test debt
- **Commit per prompt**: Each prompt = one feature branch = one PR-style commit

## Recommended Order

1. `01-foundation.md` — Project setup, conventions, tooling
2. `02-auth.md` — Authentication, 2FA, social login
3. `03-posts.md` — Posts with markdown, slugs, drafts
4. `04-comments.md` — Polymorphic comments, nested
5. `05-reactions-tags.md` — Polymorphic reactions, tags with pivot data
6. `06-search.md` — Meilisearch via Scout
7. `07-notifications.md` — Multi-channel notifications with preferences
8. `08-realtime.md` — Reverb for live updates
9. `09-api.md` — Public API with Sanctum
10. `10-admin-moderation.md` — Filament admin + audit logs
11. `11-billing.md` — Cashier + Mollie, plans, limits
12. `12-product-features.md` — Onboarding, feedback widget, changelog
13. `13-growth.md` — Referrals, SEO, digest emails
14. `14-observability.md` — Telescope, Sentry, structured logging

## Conventions for Every Prompt

When you give a prompt to Claude Code, prepend this context:

> You are working on DevHub, a Laravel 11 portfolio project demonstrating senior-level patterns. Follow these rules:
> - Use Pest for testing, write tests first when feasible
> - Use Form Requests for validation, never validate in controllers
> - Use Action classes (lorisleiva/laravel-actions style or plain invokable classes) for business logic
> - Use API Resources for all JSON responses
> - Use Eloquent attribute casts and accessors, not manual transformations
> - Add an ADR to `docs/decisions/NNNN-title.md` for non-obvious choices
> - Update `docs/ROADMAP.md` and `docs/CHANGELOG.md` after each feature
> - Run `./vendor/bin/pint` and `./vendor/bin/pest` before declaring done
> - Never put business logic in controllers or models — controllers orchestrate, models hold data + relations
