# ADR-0003: Livewire over Inertia

**Date:** 2026-04-30
**Status:** Accepted
**Deciders:** Vitalii
**Related:** [PRODUCT.md](../PRODUCT.md) (calm positioning)

---

## Context

DevHub needs an interactive frontend. The Laravel ecosystem offers two strong paths:
- **Livewire 3 + Volt + Alpine** — server-rendered components, minimal client JS
- **Inertia + Vue or React** — SPA-feeling app with Laravel backend

Both are excellent. The choice depends on what the product is.

Per [PRODUCT.md](../PRODUCT.md), DevHub is positioned as a calm reading experience. The product principles favor:
- Fast first paint
- SEO-friendly server-rendered HTML
- Minimal JavaScript on user-facing pages
- Reading experience over app-like experience

## Decision

We will use **Livewire 3 + Volt** for the UI, with **Alpine.js** for purely client-side behavior.

## Consequences

**Positive:**
- Server-rendered = excellent SEO and first-paint performance
- Single language (PHP) for most of the codebase — faster iteration solo
- Naturally fits Laravel's request lifecycle
- Volt's single-file syntax keeps components readable
- Tight integration with Eloquent — no API serialization layer needed
- Minimal JS bundle (~50KB gzipped including Alpine + Echo)

**Negative:**
- Less suitable for highly app-like, interactive experiences (we don't have these)
- Real-time interactions require care (debounce, optimistic UI patterns)
- Hiring/contributing pool for Livewire is smaller than Vue/React (acceptable for solo project)
- Some UX patterns (drag-drop, complex animations) are easier with a real client framework

**Mitigations:**
- Use Alpine for purely client-side micro-interactions
- Use Reverb broadcasting for true real-time needs
- For a future drag-drop or rich editor, evaluate dropping in a single Vue/React island within Livewire

## Alternatives Considered

**Inertia + Vue 3.** Strong choice; would have picked this if DevHub were more app-like (e.g., a dashboard product). Rejected because we'd be paying SPA complexity for a content site.

**Inertia + React.** Same as above. The team-of-one factor — staying in one language reduces context switching cost.

**Pure Blade with HTMX or stimulus.** Considered. Livewire is essentially this with better Laravel integration, so picking Livewire wins.

**Full SPA (Vue/Nuxt or Next.js separately).** Would require building a full API layer, doubling the codebase, doubling tests, doubling deploy complexity. Massive overkill for a single-engineer portfolio + product.

## How We'll Know We Got It Wrong

Signals to revisit:
- Significant interactive features feel forced or laggy in Livewire
- We hire frontend specialists who'd be more productive in Vue/React
- A core feature (e.g., a sophisticated editor) demands a real client framework

If triggered: not necessarily a full migration. Could introduce Inertia for specific routes, or render a single-page island via Vue.

## References

- Livewire 3 docs
- ADR-0001 (record architecture decisions)
- PRODUCT.md (principles favoring calm + minimal JS)
