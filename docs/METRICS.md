# DevHub — Metrics

> What we measure, how we measure it, and what we deliberately ignore.

This document defines our metric strategy. Every dashboard we build, every analytics event we fire, every report we generate should map to something on this page. If a metric isn't here, we don't track it (privacy by default).

**Last updated:** 2026-04-30
**Owner:** Vitalii

---

## North Star Metric

**Average reading time per active reader per week.**

- **Active reader** = user who completed at least one read-through in the past 7 days
- **Read-through** = post view with ≥80% scroll depth AND ≥30 seconds time on page
- **Reading time** = sum of time on page across all read-throughs

**Why this metric:**
Per PRODUCT.md, we win when developers spend meaningful time reading thoughtful work. This metric goes up when we publish better content, surface it better, or make the reading experience better. It does NOT go up by gamifying engagement, sending more notifications, or trapping users in a feed.

**Target trajectory:**
- v0.1 launch: baseline (track from day one)
- v1.0 launch: 25 minutes/reader/week
- Year 1 post-launch: 45 minutes/reader/week

---

## Input Metrics

The metrics we expect to *move* the North Star. Tracked weekly, reviewed monthly.

### Content Quality

| Metric | Definition | Why it matters |
|---|---|---|
| Average post length | Median word count of posts published this week | Proxy for depth |
| % posts with code blocks | Posts containing ≥1 fenced code block / total | Proxy for technical substance |
| % long-form posts | Posts ≥1500 words / total | Direct signal of "calm, deep" content |
| Read-through rate | Read-throughs / total post views | Are people actually reading or bouncing? |
| Bookmark rate | Bookmarks / read-throughs | Strong signal of value (reader plans to return) |

### Engagement (Quality, Not Quantity)

| Metric | Definition | Why it matters |
|---|---|---|
| Comments per post (median) | Median, not average — avoids skew from one viral thread | Healthy conversation |
| Average comment length | Words per comment | Thoughtful vs reactive |
| Edit-rate | % of comments edited within window | Signal of considered writing |
| Reaction-to-read ratio | Reactions per read-through | High ratio + low read-through = engagement bait |

### Acquisition & Activation

| Metric | Definition | Why it matters |
|---|---|---|
| Weekly signups | New verified accounts in past 7 days | Top of funnel |
| Signup → first read-through | % of new users who read 1 full post in 7 days | Activation as reader |
| Signup → first publish | % of new users who publish in 30 days | Activation as writer |
| Source attribution | Signup by referrer (search, referral, direct, social) | Where good users come from |

### Retention

| Metric | Definition | Why it matters |
|---|---|---|
| W1 retention | % returning in week after signup | Onboarding effectiveness |
| W4 retention | % active in week 4 after signup | Habit formation |
| Cohort retention curves | By signup month | Are we improving over time? |
| Resurrection rate | % of dormant users (>30d) who returned | Lifecycle email effectiveness |

### Business

| Metric | Definition | Why it matters |
|---|---|---|
| MRR | Monthly recurring revenue, normalized for annual plans | Sustainability |
| Free → Pro conversion | % of free users on Pro after 90 days | Pricing fit |
| Trial → Paid | % of trials that convert | Onboarding + product fit |
| Voluntary churn | Cancellations / paid users | Product satisfaction |
| Involuntary churn | Failed payments not recovered / paid users | Dunning effectiveness |
| LTV / CAC | Lifetime value / customer acquisition cost | Unit economics |

---

## Anti-Metrics (deliberately not tracked or optimized)

The most important section. These metrics would be easy to surface and would actively harm the product.

| Anti-Metric | Why we don't optimize for it |
|---|---|
| Total time on platform | We want users to read one great post and leave satisfied, not be trapped |
| Page views per session | Same — incentivizes feed-trapping behavior |
| Daily active users (DAU) | Wrong cadence. Weekly is the right frequency for reading. We track WAU instead. |
| Reaction count per post | Per PRODUCT.md, we don't show this on feeds. Tracking it pressures product decisions. |
| Notification open rate | Calm-first. Optimizing this leads to more aggressive notifications. |
| Comments per post (mean) | Median used instead — mean rewards viral drama. |
| Follower counts | Surfaced to users only on profiles, not for ranking or comparison. |
| Streak / consecutive days | Streaks are dark patterns. We will never ship them. |

---

## Operational Metrics

What we track for the system, not the business.

### Performance

- p50, p95, p99 response time per route (alert at p95 > 500ms)
- Time to first byte for post show pages (target <200ms cached, <400ms uncached)
- Database query count per request for hot paths (budgeted in tests)
- Cache hit rate for post renders, user profiles, tag pages

### Reliability

- Error rate per route (alert at >0.5%)
- 5xx count per hour (alert at >10)
- Queue depth (alert at >1000 jobs)
- Failed job rate (alert at >1% over 1h window)
- Webhook delivery success rate

### Security & Compliance

- Failed login attempts per hour (alert at >100)
- 2FA adoption rate (% of users with 2FA enabled — track quarterly)
- Account takeover indicators (logins from new geos, password resets)
- GDPR data requests handled within 30 days: target 100%

---

## How Metrics Are Tracked

| Metric type | Tool | Notes |
|---|---|---|
| Product analytics | Custom events table + Filament dashboard | Self-hosted, no third-party tracking |
| Performance | Sentry Performance + Inspector.dev | Alerts to Slack |
| Errors | Sentry | Slack alert on new error patterns |
| Business / billing | Cashier data + custom queries | Filament admin dashboard |
| Operational | Horizon + Telescope (non-prod) + log aggregation | Daily report email |

**Privacy stance:** No third-party analytics scripts on user-facing pages. We use server-side event tracking with hashed user IDs. No fingerprinting, no cross-site tracking. This is documented in our privacy policy.

---

## Review Cadence

- **Weekly** (Monday): operational + acquisition metrics, addressed in solo "ops review"
- **Monthly** (first Tuesday): full input metrics review, look for cohort patterns
- **Quarterly**: North Star trajectory, anti-metric audit (are we accidentally tracking something we said we wouldn't?), retire metrics that aren't actionable
- **Annually**: full metric strategy review — does this still match PRODUCT.md?

---

## Action-Triggering Thresholds

Metrics are useless if no one acts on them. These are the thresholds where we DO something:

| Trigger | Action |
|---|---|
| Read-through rate drops >10% week-over-week | Investigate: content quality? UX regression? |
| Trial → Paid below 8% for 2 consecutive months | Pricing or onboarding review |
| W1 retention below 30% for new cohort | Onboarding redesign |
| Voluntary churn above 5%/month | Cancellation flow review, customer interviews |
| p95 response time above 500ms for >1h | Page on-call (currently: me) |
| Queue depth above 1000 for >15min | Scale workers, investigate cause |

---

## What's NOT Here

Things you might expect on a metrics page that we deliberately omit:

- **Vanity counters** (total users, total posts) — useful for press releases, useless for decisions
- **Per-user time-on-site dashboards** — privacy violation, also see anti-metrics
- **Predictive churn scoring** — premature; revisit at 1000+ paid customers
- **Detailed funnel analytics for every page** — complexity tax; track funnels for the 3 critical paths only (signup, publish, upgrade)

---

## Open Questions

- Is read-through (80% scroll + 30s) the right definition? Should it be time-based only? Test in v0.2.
- How do we measure "calm" without measuring it? (Currently a vibes-check via user interviews; consider adding a quarterly NPS-style survey with custom questions.)
- Should we track post-level metrics for authors (their reads, their bookmarks) as a Pro feature? PRODUCT.md says yes for Pro. Implement v0.3.

---

This document is a living artifact. Update via PR with rationale.
