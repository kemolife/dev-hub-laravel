# ADR-0022: API Versioning Strategy

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub exposes a public REST API for third-party integrations, mobile clients, and developer webhooks. As the API evolves, breaking changes are inevitable — field renames, removed endpoints, changed response shapes. Without a versioning strategy, every change risks breaking existing consumers.

Two mainstream strategies exist: URL-based versioning (`/api/v1/`) and header-based versioning (`Accept: application/vnd.devhub.v1+json`). The choice has long-term implications for routing, documentation, caching, and consumer DX.

## Decision

We will use **URL-based versioning** with the prefix `/api/v1/`, enforced via the `apiPrefix` option in `bootstrap/app.php`.

All current routes live under `v1`. When breaking changes are needed, a `v2` prefix is added in parallel, with `v1` maintained until sunset.

## Consequences

**Positive:**
- Immediately visible in browser address bars, logs, and analytics — no header inspection required
- Works transparently with all HTTP clients, CDNs, load balancers, and proxies
- Scramble generates separate docs per version automatically via prefix
- Route caching and testing are straightforward
- Consumer migration is explicit: update the URL once

**Negative:**
- URLs are technically "impure" REST — version is not a resource concept
- Parallel version maintenance doubles testing surface during transition
- Clients that hard-code base URLs need updating on major version bumps

## Alternatives Considered

**1. Header-based versioning (`Accept` or custom `X-API-Version`).**
Rejected. Invisible to developers browsing in a browser or curl, harder to test, and not supported by most OpenAPI tooling out of the box. The DX cost outweighs the theoretical purity.

**2. No versioning, just deprecation notices.**
Rejected. Without explicit versioning, every change is a potential breaking change. Unacceptable for a public API.

**3. Query parameter versioning (`?version=1`).**
Rejected. Parameters can be stripped by caches and proxies. Semantics are muddier — the version isn't really a query, it's a contract.

## How We'll Know We Got It Wrong

- Consumers are routinely confused about which version to call
- Scramble docs fail to separate versions clearly
- We find ourselves maintaining 3+ active versions simultaneously (sign: versioning too fine-grained)
