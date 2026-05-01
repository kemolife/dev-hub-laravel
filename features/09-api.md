# Prompt 09 — Public API

Expose a versioned public API. This is portfolio gold for backend-leaning roles.

## Concepts demonstrated

- API versioning strategy
- Sanctum personal access tokens with abilities (scopes)
- API Resources and Resource Collections
- Per-token rate limiting
- OpenAPI/Swagger documentation auto-generation
- Webhook system
- Idempotency keys

## Tasks

1. **Routing structure**
   - `routes/api/v1.php` registered with prefix `/api/v1`
   - Plan for v2 from the start (ADR the strategy: URL versioning vs header)

2. **Token management**
   - `/settings/api-tokens` Livewire page
   - Create token: name, abilities (read, write, admin), expires_at (optional)
   - Show token once on creation, store hashed
   - List, revoke

3. **Endpoints**
   - `GET /api/v1/posts` — paginated list, filterable by tag, author
   - `GET /api/v1/posts/{slug}`
   - `POST /api/v1/posts` (write ability)
   - `PATCH /api/v1/posts/{slug}` (write + ownership)
   - `DELETE /api/v1/posts/{slug}` (write + ownership)
   - `GET /api/v1/me`
   - All return `PostResource` / `UserResource`

4. **Rate limiting**
   - Anonymous: 60/hour
   - Authenticated: 1000/hour
   - Per-token override possible for partner integrations
   - Return `X-RateLimit-*` headers

5. **Idempotency**
   - For `POST /posts`, accept `Idempotency-Key` header
   - Cache request hash + response for 24h, return same response on duplicate

6. **Error responses**
   - Consistent JSON error envelope: `{ message, errors?, code }`
   - Custom `ApiException` base, `Handler` renders it consistently
   - 422 for validation, 404 for not found, 403 for unauthorized, etc.

7. **Documentation**
   - Install `dedoc/scramble` (or `knuckleswtf/scribe`)
   - Annotate resources/requests for accurate spec
   - Publish at `/api/docs`

8. **Webhooks**
   - `webhook_endpoints` table: user_id, url, secret, events (json array of event names), enabled, last_success_at, last_failure_at, failure_count
   - Listener on `PostPublished`, `CommentPosted`, etc., dispatches `DeliverWebhookJob`
   - Job signs payload with HMAC-SHA256, retries with exponential backoff, disables endpoint after 10 consecutive failures

## Product thinking

- API key dashboard shows usage stats per token (requests in last 7 days)
- Webhook test button (sends a `webhook.test` event)
- Webhook delivery log (last 50 attempts per endpoint)
- Public docs at `/developers` showcasing the API as a marketing tool

## Tests

- All endpoints with auth + abilities
- Rate limit triggers after threshold
- Idempotency returns cached response
- Webhook signing produces valid signatures
- Failed webhook retries with backoff

## Definition of Done

- ADR: "API versioning strategy"
- ADR: "Webhook signing format"
- Scramble docs generate cleanly
- `composer check` clean
