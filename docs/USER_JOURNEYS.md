# DevHub — User Journeys

> Role-first reference for understanding and manually testing all planned user flows.
>
> **Scope:** Full planned product (v0.1 through v1.0).
> **Audience:** Developer testing flows, product review, sharing with collaborators.
>
> Each journey follows: **Preconditions → Steps → Expected outcome → Verify**.

---

## Roles

| Role | Who | Access |
|---|---|---|
| **Visitor** | Unauthenticated user | Public pages only |
| **Reader** | Registered user, Free tier | Read, react, comment, follow |
| **Writer** | Registered user with published posts | Everything Reader + post management |
| **Pro** | Paid subscriber | Everything Writer + Pro features |
| **Admin** | Staff / operator | Filament admin panel + all user data |

---

## Reader Journeys

### R1 — Discovery & Registration

#### R1.1 — Discover via search / shared link
**Preconditions:** User is a visitor (not logged in), arrives from Google or a shared URL.

**Steps:**
1. Land on a post detail page or the home feed.
2. Read post content — fully accessible without sign-in.
3. Scroll to comments — visible, but posting requires login.
4. See a soft prompt: "Join DevHub to react and comment."

**Expected:** Content is readable without account. No hard gate. Reaction/comment CTAs shown but disabled.

**Verify:**
- Post body, tags, author name visible without auth.
- React button shows tooltip "Sign in to react" on hover.
- Comment form replaced with "Sign in to join the conversation."

---

#### R1.2 — Sign up with email and password
**Preconditions:** Visitor on `/register`.

**Steps:**
1. Enter name, email, password (min 8 chars), confirm password.
2. Submit form.
3. Receive verification email at the entered address.
4. Click verification link in email.
5. Redirected to onboarding or home feed.

**Expected:** Account created, email verified, user logged in.

**Verify:**
- Unverified user cannot publish posts.
- Duplicate email shows validation error, not a 500.
- Password confirmation mismatch shows inline error.
- Verification link expires after 60 minutes.

---

#### R1.3 — Sign up via GitHub OAuth
**Preconditions:** Visitor on `/register` or `/login`.

**Steps:**
1. Click "Continue with GitHub."
2. Authorize DevHub on GitHub.
3. Redirected back — account created from GitHub profile (name, avatar, email).
4. If GitHub email is private, prompted to enter an email manually.

**Expected:** Account created and verified in one step (GitHub email counts as verified).

**Verify:**
- GitHub username stored in profile.
- No duplicate created if email already exists — existing account linked.
- No password set on OAuth-created accounts.

---

#### R1.4 — Sign up via Google OAuth
**Preconditions:** Visitor on `/register` or `/login`.

**Steps:**
1. Click "Continue with Google."
2. Select Google account.
3. Redirected back — account created from Google profile.

**Expected:** Same as R1.3 but via Google.

**Verify:** Same as R1.3.

---

#### R1.5 — Log in with 2FA enabled
**Preconditions:** Existing user with TOTP 2FA enabled.

**Steps:**
1. Enter email + password on `/login`.
2. Credential check passes — 2FA challenge screen shown.
3. Enter 6-digit TOTP code from authenticator app.
4. Logged in and redirected to home feed.

**Expected:** Login blocked until correct TOTP entered.

**Verify:**
- Wrong TOTP shows error, does not log in.
- Backup recovery code accepted in place of TOTP.
- Used recovery code is invalidated after use.

---

#### R1.6 — Recover account with lost 2FA device
**Preconditions:** User with 2FA enabled, authenticator app lost.

**Steps:**
1. Click "Use recovery code" on 2FA challenge screen.
2. Enter one of the 10 backup codes shown during 2FA setup.
3. Logged in.
4. Prompted to reconfigure 2FA or generate new backup codes.

**Expected:** Account accessible via recovery code. Code invalidated after use.

**Verify:**
- Same recovery code cannot be used twice.
- All recovery codes can be regenerated (invalidates old ones) from security settings.

---

### R2 — Reading

#### R2.1 — Browse the home feed
**Preconditions:** User is logged in (Reader or above).

**Steps:**
1. Navigate to `/` (home).
2. See feed of recent posts — ordered by depth signals (length, code blocks, read-through rate), not raw reactions.
3. Each card shows: title, author, tags, estimated read time, excerpt.
4. No reaction counts visible in the feed.
5. Scroll to paginate or infinite-scroll.

**Expected:** Feed shows quality-ranked posts. No engagement metrics visible in the list.

**Verify:**
- Reaction counts NOT visible on feed cards.
- Bookmark counts NOT visible.
- Tags link to tag pages.
- Read time estimate visible (e.g., "8 min read").

---

#### R2.2 — Read a post
**Preconditions:** User navigates to a post (authenticated or visitor).

**Steps:**
1. Click a post card from any feed or search result.
2. Post loads: title, author avatar, publish date, read time, body (markdown rendered), tags.
3. Scroll through — code blocks syntax-highlighted.
4. Reach bottom — see reactions bar, comment section.
5. Reading progress tracked server-side (scroll depth + time ≥ 30s = "read-through").

**Expected:** Post renders completely. Reaction bar and comments visible at bottom.

**Verify:**
- Code blocks render with syntax highlighting.
- External links open in new tab.
- Internal links navigate in-app.
- Reaction counts visible ON the post (not in feed).
- Read-through event fires once per user per post (not on re-reads).

---

#### R2.3 — Search for content
**Preconditions:** Any user (visitor or authenticated).

**Steps:**
1. Click search icon or press `/` to focus search input.
2. Type query (e.g., "postgres indexing").
3. Results appear — title, author, tags, excerpt with matched term highlighted.
4. Click a result to navigate to the post.

**Expected:** Results returned via Meilisearch, relevant and fast (< 200ms).

**Verify:**
- Searching partial words returns relevant results (prefix match).
- Searching by tag name works.
- Empty query shows no results (not all posts).
- Unpublished/draft posts do NOT appear in search.

---

#### R2.4 — Explore by tag
**Preconditions:** Any user.

**Steps:**
1. Click a tag on any post card or post page.
2. Tag detail page loads: tag name, description (if set), post list.
3. Posts sorted by depth signal (not chronological).

**Expected:** Tag page shows all published posts with that tag.

**Verify:**
- Tags are normalized (e.g., "Rust", "rust", "RUST" all go to same page).
- Tag with no posts shows empty state, not 404.
- Tag detail links back to home feed.

---

#### R2.5 — View personal feed (follows)
**Preconditions:** Reader has followed at least one writer.

**Steps:**
1. Navigate to "Following" tab on home feed.
2. See posts only from followed writers, ordered by publish date (newest first).
3. Empty state if no one followed yet.

**Expected:** Personal feed is a secondary view — content-first feed is default.

**Verify:**
- Default tab on `/` is the main feed, not the following feed.
- Following feed shows ONLY published posts from followed authors.
- Unfollowing a writer removes their posts from the following feed.

---

### R3 — Engagement

#### R3.1 — React to a post
**Preconditions:** Reader is logged in, viewing a post.

**Steps:**
1. Scroll to reaction bar at bottom of post.
2. See five reaction types: Insightful, Fire, Mind-blown, Heart, Like.
3. Click one reaction to toggle it on.
4. Click again to toggle it off.
5. React count on the post increments/decrements.

**Expected:** One reaction type per user per post. Toggle behavior.

**Verify:**
- Selecting a second reaction type replaces the first (not multiple reactions per user).
- Count shown on post updates in near-real-time (optimistic UI or Reverb).
- Reaction counts NOT shown to visitors in the feed (only on post itself).
- Admin can see reaction breakdown in the admin panel.

---

#### R3.2 — Comment on a post
**Preconditions:** Reader is logged in, viewing a post. Has been on the page ≥ 5 minutes OR has scroll depth ≥ 50%.

**Steps:**
1. Scroll to comment section.
2. If time/depth threshold not met, see: "Read more before commenting — take your time."
3. Once threshold met, comment form becomes active.
4. Type comment (markdown supported).
5. Preview rendered comment before submitting.
6. Submit — comment appears in thread.

**Expected:** Comment posted. Thoughtfulness gate enforced before form activates.

**Verify:**
- Comment form blocked before threshold (5 min or 50% scroll).
- Comment markdown renders (bold, code, links).
- Author of the post receives notification of new comment.
- Comment appears immediately for commenter (optimistic) and for others via Reverb or reload.

---

#### R3.3 — Reply to a comment
**Preconditions:** Reader is logged in, a comment exists.

**Steps:**
1. Click "Reply" under an existing comment.
2. Inline reply form appears, indented under parent comment.
3. Type reply, submit.
4. Reply appears nested under parent.

**Expected:** Nested threading rendered. Max nesting depth enforced (e.g., 3 levels).

**Verify:**
- Reply is indented visually under parent.
- Deep nesting collapses after N levels (no infinite indent).
- Parent commenter notified of reply.

---

#### R3.4 — Edit a comment (15-minute window)
**Preconditions:** Reader has posted a comment within the last 15 minutes.

**Steps:**
1. Click "Edit" on own comment.
2. Inline edit form opens with current text.
3. Make changes, save.
4. Comment shows "(edited)" label.

**Expected:** Edit succeeds within window. Edit button disappears after 15 minutes.

**Verify:**
- "Edit" button visible only within 15 minutes of posting.
- Edited comment shows "(edited)" label.
- Original text NOT preserved publicly (edit replaces text).
- Edit button absent after window closes.

---

#### R3.5 — Follow a writer
**Preconditions:** Reader is logged in, viewing a writer's profile or post.

**Steps:**
1. Click "Follow" on writer's profile or on author byline.
2. Follow count increments on writer's profile.
3. Writer's posts now appear in Reader's "Following" feed.

**Expected:** Follow persisted. Following feed updates.

**Verify:**
- "Follow" button becomes "Unfollow" after clicking.
- Following own profile is not possible.
- Writer receives notification when followed.
- Unfollowing removes them from the following feed immediately.

---

### R4 — Notifications

#### R4.1 — Receive daily digest email
**Preconditions:** Reader has notifications enabled (default), digest frequency set to daily (default).

**Steps:**
1. Activity occurs: someone follows Reader, comments on their post, replies to their comment.
2. At 08:00 UTC (or configured time), digest email is sent.
3. Email shows grouped activity: new followers, reactions, comments.

**Expected:** Single daily email, not one per event. Calm default.

**Verify:**
- Multiple events in one day → one email, not multiple.
- Email contains unsubscribe/preferences link.
- No email sent on days with zero activity.
- Reader can change frequency (daily, weekly, never) in preferences.

---

#### R4.2 — Configure notification preferences
**Preconditions:** Reader is logged in, in `/settings/notifications`.

**Steps:**
1. Navigate to Settings → Notifications.
2. See notification types: new follower, reaction on post, comment on post, reply to comment, weekly digest.
3. Toggle each type on/off independently.
4. Set digest frequency: real-time (email per event), daily digest, weekly digest, never.
5. Save preferences.

**Expected:** Preferences persisted. Notifications follow configured rules immediately.

**Verify:**
- Disabling "comment on post" → no notification when someone comments.
- Setting "never" → no emails at all.
- Preferences save without page reload (AJAX or Livewire).

---

#### R4.3 — View in-app notification feed
**Preconditions:** Reader is logged in, has unread notifications.

**Steps:**
1. See bell icon in topbar with unread count badge.
2. Click bell — notification panel opens.
3. Notifications listed: each shows type, actor, and relative time.
4. Click a notification — navigate to the relevant post/comment.
5. Viewed notifications marked as read; badge clears.

**Expected:** Unread count accurate. Clicking navigates correctly. Mark-as-read works.

**Verify:**
- Unread count decrements as notifications are read.
- "Mark all as read" clears all at once.
- Old notifications paginate (not infinite list in DOM).
- Notification links to the specific comment, not just the post top.

---

### R5 — Account Management

#### R5.1 — Update profile
**Preconditions:** Reader is logged in.

**Steps:**
1. Navigate to Settings → Profile.
2. Update: display name, bio, website URL, avatar upload, social links.
3. Save.

**Expected:** Profile page reflects changes immediately.

**Verify:**
- Name change reflected in post bylines.
- Avatar upload resized and served from storage (not original).
- Invalid URL in website field shows validation error.
- Bio has character limit enforced (e.g., 300 chars).

---

#### R5.2 — Change password
**Preconditions:** Reader with a password-based account (not OAuth-only).

**Steps:**
1. Settings → Security → Change Password.
2. Enter current password, new password, confirm new password.
3. Save.

**Expected:** Password updated. All other sessions invalidated.

**Verify:**
- Old password required (prevents session-hijack escalation).
- All other active sessions logged out after password change.
- OAuth-only accounts see this section disabled with explanation.

---

#### R5.3 — Enable 2FA
**Preconditions:** Reader is logged in, 2FA not yet set up.

**Steps:**
1. Settings → Security → Two-Factor Authentication → Enable.
2. QR code and manual entry key shown.
3. Scan QR with authenticator app.
4. Enter 6-digit code to confirm setup.
5. Download / copy 10 backup codes. Confirm download.
6. 2FA now active.

**Expected:** 2FA enabled. Backup codes shown once.

**Verify:**
- 2FA not active until confirmed with a valid TOTP code.
- Backup codes shown only once — no way to retrieve them (only regenerate).
- All future logins require TOTP.

---

#### R5.4 — Export account data
**Preconditions:** Reader is logged in.

**Steps:**
1. Settings → Privacy → Export My Data.
2. Click "Request Export."
3. System queues export job.
4. Email received with download link (within ~5 minutes for small accounts).
5. Download ZIP: posts (markdown), comments, profile, reactions, follows.

**Expected:** Full data export delivered by email. No lock-in.

**Verify:**
- Export includes all posts (published + drafts).
- Export is a queued job, not a synchronous response.
- Download link is time-limited (e.g., 24 hours).
- Re-requesting export replaces the previous one (no duplicates).

---

#### R5.5 — Delete account
**Preconditions:** Reader is logged in.

**Steps:**
1. Settings → Privacy → Delete Account.
2. Confirm by typing email address.
3. Click "Delete."
4. Soft-deleted: account and posts anonymized, not hard-deleted immediately.
5. 30-day grace period — user can log in and cancel deletion.

**Expected:** Soft delete on request. Grace period to reverse.

**Verify:**
- Posts anonymized (author shown as "[deleted]"), not removed, within grace period.
- After 30 days: hard delete of account data, posts permanently anonymized.
- Cancelling deletion within 30 days fully restores account.
- Subscription (if Pro) cancelled immediately on deletion request.

---

## Writer Journeys

### W1 — Writing & Publishing

#### W1.1 — Create a draft
**Preconditions:** Reader is logged in (Free: up to 5 posts/month; Pro: unlimited).

**Steps:**
1. Click "Write" in the nav.
2. New draft created. Redirected to editor at `/posts/new` or `/drafts/{id}/edit`.
3. Editor shows: title field, markdown body editor, tag input, cover image upload (optional), publish controls.
4. Type title and body.
5. Draft auto-saves every 30 seconds.

**Expected:** Draft persisted. Auto-save works. No publish yet.

**Verify:**
- Draft NOT visible in public feeds or search.
- Auto-save indicator shows "Saved" or timestamp.
- Closing the tab and returning → draft content preserved.
- Free tier limit: attempting to create 6th post in calendar month shows upgrade prompt.

---

#### W1.2 — Add tags to a post
**Preconditions:** Writer is in the post editor.

**Steps:**
1. Click tag input field.
2. Type tag name — autocomplete suggests existing tags.
3. Select existing tag or press Enter to create new one.
4. Add up to 5 tags.
5. Tags shown as chips in the input.

**Expected:** Tags attached to draft. Existing tags suggested. New tags created with normalization.

**Verify:**
- Tag input normalizes to lowercase slug (e.g., "TypeScript" → "typescript").
- Maximum 5 tags enforced.
- Existing tags are not duplicated in the DB — resolved by slug.
- Tags appear on the published post.

---

#### W1.3 — Preview a post
**Preconditions:** Writer is in the post editor with some content.

**Steps:**
1. Click "Preview" toggle or button.
2. Editor switches to preview mode — rendered markdown, same styling as published post.
3. Code blocks syntax-highlighted. Headings linkable.
4. Click "Edit" to return to editor.

**Expected:** Preview matches final rendered output exactly.

**Verify:**
- Preview uses same markdown renderer as public post page.
- Switching between Edit/Preview does not lose content.

---

#### W1.4 — Publish a post
**Preconditions:** Writer has a draft with title and body content, email verified.

**Steps:**
1. Click "Publish" button.
2. Confirmation modal: publish date (now or scheduled — future feature).
3. Confirm — post set to `published`, `published_at` set to now.
4. Redirected to the live post URL.
5. Post appears in feeds and search index.

**Expected:** Post immediately visible in feeds and search. Meilisearch index updated.

**Verify:**
- Post visible on home feed within seconds of publishing.
- Post appears in Meilisearch results.
- Post URL uses slug derived from title (unique, collision handled).
- Published post can NOT revert to draft (only unpublish, which hides it but keeps status as `published`).

---

#### W1.5 — Edit a published post
**Preconditions:** Writer is viewing their own published post.

**Steps:**
1. Click "Edit" on their own post (visible only to the author).
2. Editor opens with current content.
3. Make changes, save.
4. Post shows "(updated)" label with last-edited timestamp.

**Expected:** Edit saved. Search index updated. "(updated)" label appears.

**Verify:**
- "(updated)" label visible on post after edit.
- Meilisearch index updated (not stale).
- Other users do NOT see edit history (no public revision log).
- Admin can see edit history in audit logs.

---

#### W1.6 — Unpublish a post
**Preconditions:** Writer has a published post.

**Steps:**
1. Go to post settings (kebab menu on own post).
2. Click "Unpublish."
3. Confirm action.
4. Post hidden from feeds, search, and public URL (returns 404 for visitors).
5. Post remains accessible in writer's drafts dashboard.

**Expected:** Post hidden but not deleted. Re-publishable.

**Verify:**
- Unpublished post returns 404 for non-authors.
- Author still sees it in their dashboard with "Unpublished" status.
- Re-publishing makes it live again (same URL).
- Meilisearch index entry removed.

---

#### W1.7 — Delete a post
**Preconditions:** Writer owns the post.

**Steps:**
1. Post settings → "Delete Post."
2. Confirm by typing the post title.
3. Post soft-deleted — removed from feeds, search, public URL.
4. All reactions and comments on the post soft-deleted.

**Expected:** Post and its engagement data soft-deleted.

**Verify:**
- URL returns 404 immediately.
- Reactions and comments soft-deleted (not hard-deleted).
- Admin can still see deleted posts in Filament.

---

### W2 — Pro Features

#### W2.1 — View post analytics (Pro only)
**Preconditions:** Writer has Pro subscription, has published posts.

**Steps:**
1. Navigate to a published post → "Analytics" tab.
2. See: total views, unique readers, read-through rate (%), geography (country-level only), reactions breakdown.
3. Privacy-respecting: no individual reader tracking.

**Expected:** Aggregated, anonymized analytics. No per-reader data.

**Verify:**
- Geography shows country-level only (no city, no IP).
- Read-through rate = (users who reached ≥80% scroll + ≥30s) / total views.
- Free user sees "Upgrade to Pro" instead of analytics.

---

#### W2.2 — Use the public API
**Preconditions:** Writer has Pro subscription.

**Steps:**
1. Settings → API → "Generate Token."
2. Token created with selected abilities (read:posts, write:posts, etc.).
3. Token shown once — copy it.
4. Use `Authorization: Bearer <token>` header on API calls.
5. API rate limit: 1000 req/h per token.

**Expected:** Token usable immediately. Rate limit enforced per token.

**Verify:**
- Token shown only once — no way to retrieve plaintext again.
- Token revocation works immediately.
- Rate limit returns 429 with `Retry-After` header.
- Free user cannot generate tokens.

---

#### W2.3 — Set up a webhook
**Preconditions:** Writer has Pro subscription.

**Steps:**
1. Settings → Webhooks → "Add Endpoint."
2. Enter URL, select events (post.published, comment.created, reaction.created).
3. Save — secret key generated.
4. Trigger event (publish a post) — DevHub sends signed POST to the URL.
5. Verify signature using `X-DevHub-Signature` header.

**Expected:** Webhook delivered. Payload signed. Retry on failure.

**Verify:**
- Failed deliveries retried with exponential backoff (3 attempts, max 24h).
- Signature verifiable using shared secret.
- Webhook log in settings shows delivery history and status codes.

---

### W3 — Billing (Pro Subscription)

#### W3.1 — Start free trial
**Preconditions:** Reader on Free tier, no prior Pro subscription.

**Steps:**
1. Click "Upgrade to Pro" anywhere in the UI.
2. Trial period: 14 days, no payment method required (or card required upfront — per business decision).
3. Trial starts — Pro features unlocked.
4. Reminder email at day 7, day 12.
5. At day 14: prompted to enter payment details or downgrade.

**Expected:** Trial flows into subscription or graceful downgrade.

**Verify:**
- Pro features locked again immediately on trial expiry if no payment added.
- Reminder emails sent at correct intervals.
- Trial cannot be started twice for the same account.

---

#### W3.2 — Subscribe to Pro
**Preconditions:** Reader on Free tier or trial.

**Steps:**
1. Upgrade to Pro → enter payment details (Stripe/Mollie card form).
2. Choose monthly (€9) or annual (€90).
3. Confirm — subscription created, first invoice generated.
4. Redirect to success page.
5. Subscription confirmation email sent.

**Expected:** Payment processed. Pro features unlocked immediately.

**Verify:**
- Invalid card shows error (not a 500).
- Subscription status updates immediately (not after webhook delay).
- Invoice sent by email.
- VAT applied correctly for EU users (with VAT number opt-out for B2B).

---

#### W3.3 — Cancel subscription
**Preconditions:** Writer has active Pro subscription.

**Steps:**
1. Settings → Billing → "Cancel Subscription."
2. See end date (end of current billing period, not immediate).
3. Confirm cancellation.
4. Pro access continues until end of billing period.
5. At period end: downgraded to Free. Posts over 5/month become unpublished (not deleted).

**Expected:** Subscription ends gracefully. Posts over limit not deleted — hidden.

**Verify:**
- Pro access continues until the paid period ends.
- Posts over free tier limit are unpublished, not deleted, on downgrade.
- Re-subscribing re-publishes the hidden posts.
- Cancellation confirmation email sent.

---

#### W3.4 — Handle failed payment (dunning)
**Preconditions:** Writer has active Pro subscription, payment method expires.

**Steps:**
1. Payment fails at renewal.
2. Day 0: payment failed email sent. Retry attempted.
3. Day 3: second retry + reminder email.
4. Day 7: final retry + "last chance" email with update payment link.
5. Day 14: subscription cancelled, account downgraded to Free.

**Expected:** Multiple retries before hard downgrade. Clear communication at each step.

**Verify:**
- Subscription stays active during the grace period.
- Updating payment method triggers immediate retry.
- After downgrade: posts over limit unpublished, not deleted.

---

## Admin Journeys

### A1 — Moderation

#### A1.1 — View reports queue
**Preconditions:** Admin is logged into Filament admin panel (`/admin`).

**Steps:**
1. Navigate to Moderation → Reports.
2. See list of open reports: reported content (post/comment), reporter, reason, date.
3. Sort by date or priority.
4. Click a report to view full context (reported content + surrounding thread).

**Expected:** Reports visible and actionable.

**Verify:**
- Only unresolved reports in default view.
- Filter by type (post, comment), status (open, resolved, dismissed).
- Content visible in context, not just a snippet.

---

#### A1.2 — Resolve a report
**Preconditions:** Admin is viewing a specific report.

**Steps:**
1. Review the reported content.
2. Choose action: Remove Content, Warn User, Suspend User, Dismiss Report.
3. Optionally add a note (internal, not shown to user).
4. Confirm action.
5. If content removed: author notified by email.

**Expected:** Report resolved. Action logged in audit trail.

**Verify:**
- Dismissed report → marked resolved, no action taken.
- Content removed → post/comment soft-deleted, author emailed.
- All actions appear in audit log with admin ID, timestamp, action taken.

---

#### A1.3 — Suspend a user
**Preconditions:** Admin in Filament, viewing a user record.

**Steps:**
1. User record → "Suspend Account."
2. Enter reason and duration (7 days / 30 days / permanent).
3. Confirm.
4. User cannot log in during suspension period. Existing sessions invalidated.
5. Suspension email sent to user.

**Expected:** User locked out immediately. Sessions invalidated.

**Verify:**
- Suspended user sees "Your account has been suspended" on login attempt.
- Suspension visible in user record with reason and expiry.
- Suspension automatically lifted at expiry date (scheduled job).
- Permanent suspension requires a second confirmation step.

---

#### A1.4 — Impersonate a user
**Preconditions:** Admin in Filament.

**Steps:**
1. User record → "Impersonate."
2. Warning modal: "You are about to impersonate [user]. All actions will be logged."
3. Confirm.
4. Admin is now logged in as the target user. Banner visible: "Impersonating [user] — Return to admin."
5. Perform investigation (e.g., reproduce a bug).
6. Click "Return to admin" — back to own admin session.

**Expected:** Impersonation logged. Banner always visible. All actions traceable to admin.

**Verify:**
- Impersonation start/end recorded in audit log with admin ID.
- Any action taken while impersonating (e.g., publishing a post) also logged.
- Impersonation cannot be nested (admin impersonating someone who is already impersonating).
- Banner cannot be dismissed.

---

#### A1.5 — Merge tags
**Preconditions:** Admin in Filament → Tags section.

**Steps:**
1. Select tag to merge (e.g., "reactjs").
2. Choose merge target (e.g., "react").
3. Confirm.
4. All posts tagged "reactjs" are re-tagged "react."
5. "reactjs" tag deleted.

**Expected:** Tags merged. Posts updated. Duplicate tag removed.

**Verify:**
- Posts correctly re-tagged.
- Old tag no longer appears in search or autocomplete.
- Merge action in audit log.

---

#### A1.6 — View audit logs
**Preconditions:** Admin in Filament → Audit Logs.

**Steps:**
1. Navigate to Audit Logs.
2. Filter by: admin user, action type, target (user/post/tag), date range.
3. Each log entry shows: who, what, when, on what, before/after state.

**Expected:** Full trace of all admin actions. Immutable — logs cannot be deleted by admins.

**Verify:**
- Logs are append-only (no delete button, even for super-admins).
- All impersonation actions present.
- All content removal actions present.
- Filters work (date range, actor, action type).

---

#### A1.7 — Manage billing operations
**Preconditions:** Admin in Filament → Billing.

**Steps:**
1. Search a user by email.
2. See subscription status, tier, billing history, invoices.
3. Manually apply a credit or extend grace period if needed.
4. All billing mutations logged.

**Expected:** Admin can resolve billing edge cases without touching payment provider directly.

**Verify:**
- Manual credit logged with reason.
- Billing log entries visible per user.
- Invoice PDFs downloadable from admin panel.

---

## Cross-Cutting Flows

### X1 — Report content (Reader/Writer)
**Preconditions:** Any logged-in user sees a post or comment they believe violates guidelines.

**Steps:**
1. Click the kebab menu (⋯) on the post or comment.
2. Click "Report."
3. Select reason: spam, harassment, misinformation, off-topic, other.
4. Optionally add a note.
5. Submit.

**Expected:** Report queued for admin review. User sees confirmation.

**Verify:**
- Reporter sees "Report received. We'll review within 48 hours."
- Report appears in admin Reports queue.
- Same user cannot report the same content twice.
- Author NOT notified that they've been reported (prevent retaliation).

---

### X2 — Real-time updates via Reverb
**Preconditions:** Reader is viewing a post with active discussion. Reverb is connected.

**Steps:**
1. Another user posts a comment on the same post.
2. New comment appears in Reader's browser without page reload.
3. Another user reacts — reaction count updates.
4. Reader's notification bell count updates when they receive a notification.

**Expected:** Real-time updates delivered via WebSocket (Reverb/Laravel Echo).

**Verify:**
- Comment appears without reload.
- Reaction count updates in near-real-time.
- WebSocket disconnects gracefully when browser tab goes to background.
- Fallback: page reload always shows current state (no stale cache shown instead of real-time).

---

### X3 — SEO & open graph
**Preconditions:** Visitor with a direct post URL (from Google, social share, etc.).

**Steps:**
1. Share a post URL in Slack / Twitter.
2. Unfurl shows: post title, author name, excerpt, OG image (generated or cover image).
3. Google crawls the URL — structured data (Article schema) present.

**Expected:** Posts unfurl correctly. Structured data valid.

**Verify:**
- OG `title`, `description`, `image` tags present in HTML `<head>`.
- Twitter card tags present.
- JSON-LD Article schema present.
- Sitemap at `/sitemap.xml` contains all public posts.

---

*Last updated: 2026-05-09*
*Maintained by: Vitalii (founder)*
*Status: Living document — update when flows change*
