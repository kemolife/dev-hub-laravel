# DevHub — Roadmap

> What's shipped, what's next, what we're not doing.

This roadmap is honest about timing, ruthless about priorities, and public about cuts. It maps to the PRODUCT.md principles — if a feature isn't here, it's either explicitly cut (see "Not Doing") or hasn't earned its place yet.

**Last updated:** 2026-04-30
**Status convention:** ✅ Shipped · 🚧 In Progress · 📋 Planned · 🔬 Researching · ❌ Not Doing

---

## Now (current focus)

**v0.1 — Core Publishing** · 🚧 In Progress · Target: 2026-06

The minimum viable platform: a developer can sign up, write a post, and have someone read it.

- ✅ Project foundation (Laravel 11, Postgres, Redis, Sail, CI)
- ✅ Authentication with 2FA + GitHub/Google login
- 🚧 Posts: markdown editor, drafts, publishing, slugs
- 📋 Comments: polymorphic, nested, edit window
- ✅ Tags with normalization
- ✅ Search via Meilisearch

**Done means:** A new user can sign up, publish a post, get a comment on it, and find it via search. No more, no less.

---

## Next (planned, sequenced)

**v0.2 — Calm Engagement** · Target: 2026-07

The features that make DevHub a community without becoming a feed.

- ✅ Reactions (5 types, no leaderboard)
- 📋 Follow system + personal feed
- 📋 Notifications with multi-channel preferences
- 📋 Real-time updates via Reverb
- 📋 Weekly digest emails

**Done means:** A returning user has a reason to come back beyond writing.

---

**v0.3 — Identity & Openness** · Target: 2026-08

Making writers feel ownership of their presence.

- 📋 Public REST API with token abilities
- 📋 Webhooks
- 📋 Custom profile pages
- 📋 Data export (full account archive)
- 📋 OG image generation
- 🔬 Custom domains (yourname.devhub.app + bring-your-own)

**Done means:** A writer can treat DevHub as a writing platform and a personal blog simultaneously.

---

**v0.4 — Sustainability** · Target: 2026-09

The business has to work or the principles are theoretical.

- 📋 Cashier (Mollie) integration
- 📋 Pro plan with limits enforcement
- 📋 Trial flow
- 📋 Dunning + grace period
- 📋 BTW/VAT handling for EU
- 📋 Internal admin dashboard for billing operations

**Done means:** First paid customer can subscribe, get value, and renew without manual intervention.

---

**v0.5 — Trust & Safety** · Target: 2026-10

Required before public launch. Nothing here is glamorous; all of it is non-negotiable.

- 📋 Filament admin panel
- 📋 Reports queue with bulk actions
- 📋 Audit logging
- 📋 User suspension flow
- 📋 Impersonation with safety guardrails
- 📋 Tag merging

**Done means:** A solo operator can moderate a community of 1000+ users without burning out.

---

**v1.0 — Public Launch** · Target: 2026-12

The polish that makes the difference between "side project" and "product."

- 📋 Onboarding checklist
- 📋 Empty states everywhere
- 📋 Feedback widget
- 📋 Public roadmap (this document, but in-app)
- 📋 Changelog page with subscribe
- 📋 Feature flags via Pennant
- 📋 Referral system
- 📋 SEO infrastructure (sitemap, structured data)
- 📋 Lifecycle emails
- 📋 Status page
- 📋 Sentry + Horizon + structured logging
- 📋 Backup + restore tested
- 📋 Performance budget tests

**Done means:** Public on Hacker News, ready for real traffic, ready for real failure modes.

---

## Researching

Things we're considering but haven't committed to. No promises.

- 🔬 **AI-assisted writing**: outline generation, code block explanation. *Concern:* directly contradicts our human-first positioning. If we ship it, AI use will be disclosed per-post.
- 🔬 **RSS import**: let writers cross-post from existing blogs. *Concern:* duplicate content SEO penalties.
- 🔬 **Newsletter feature**: writers send their followers a weekly digest of their own posts. *Concern:* feature creep into Substack territory.
- 🔬 **Bookmarking with collections**: private + shareable. *Concern:* probably v1.1, not blocking launch.
- 🔬 **Code playground embedding** (CodeSandbox/StackBlitz). *Concern:* third-party dependencies, perf cost.

Each will get a discovery doc + 5 user conversations before any code is written.

---

## Not Doing (explicit cuts)

The most important section. These have been considered and rejected — re-litigate only with new evidence.

| Feature | Rejected because |
|---|---|
| Native mobile apps | Maintenance cost without revenue. Responsive web is sufficient. Revisit at 10k MAU. |
| Video posts | Different platform entirely. Stay focused. |
| Live streaming | Same as above. |
| Real-time chat | Async by design. People who want chat have Discord. |
| Stories / ephemeral content | Antithetical to "writing that lasts." |
| Engagement-based feed ranking | Violates PRODUCT.md principle #3. |
| Push notifications | Calm-first. Email digest is the channel. |
| Gamification (streaks, badges, levels) | Engagement-bait. Hard no. |
| Course creation / paid content | Not v1. Different business. |
| AI-generated cover images by default | Visual noise. Authors can opt-in to upload their own. |
| Comment voting / Reddit-style threading | We picked nested comments with reactions, not karma. |
| User-to-user DMs | Out of scope. Email exists. |
| Translation features | Defer until we have multi-language demand. |
| Job board | Tempting revenue, wrong audience. No. |
| Analytics for non-Pro users | Pro-tier differentiator. Free tier intentionally limited. |

---

## Recently Shipped (last 90 days)

- **2026-04** — ✅ Project foundation, auth with 2FA, social login
- **2026-03** — ✅ Initial PRODUCT.md, competitive analysis, 12 user interviews
- **2026-03** — ✅ Domain registered, repo created, brand decisions

---

## How This Document Is Maintained

- Updates land via PR with rationale in the description
- Quarterly review: revisit "Researching" items, promote or kill
- "Not Doing" items only reopen if a customer interview surfaces a real, repeated need
- Targets are guidance, not promises — slipping is fine, hiding the slip is not

---

## How to Influence This Roadmap

If you're using DevHub and want something added:

1. Check this document — it might already be planned, researching, or explicitly cut
2. Open a discussion on GitHub or use the in-app feedback widget
3. Frame the request as a problem, not a solution: "I can't do X" is more useful than "build Y"
4. Vote on existing requests in the public roadmap (coming v1.0)

We read everything. We can't build everything. The principles in PRODUCT.md decide.
