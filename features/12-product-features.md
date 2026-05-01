# Prompt 12 — Product Features (Onboarding, Feedback, Changelog)

Pure product engineering. These features don't add new business logic — they add polish that turns visitors into users into advocates.

## Concepts demonstrated

- Feature flags (Laravel Pennant)
- In-app onboarding state machine
- Feedback widget with metadata capture
- Public roadmap / changelog
- A/B testing infrastructure

## Tasks

1. **Onboarding checklist**
   - `onboarding_steps` table OR JSON column on user: { profile_completed, first_post_published, first_comment_left, followed_someone, set_notification_prefs }
   - Dismissible widget on dashboard showing checklist with progress bar
   - Each step links to relevant action
   - Mark complete via observers/events automatically (don't make user click "I did this")
   - Award something on completion (badge, "founder" status if early)

2. **Empty states**
   - Audit every list view: posts, comments, notifications, followers
   - Each empty state explains what goes there + CTA + maybe a sample/demo

3. **Feedback widget**
   - Floating button bottom-right on every page
   - Click opens modal: type (bug/feature/other), description, optional email
   - Auto-captures: current URL, user_id (if logged in), browser, viewport, Laravel version, git SHA
   - Stores in `feedback` table, optionally posts to Slack
   - Filament resource for browsing feedback

4. **Feature requests with voting**
   - `/feedback` public page (Pennant-gated to start)
   - Users post feature requests, others upvote
   - Status badges: Under Review, Planned, In Progress, Shipped, Declined
   - Admin can change status, link to GitHub issue, post update comment
   - When status changes to Shipped, auto-notify upvoters

5. **Changelog**
   - `/changelog` public page, generated from `changelog` posts (a special post type or separate model)
   - Email subscribers when new entry posted
   - "What's new" badge in nav until user views latest

6. **Feature flags (Pennant)**
   - Install Laravel Pennant
   - Define flags: `new-editor`, `ai-summaries`, `recommendations`
   - UI toggle in admin to enable per-user / per-cohort / globally
   - Use `Feature::active('new-editor')` in code

7. **A/B testing**
   - Build on Pennant: assign users to variants on first visit, store assignment
   - Track conversion events with variant tag (e.g., `signup_completed:new-editor:A`)
   - Simple stats page showing variant performance

## Product thinking

- Onboarding doesn't nag — dismissible, never blocking
- Feedback widget makes user feel heard: instant "Thanks, here's your ticket #123" with link to track
- Roadmap builds trust by being honest about what's NOT planned
- Changelog is marketing — celebrate shipped work

## Tests

- Onboarding step auto-completes on relevant action
- Feature flag respected in code paths
- Feedback submission creates record with correct metadata
- A/B variant assignment is sticky

## Definition of Done

- ADR: "Onboarding storage: JSON column vs separate table"
- ADR: "Feature flag rollout strategy"
- All onboarding steps have a path to complete
- `composer check` clean
