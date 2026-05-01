# ADR-0023: No Admin Impersonation in API-Only Context

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

Admin panels commonly include a "Log in as user" (impersonation) feature that allows admins to view the application from another user's perspective, which is useful for debugging reported issues and verifying account states. Several Laravel packages (e.g., `lab404/laravel-impersonate`) provide this functionality.

DevHub is an **API-only backend**: there is no server-side session-rendered frontend. The Filament admin panel uses standard web sessions, but all application endpoints are consumed by API clients using Sanctum token authentication. Impersonation in this architecture would require one of:
1. Issuing a Sanctum token on behalf of another user (admin escalation to create tokens)
2. A session cookie swap mechanism (only meaningful in server-rendered apps)

## Decision

We will **not implement admin impersonation** in this feature. No impersonation code, routes, or UI will be added.

Admins who need to investigate user-reported issues will instead use:
- The Filament admin panel to inspect user records, audit logs, and reports directly
- The audit log to trace what actions a user took
- Direct database inspection via the admin panel's read-only query interface

## Consequences

**Positive:**
- No privilege escalation vector: an admin cannot mint tokens for arbitrary users
- Simpler security audit surface — no "acting as" session state to reason about
- No dependency on an impersonation package

**Negative:**
- Admins cannot reproduce bugs from the exact perspective of a specific user
- Investigating client-side rendering issues (React frontend) is harder without acting as the user's token

**Mitigations:**
- Audit logs provide a detailed paper trail of user actions
- If user debugging becomes critical, a scoped "read-only shadow mode" can be designed with a proper ADR at that time

## Alternatives Considered

**1. Token-based impersonation (issue temporary Sanctum token for target user)**
Rejected. This introduces a high-risk admin action: a compromised admin account could issue tokens for any user. Requires careful audit logging, short-lived tokens, and re-authentication prompts — disproportionate complexity for the current use case.

**2. lab404/laravel-impersonate**
Rejected. Designed for session-based apps. Not directly applicable to an API-first architecture without significant adaptation.

## How We'll Know We Got It Wrong

- If support/debugging velocity becomes consistently blocked by inability to reproduce user-specific issues
- If user reports require more than 30 minutes of investigation due to lack of impersonation tooling
