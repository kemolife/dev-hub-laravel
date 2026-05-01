# ADR-0015: Stripe Over Mollie for Billing

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub needs a subscription billing provider to handle plan upgrades, trials, payment collection, and dunning. Two primary candidates emerged: **Stripe** (global) and **Mollie** (NL/EU-focused). Laravel ships official first-party Cashier integrations for both: `laravel/cashier` (Stripe) and `laravel/cashier-mollie`.

The decision affects:
- Developer experience (documentation quality, SDK maturity)
- Test tooling (how easy it is to fake payments in CI)
- Market reach (which payment methods are supported out of the box)
- Career portfolio value (which provider hiring companies most want to see)

## Decision

We will use **Stripe** (`laravel/cashier`).

## Consequences

**Positive:**
- Stripe is the industry standard for SaaS billing globally; it appears in nearly every Laravel job posting.
- `laravel/cashier` is more mature, better tested, and better documented than `laravel/cashier-mollie`.
- Stripe's hosted checkout handles SCA (Strong Customer Authentication) automatically.
- Excellent webhook tooling and the Stripe CLI enables local webhook testing without external tunneling.
- Stripe's test mode with specific card numbers (`4242...`) makes deterministic testing straightforward.
- Stripe supports 135+ currencies and is available in 46 countries.
- Proration, subscription changes, and dunning retries are handled natively.

**Negative:**
- Stripe is not available in all countries (notably absent from many MENA and parts of SEA).
- For a purely NL/EU market, Mollie's iDEAL, Bancontact, and SEPA integrations provide better local coverage.
- Stripe's pricing (2.9% + $0.30) is slightly higher than Mollie's (1.8% + €0.25 for EU cards).

## Alternatives Considered

**1. Mollie (`laravel/cashier-mollie`)**
Rejected. While Mollie is excellent for the Dutch/Belgian market and offers lower fees for EU cards, DevHub is a portfolio project targeting a global audience. Stripe is the provider hiring companies expect to see in a senior developer's portfolio. Mollie's Cashier integration is less mature and less documented.

**2. Paddle**
Rejected. Paddle acts as a merchant of record, which simplifies VAT but reduces control. It's a niche choice and less relevant for a backend portfolio.

**3. Manual Stripe API (without Cashier)**
Rejected. `laravel/cashier` handles webhook verification, subscription lifecycle, invoice retrieval, and payment method management correctly. Re-implementing these from scratch would be high-risk with no benefit.

## How We'll Know We Got It Wrong

- Cashier's API diverges significantly from the version we're on and causes upgrade pain.
- Hiring context shifts toward Mollie (unlikely for global market).
- Stripe raises prices or restricts access in target markets.
