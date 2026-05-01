# Prompt 07 — Notifications (Multi-Channel + Preferences)

Build notifications that respect user preferences. This is where product thinking really shows.

## Concepts demonstrated

- Laravel Notifications across multiple channels (database, mail, broadcast)
- User notification preferences per type per channel
- Digest mode (queued summary instead of individual emails)
- Timezone-aware delivery (don't email at 3am)
- Frequency capping
- Notification center UI

## Tasks

1. **Migrations**
   - `notification_preferences`: user_id, type (string), channel (enum: database, email, push), enabled (bool), digest (bool default false). Composite unique on (user_id, type, channel)
   - Use Laravel's default `notifications` table for database channel

2. **Notification types**
   - `NewCommentOnYourPost`
   - `ReplyToYourComment`
   - `MentionedInComment`
   - `NewFollower` (set up the structure even if follow comes later)
   - `WeeklyDigest`
   - Each is a class in `app/Notifications/`

3. **Smart channel routing**
   - Override `via($notifiable)` to read user preferences for this notification type
   - If digest is on for email channel, route to database only and let scheduler send digest later
   - Helper trait `RespectsPreferences` to DRY this up

4. **Settings UI**
   - `/settings/notifications` Livewire page
   - Matrix: rows = notification types, columns = channels (in-app, email, push), checkboxes
   - Per-type "digest" toggle for email
   - "Quiet hours" setting (start/end time in user's timezone) — mark notifications as `delay_until`

5. **Notification center**
   - Bell icon in nav with unread badge (Livewire, polls or uses Reverb later)
   - Dropdown shows recent 10
   - `/notifications` full page with mark all read, filter by type
   - Click on notification marks read + navigates to relevant URL

6. **Digest job**
   - `SendWeeklyDigestJob` scheduled weekly
   - Pulls each user's database notifications from past week, groups, renders Markdown email
   - Skips users who have no notifications or have opted out entirely

7. **Frequency capping**
   - `app/Support/NotificationThrottle.php` — checks "have we sent this user more than N notifications of type X in window Y?"
   - Used by mass notifications (e.g., "user X who you follow posted") to avoid spam

## Product thinking

- Sane defaults: in-app on for everything, email on only for direct interactions (mentions, replies)
- First-time notification gets a banner: "You can change these anytime in settings"
- "Pause all notifications for 24h" quick toggle

## Tests

- User with email disabled for `NewCommentOnYourPost` only gets database notification
- Digest mode: notifications stored, not emailed; digest job sends one email
- Quiet hours: notification queued with delay
- Mention triggers `MentionedInComment` with correct URL

## Definition of Done

- ADR: "Notification preference granularity: per-type vs global, why we picked per-type-per-channel"
- ADR: "Digest implementation: real-time aggregation vs scheduled batch"
- `composer check` clean
