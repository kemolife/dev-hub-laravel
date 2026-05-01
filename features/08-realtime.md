# Prompt 08 — Real-Time (Laravel Reverb)

Add WebSocket-powered live updates.

## Concepts demonstrated

- Laravel Reverb setup
- Public, private, and presence channels
- Broadcasting from events
- Echo on the frontend (Livewire integration)
- Channel authorization
- Graceful fallback when WS disconnected

## Tasks

1. **Install Reverb**
   - `php artisan install:broadcasting`
   - Configure in Sail (add reverb service)
   - Set up Echo in Livewire — use the `wire:stream` or Echo + Alpine integration

2. **Use cases (channels)**
   - `posts.{postId}` (public) — broadcast new comments live
   - `users.{userId}.notifications` (private) — broadcast new notifications, update bell badge
   - `posts.{postId}.viewers` (presence) — show "X people viewing"

3. **Broadcast existing events**
   - `CommentPosted` implements `ShouldBroadcast` → `broadcastOn()` returns Channel for the post
   - `NotificationCreated` (new event) → private channel
   - `broadcastWith()` returns minimal payload, frontend re-fetches if needed

4. **Channel authorization**
   - Define in `routes/channels.php`
   - Private notification channel: only the user themselves
   - Presence channel: anyone authenticated, returns `{id, username, avatar}`

5. **Frontend integration**
   - Update `CommentThread` Livewire component to listen for new comments and prepend optimistically
   - Update notification bell to listen and update count without polling
   - On post show page, show "X reading now" badge with avatars

6. **Resilience**
   - Show "Reconnecting…" indicator if WebSocket drops
   - On reconnect, re-fetch state to ensure consistency
   - Fallback: if Reverb unreachable, polling kicks in (every 30s)

## Product thinking

- Don't be creepy: presence channel shows count + maybe author avatars only, not all viewers
- Live comment notifications use subtle animation (slide in), not jarring
- "X new comments — click to load" pattern instead of auto-prepending if user has scrolled

## Tests

- Event implements ShouldBroadcast and includes correct channel
- Channel authorization rejects unauthorized users
- Use `Event::fake()` and assert dispatched

## Definition of Done

- ADR: "Reverb vs Pusher vs Soketi" — what we picked and why
- ADR: "Broadcast payload strategy: full vs minimal+refetch"
- Manual test: open two browsers, comment in one, see it in the other within 1s
- `composer check` clean
