# ADR-0023: Referral Reward Structure

**Date:** 2026-05-01
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub tracks referrals (which user invited which new user) via a cookie-based `referral_code` mechanism. The question is what, if anything, to reward the referrer with. Options include extended trial, pro credit, badge, or nothing (tracking only).

Two competing values are in tension: growth incentive vs. reward complexity and potential for abuse.

## Decision

Phase 1 (this feature): **tracking only — no rewards yet.**

We store `referred_by_user_id` on the new user but do not grant any reward. This gives us the data model needed to add rewards later without committing to a reward structure before we have usage data.

Implementation:
- `referral_code` (8-char random string) is generated for every user at creation
- Referral link: `https://devhub.app/?ref={referral_code}`
- `TrackReferral` middleware stores the code in a 30-day cookie
- `RegisterUserAction` reads the cookie and sets `referred_by_user_id` on the new user

## Consequences

**Positive:**
- Zero reward infrastructure needed now
- Data is captured from day one — no retroactive gap
- Avoids reward abuse (fake account farms, etc.) until we have moderation in place
- Easy to add rewards later with a migration and a listener on `Registered`

**Negative:**
- No growth incentive for referrers in the short term
- Referrers may not bother sharing links without an obvious reward

## Alternatives Considered

**1. Extend trial by N days per referral.**
Considered. Simple and high-value but requires Stripe/billing integration to be stable first. Deferred.

**2. Badge / profile achievement for top referrers.**
Considered. Low implementation cost but requires a badge/achievement system that doesn't exist yet. Deferred.

**3. Pro credit / discount.**
Considered. High incentive but complex: needs promo code integration with Stripe and abuse prevention. Deferred.

## How We'll Know We Got It Wrong

- If referral attribution rate is near zero (suggesting users never click referral links)
- If `referred_by_user_id` is set on fewer than 5% of new registrations after 3 months of marketing activity
- If analysis shows referred users have significantly different retention vs. organic users (positive or negative)
