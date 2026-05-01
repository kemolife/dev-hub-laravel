# ADR-0023: Webhook Signing Format

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub dispatches webhook events to consumer-supplied URLs when domain events occur (post published, comment posted). Because the delivery travels over the public internet, consumers need a way to verify that a request genuinely originated from DevHub and has not been tampered with in transit.

A signing mechanism is required. The industry has converged on HMAC-based signing, but the specific algorithm, header name, and format are not standardized.

## Decision

We will sign webhook payloads with **HMAC-SHA256**, serializing the raw JSON body as the message and a per-endpoint random secret as the key. The signature is delivered in the `X-DevHub-Signature` header as `sha256=<hex-digest>`.

Per-endpoint secrets are generated with `Str::random(40)` at endpoint creation and stored in plain text in the `webhook_endpoints.secret` column. The event name is also sent in `X-DevHub-Event` for routing without JSON parsing.

**Verification example (consumer side):**
```php
$expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
$trusted = hash_equals($expected, $request->header('X-DevHub-Signature'));
```

## Consequences

**Positive:**
- HMAC-SHA256 is well-understood, documented, and implemented in every language
- Per-endpoint secrets limit blast radius: revoking one endpoint doesn't affect others
- `hash_equals()` timing-safe comparison prevents timing attacks on consumer side
- Format matches GitHub Webhooks — developers already know how to implement it
- Payload integrity is verifiable without a DevHub-specific SDK

**Negative:**
- Secrets are stored in plain text — a DB breach exposes all signing keys. Hashing them would require re-issuing secrets to consumers on each rotation.
- Replay attacks are possible unless consumers implement timestamp validation (not enforced by DevHub)
- Rotating secrets requires consumers to update their stored secret

**Future mitigations:**
- Add `X-DevHub-Delivery-Timestamp` header so consumers can reject stale requests
- Offer secret rotation endpoint that accepts a transition window

## Alternatives Considered

**1. HMAC-SHA512.**
Considered but rejected — SHA256 has identical collision-resistance properties for HMAC use cases, is faster, and is more familiar to developers.

**2. Asymmetric signing (Ed25519 private key, public key for verification).**
Considered. Eliminates the shared-secret storage problem. Rejected for now — implementation complexity (key rotation, public key distribution) exceeds the threat model for a v1 API. Noted in `docs/ROADMAP.md` for future consideration.

**3. JWT payload wrapping.**
Rejected. Over-engineering for a simple integrity check; consumers would need JWT libraries for a use case that raw HMAC handles in three lines.

**4. No signing, rely on HTTPS.**
Rejected. TLS protects transit confidentiality but not authenticity — a malicious actor could still send forged requests to consumer endpoints.

## How We'll Know We Got It Wrong

- Consumer integrations report signature verification failures due to encoding mismatches
- Secret storage in plain text causes a security audit finding
- Developers ask for a rotation endpoint (implement it then)
