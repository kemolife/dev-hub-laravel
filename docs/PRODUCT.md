# DevHub — Product Document

> A calmer place for developers to read and write deep technical content.

This document defines who DevHub is for, what problem it solves, and the principles that guide every product decision. If a feature doesn't fit this document, we don't build it — or we update the document deliberately.

---

## The Problem

Developer publishing platforms have drifted toward engagement maximization. Front pages are dominated by listicles, hot takes, AI-generated SEO content, and "I quit my $500k job to..." narratives. Genuinely deep technical writing — the long debugging stories, the architectural decision walkthroughs, the patient explanations of hard concepts — gets buried.

The developers who *want* to read and write that kind of content are increasingly leaving these platforms for personal blogs and RSS, fragmenting the audience and making good writing harder to find.

## Who It's For

**Primary user: The Thoughtful Practitioner**
- 5+ years of professional development experience
- Reads technical content during focused time, not while doomscrolling
- Wants depth over hot takes
- Willing to spend 20 minutes on a great post; uninterested in 5-item listicles
- Frustrated by current platforms feeling like LinkedIn for code

**Secondary user: The Technical Writer**
- Already writes (personal blog, internal docs, conference talks)
- Wants distribution without selling their soul to algorithms
- Cares about who reads them, not just how many

**Explicitly NOT for:**
- Beginners looking for tutorial roundups (well-served by existing platforms)
- Brand marketers and content-as-funnel writers
- Engagement farmers who measure success in reactions per minute

## What Makes DevHub Different

1. **No engagement metrics on the front page.** No vanity counters in the feed. Reaction counts visible only on the post itself.
2. **Reading time, not "trending."** Surfacing prioritizes depth markers (length, code blocks, references) and explicit signals (bookmarks, shares), not raw reactions.
3. **Reactions are emotional, not gamified.** Five reaction types (insightful, fire, mind-blown, heart, like) — no "score." No leaderboards.
4. **Comments are conversations, not first-takes.** 5-minute mandatory delay before posting? *Considered, see ADR-0007.* Edit window of 15 minutes — encourage thinking before clicking.
5. **Plain text first.** Editor encourages prose. Code blocks render beautifully. No GIF reactions, no AI-generated cover images by default.
6. **Calm notifications.** Daily digest by default. Real-time only when *you* enable it per type.
7. **Open API and data export.** Your writing is yours. Export everything any time. No lock-in.

## Non-Goals

We've deliberately said no to:

- **Video content.** Other platforms do this well; we don't.
- **Course/monetization for creators.** Possibly later, definitely not v1.
- **Social graph as primary.** Following exists, but the home feed is content-first, not who-you-follow-first.
- **Real-time chat.** Comments are async. People who want chat have Discord.
- **Mobile app.** Responsive web only. Adding native apps without revenue is a maintenance trap.
- **AI-generated content amplification.** Human-written by default; AI assistance disclosed.

## Principles for Decision-Making

When in doubt, prefer:

1. **Calm over urgent** — 24h digest > push notification
2. **Depth over breadth** — fewer features, executed completely
3. **Author over algorithm** — let readers choose what to read, don't trap them in a feed
4. **Privacy over personalization** — don't ask for data we don't need
5. **Boring over flashy** — if it's not on the user's path, don't build it
6. **Reversible over permanent** — soft delete, undo windows, edit history

## Success Metrics (North Star + Inputs)

**North Star: Average reading time per active reader per week.**
Not posts published, not signups, not DAU. We win when people spend meaningful time reading thoughtful work.

**Input metrics tracked weekly:**
- Average post length (words) for posts published this week
- % of posts with code blocks
- Read-through rate (scroll depth × time on page)
- Bookmark rate (bookmarks / views)
- Comment thoughtfulness proxy: average comment length, % of comments edited within window
- Weekly active readers (read ≥1 post fully)
- Weekly active writers (published or saved a draft)

**Anti-metrics — things we explicitly do NOT optimize for:**
- Time spent on the platform overall (we'd rather they read one great post and leave satisfied)
- Reaction count
- Page views per session
- Push notification open rate

## Pricing & Business Model

**Free tier (forever):**
- Unlimited reading
- Up to 5 posts/month
- Basic profile
- Email digest

**Pro (€9/month, €90/year):**
- Unlimited posts
- Custom domain (yourname.devhub.app or your own)
- Public API access (1000 req/h)
- Reader analytics (read-through, geography — privacy-respecting, aggregated)
- Custom profile theme
- Priority support

**Why this model:**
- Free tier large enough to be genuinely useful (not freemium-as-bait)
- Pro priced where individuals can pay personally (€9 < a coffee subscription)
- No ads, ever. ADR-0011 documents the rejection of an ad-supported model.
- No "team" tier in v1 — keep focus on individual writers.

## Roadmap Summary

(Full detail in `ROADMAP.md`)

- **v0.1 — Core publishing**: auth, posts, comments, tags, search
- **v0.2 — Calm engagement**: reactions, follow, notifications with preferences
- **v0.3 — Identity**: API, public profiles, custom domains
- **v0.4 — Sustainability**: billing, Pro features
- **v0.5 — Trust**: moderation, audit, reporting
- **v1.0 — Polish**: onboarding, growth, observability, public launch

## Competitive Position

| Platform | Vibe | Our Take |
|---|---|---|
| dev.to | Welcoming but algorithm-driven, increasingly listicle-heavy | We're for readers who outgrew it |
| Hashnode | Strong personal blog focus | Closest competitor; we differentiate on community + calm defaults |
| Medium | Paywalled, generalist | Wrong audience |
| Substack | Newsletter-first | Wrong format for technical depth |
| Personal blogs + RSS | Pure but isolating | We aim to capture this audience by being calm enough to feel like an RSS reader, social enough to find new writers |

## Open Questions

Things we don't know yet, tracked here so we don't pretend we do:

- Does the "calm" positioning convert, or do users default to wanting engagement signals back?
- Does mandatory reading-time-before-comment improve discussion quality, or just frustrate?
- Will Pro convert at >2% of MAU, the threshold for sustainability at our cost structure?
- How do we handle the cold-start problem (no readers → no writers → no readers)?

## Glossary

- **Active reader**: user who completed reading ≥1 post in the past 7 days
- **Active writer**: user who published or saved a draft in the past 30 days
- **Read-through**: a post view where the user reached ≥80% scroll depth and spent ≥30 seconds
- **Calm default**: when a feature has a "loud" and "quiet" setting, quiet is default

---

**Last updated:** 2026-04-30
**Owners:** Vitalii (founder, engineer)
**Status:** Living document — update via PR with rationale
