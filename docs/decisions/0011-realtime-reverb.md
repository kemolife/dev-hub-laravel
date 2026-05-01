# ADR-0011: Real-time: Reverb over Pusher

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub requires real-time features: live comment feeds on post pages, per-user notification badges, and presence indicators showing who else is reading a post. These features need a WebSocket infrastructure layer.

The Laravel ecosystem offers two first-class options: Pusher (cloud SaaS) and Reverb (Laravel's own self-hosted WebSocket server, introduced in Laravel 11). Both integrate cleanly with Laravel Broadcasting.

## Decision

We will use **Laravel Reverb** as the WebSocket server.

Reverb runs as a local process (`php artisan reverb:start`), is developed and maintained by the Laravel team, and ships with native Laravel Broadcasting integration — the same `ShouldBroadcast` contracts, `Channel`, `PrivateChannel`, and `PresenceChannel` classes used throughout the ecosystem.

## Consequences

**Positive:**
- No external SaaS dependency; zero cost at all traffic levels.
- Self-hosted: no data leaves the server, which supports our privacy stance.
- First-party: maintained by the Laravel core team, versioned alongside the framework.
- No API key rotation or third-party account management.
- Local dev works out of the box without a Pusher sandbox account.

**Negative:**
- We are responsible for running and scaling the Reverb process in production.
- Reverb is newer than Pusher and has less battle-testing at large scale.
- Horizontal scaling requires a sticky-session or shared state strategy (Redis pub/sub — Reverb supports this).

## Alternatives Considered

**1. Pusher (cloud SaaS)**
Rejected. Introduces a paid third-party dependency, routes WebSocket traffic through external infrastructure, and adds an account/key management burden. For a privacy-conscious portfolio project, shipping data off-server by default is the wrong default.

**2. Soketi (self-hosted Pusher protocol)**
Rejected. Soketi is community-maintained and uses the Pusher protocol. Reverb is a direct Laravel-team deliverable with the same self-hosted benefits and better long-term support trajectory.

**3. Server-Sent Events (SSE) only**
Rejected. SSE is unidirectional. Presence channels and future interactive features require bidirectional communication.

## How We'll Know We Got It Wrong

- Reverb crashes under normal post-publish load (indicating scaling issues).
- The Reverb process requires significant operational overhead beyond `php artisan reverb:start`.
- The Laravel team deprecates Reverb in favor of a different solution.
