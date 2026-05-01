# Prompt 13 — Growth (Referrals, SEO, Digest, Follow)

Features that drive new users in and bring existing users back.

## Concepts demonstrated

- Referral attribution
- SEO infrastructure (sitemaps, structured data)
- Lifecycle email (digest, win-back)
- Follow system (graph data modeling)
- Recommendation engine basics

## Tasks

1. **Follow system**
   - `follows` table: follower_id, followee_id, created_at — composite PK
   - Methods on User: `following()`, `followers()`, `isFollowing(user)`
   - Counts denormalized on user (followers_count, following_count) updated via observer
   - `Follow` button as Livewire component, optimistic
   - Notification on new follower (respects preferences from prompt 07)

2. **Personal feed**
   - `/feed` route (auth required) showing posts from followed users + tags
   - Mix algorithm: recency-weighted, with "for you" suggestions sprinkled in
   - Empty state: "Follow some people to see posts here. Here are some popular authors:"

3. **Recommendations (basic)**
   - "Recommended posts" sidebar on post show: same tags, recent, not by same author
   - "Suggested people to follow" on profile/feed empty state
   - Pure SQL, no ML: cosine similarity on tag overlap is enough to start

4. **Referrals**
   - Each user gets `referral_code` (short, URL-safe)
   - `?ref=CODE` on any page sets cookie for 30 days
   - On signup, link to referrer in `referred_by_user_id`
   - Referrer dashboard: shows referrals (count, who, signup date, converted to paid?)
   - Reward: 1 month Pro free for both when referred user upgrades to Pro

5. **SEO**
   - `/sitemap.xml` generated dynamically (or daily job to static file for big sites)
   - JSON-LD structured data on posts (Article schema)
   - OpenGraph + Twitter Card meta tags
   - OG image generation: queued job that uses Browsershot or simple PHP image generation, caches to S3
   - Canonical URLs, robots.txt
   - Public profiles indexable, private settings noindex

6. **Lifecycle emails**
   - **Welcome series**: day 0 (welcome), day 1 (here's how to write your first post), day 7 (here's what others have shared this week), day 30 (your stats so far) — each as queued mailables
   - **Re-engagement**: user inactive 14 days → "Here's what you missed"
   - **Win-back**: cancelled subscriber → 30 days later, discount code
   - All respect notification preferences

7. **Weekly digest** (already partially in prompt 07)
   - Personalized: top posts from people you follow + tags you like
   - Sent at user's preferred time in their timezone
   - One-click unsubscribe (signed URL)

## Product thinking

- Referral copy emphasizes mutual benefit, not just "give us users"
- SEO isn't dirty — it brings the right readers to good content
- Digest opens are your primary engagement metric for this feature; track open rate, CTR
- Empty feed is opportunity, not failure — surface great content to convert lurker → follower

## Tests

- Follow toggles correctly, counters update
- Referral cookie persists, attributes correctly on signup
- Sitemap includes only public, published content
- Digest job runs only for opted-in users at their local time

## Definition of Done

- ADR: "Recommendation approach: rules-based vs collaborative filtering vs ML — what we picked and why"
- ADR: "Referral reward structure"
- Lighthouse SEO score >95 on a sample post page
- `composer check` clean
