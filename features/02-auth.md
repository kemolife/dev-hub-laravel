# Prompt 02 — Authentication

Build a complete auth system showcasing Laravel's auth ecosystem. Don't just scaffold Breeze — extend it deliberately to demonstrate the patterns.

## Concepts demonstrated

- Fortify for backend auth, Livewire for UI
- Socialite (GitHub + Google)
- Two-factor authentication
- Email verification with signed URLs
- Custom middleware for rate limiting
- Policies and Gates (set up the structure even if we add policies for posts later)
- Session management UI (active sessions, revoke)

## Tasks

1. **Install + configure**
   - Install Fortify, configure features: registration, reset passwords, email verification, 2FA
   - Install Socialite with GitHub and Google providers (env vars, no hardcoded secrets)
   - Use Livewire components for login, register, 2FA challenge, password reset

2. **User model enhancements**
   - Add `username` (unique), `bio`, `avatar_path`, `github_handle`, `twitter_handle`, `website_url`, `last_seen_at`, `timezone`
   - Add `HasUuids` trait for public-facing IDs (or keep auto-increment but add a `public_id` UUID column — make a deliberate choice and ADR it)
   - Cast `last_seen_at` properly, add a `last_seen_at` middleware that updates throttled (max once per minute via cache)

3. **Social login flow**
   - `/auth/{provider}/redirect` and `/auth/{provider}/callback`
   - On callback: find or create user by email, link the OAuth account in a `social_accounts` table (so a user can link multiple providers)
   - Handle the edge case: user signs up with email, then tries to "Sign in with GitHub" using the same email — link, don't create duplicate

4. **Two-factor authentication**
   - Use Fortify's 2FA features
   - Show recovery codes once on enable, force re-download flow if user wants new ones
   - Add a "trusted devices" concept: after 2FA success, optionally remember device for 30 days via signed cookie

5. **Session management**
   - `/settings/sessions` page listing active sessions (browser, IP, last active, current?)
   - "Log out other sessions" button (Laravel has `Auth::logoutOtherDevices`)
   - Audit log entry when password changes, 2FA enabled/disabled, new login from new IP

6. **Rate limiting**
   - Custom rate limiter for login attempts (5 per minute per email + IP)
   - Custom rate limiter for password reset requests (3 per hour per email)
   - Use `RateLimiter::for()` in `AppServiceProvider` to define named limiters

7. **Authorization scaffolding**
   - Create a `Role` enum (Admin, Moderator, Member) — use a real PHP enum, cast on User model
   - Create a Gate `is-admin` and `is-moderator`
   - Add `app/Policies/` with a base `BasePolicy` class that handles admin bypass via `before()`

## Tests to write

- Feature: register flow including email verification (use `Notification::fake()`)
- Feature: login with 2FA enabled requires the second factor
- Feature: social login creates user, second social login with same email links account
- Feature: rate limiter blocks after threshold
- Unit: Role enum behavior
- Browser (optional): Dusk test for full login + 2FA flow

## Definition of Done

- ADR explaining: why Fortify+Livewire over Breeze, UUID strategy chosen, 2FA approach
- `docs/CHANGELOG.md` updated
- All `composer check` passes
- Manual smoke test: register → verify email → enable 2FA → log out → log in → see sessions page
