# Prompt 10 — Admin & Moderation

Build the internal tools you'd actually need to run this product.

## Concepts demonstrated

- Filament 3 admin panel
- Audit logging (event sourcing-lite)
- User impersonation with safety
- Manual interventions (refund, suspend, etc.)
- Moderation queue with batched actions

## Tasks

1. **Install Filament 3**
   - Configure at `/admin`
   - Restrict to Admin/Moderator roles via Filament's `canAccessPanel`

2. **Resources**
   - UserResource: search, filter by role/status, view profile, suspend, impersonate
   - PostResource: filter by status, bulk archive/restore, view as user
   - CommentResource: filter by reported flag, soft/hard delete
   - TagResource: merge tags (combine "reactjs" + "react.js")

3. **Audit log**
   - `audit_logs` table: id, user_id (actor), action, auditable_type, auditable_id, before (json), after (json), context (json: IP, user_agent), created_at
   - `LogsActivity` trait OR use `spatie/laravel-activitylog` (ADR which and why)
   - Log on: role change, password reset (admin-initiated), post moderation, user suspension, impersonation start/end, billing changes

4. **Impersonation**
   - Action on UserResource: "Impersonate"
   - Stores admin's original ID in session
   - Banner shown across app: "You are impersonating {user}. [Stop impersonating]"
   - Audit log entry on start/stop
   - Block sensitive actions while impersonating (changing password, deleting account, billing)

5. **Reports queue**
   - `reports` table: reporter_user_id, reportable_type, reportable_id, reason (enum), description, status (open/resolved/rejected), resolved_by_user_id, resolved_at
   - "Report" button on every Post and Comment
   - Filament page: list open reports, take action (delete content, warn user, dismiss)
   - Bulk actions: dismiss similar reports

6. **User suspension**
   - `suspended_at`, `suspended_until`, `suspension_reason` on users table
   - Middleware blocks suspended users from posting/commenting (read-only mode)
   - Banner shown to suspended user explaining and showing duration

## Product thinking

- Internal "user 360" page: full account state, recent posts/comments, reports against, billing, audit trail — one click during support
- Saved filters in Filament for common moderation queries
- Slack notification to mods channel when a report comes in

## Tests

- Non-admin can't access /admin
- Impersonation creates correct audit entries
- Suspended user can read but not post
- Report submission rate-limited
- Audit log captures before/after correctly

## Definition of Done

- ADR: "Audit logging: spatie/laravel-activitylog vs custom"
- ADR: "Impersonation safety guardrails"
- `composer check` clean
