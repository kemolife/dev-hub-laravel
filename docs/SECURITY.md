# Security

DevHub's security posture, threat model, and how to report vulnerabilities.

**Last updated:** 2026-04-30

---

## Reporting a Vulnerability

If you've found a security issue, please email **security@devhub.app** instead of opening a public issue.

We will:
- Acknowledge within 48 hours
- Provide an initial assessment within 7 days
- Keep you updated as we work on a fix
- Credit you (with permission) once resolved

Please do **not** publicly disclose until we've had a chance to fix it.

---

## Threat Model

### Assets we protect

| Asset | Sensitivity | If breached |
|---|---|---|
| User passwords | Critical | Account takeover; legal liability |
| Email addresses | High | Spam, phishing of users |
| Private drafts | High | Loss of user trust |
| API tokens | High | Unauthorized API use, data exfiltration |
| Payment info | We never store this — Mollie does | N/A |
| User session tokens | High | Session hijacking |
| Audit logs | Medium | Loss of accountability trail |

### Threats we actively defend against

1. **Account takeover** via password compromise → mitigated by strong hashing (bcrypt), 2FA option, login throttling, alerting on new device
2. **CSRF** → Laravel CSRF tokens on all state-changing routes
3. **XSS** → output escaping by default (Blade `{{ }}`), CSP headers, sanitized markdown rendering
4. **SQL injection** → all queries via Eloquent / parameter binding; Larastan blocks raw queries
5. **Mass assignment** → every model has `$fillable`; CI lint check enforces
6. **Privilege escalation** → policies on every authorize-able action; admin actions audit-logged
7. **Open redirect** → only redirect to vetted internal routes after auth flows
8. **Subdomain takeover** → DNS records audited quarterly; Forge handles cert validation
9. **Webhook forgery (incoming)** → HMAC signature validation
10. **Webhook forgery (outgoing)** → we sign with HMAC, document in API docs

### Threats we accept (with rationale)

- **Sophisticated DDoS**: at our scale, Cloudflare-style protection is overkill; mitigation plan in [RUNBOOK.md](./RUNBOOK.md) if it ever happens
- **Insider threats**: solo project, single admin; risk accepted, audit log provides forensics
- **Zero-days in dependencies**: we run `composer audit` weekly, can't preempt unknown vulnerabilities

---

## Security Practices

### Authentication

- Passwords hashed with bcrypt (cost 12)
- 2FA via TOTP (recovery codes provided once, force re-download for new ones)
- Login throttling: 5 attempts per minute per email + IP
- Password reset tokens expire in 60 minutes, single-use
- Email verification required before posting
- Session timeout: 2 weeks idle, 30 days max
- "Log out other sessions" available in settings
- New-device email alert with IP + approximate location

### Authorization

- Policies on every model; admin bypass via `before()` hook
- Role-based access: Member / Moderator / Admin (PHP enum)
- Per-resource checks (e.g., post owner) on top of role
- Impersonation logged + banner shown; sensitive actions blocked while impersonating

### Data in transit

- HTTPS enforced (HSTS with includeSubDomains, preload)
- TLS 1.2 minimum
- Certificate via Let's Encrypt, auto-renewed

### Data at rest

- Database encryption at rest (Hetzner managed Postgres provides this)
- Object storage encryption at rest (S3 SSE)
- Backups encrypted with separate key
- App-level encryption for: 2FA secrets, OAuth refresh tokens, webhook secrets

### Headers

```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' wss:
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

### Dependencies

- `composer audit` runs weekly via CI
- Renovate / Dependabot for automated dependency PRs
- Critical CVEs patched within 72 hours

### Logging & Audit

- Auth events (login, logout, password change, 2FA changes) logged
- Admin actions logged via audit trail
- Failed login attempts logged (rate-limit fuel + forensics)
- Sensitive data redacted from logs (passwords, tokens, payment details)

### Backups

- Daily DB backup, 30 days retention
- Weekly Meilisearch snapshot
- Quarterly restore tests (see [RUNBOOK.md](./RUNBOOK.md))

---

## Privacy

Per [PRODUCT.md](./PRODUCT.md), privacy is a principle, not an afterthought.

- **No third-party analytics scripts** on user-facing pages
- **No cross-site tracking, no fingerprinting**
- **Server-side event tracking only**, hashed user IDs
- **GDPR compliance**: data export, deletion, rectification within 30 days
- **Cookie banner**: only the bare minimum, no consent-spam
- **EU data residency**: all infra in EU (Hetzner Germany)
- **Email**: Postmark stores delivery metadata only, configured to not store email body content beyond delivery

### What we collect

| Data | Why | Retention |
|---|---|---|
| Email | Account + comms | While account exists |
| Username, profile fields | Identity | While account exists |
| Posts, comments, reactions | The product | While account exists; "[deleted]" tombstone if removed with replies |
| Login IP + user agent | Security alerts, audit | 90 days |
| Server logs | Debugging | 30 days |
| Payment metadata (not card) | Billing | 7 years (legal requirement, BTW) |

### What we don't collect

- Browsing history outside DevHub
- Social graph beyond follows
- Device fingerprints
- Behavioral biometrics
- Anything we don't have a use for

---

## Security Review Cadence

| Activity | Frequency |
|---|---|
| `composer audit` | Weekly (CI) |
| Dependency PRs review | Weekly |
| Subdomain / DNS audit | Quarterly |
| Backup restore test | Quarterly |
| Secret rotation | Quarterly (or on suspected compromise) |
| Pen test (third-party) | Annually once revenue justifies (~€2k+) |
| Security headers / CSP review | Annually |
| Privacy policy review | Annually |

---

## Incident Response

For security incidents specifically (vs general outages):

1. **Contain** — disable compromised credentials, revoke sessions, take affected feature offline if needed
2. **Assess** — what data was accessed, by whom, for how long
3. **Notify** — affected users within 72 hours (GDPR requirement). Be honest about scope.
4. **Remediate** — fix the root cause, not just symptoms
5. **Post-mortem** — write public-facing summary if material

Template for breach notification email lives in `resources/views/emails/security-incident.blade.php`.

---

## Open Items

Things we haven't done yet but should:

- [ ] Bug bounty program (consider once we have revenue)
- [ ] Pen test (annual, post-launch)
- [ ] Web Application Firewall (Cloudflare) — evaluate at scale
- [ ] Hardware security keys (WebAuthn) as 2FA option — post-v1
- [ ] Anomaly detection on login (geo, device velocity) — post-v1

These are tracked in [ROADMAP.md](./ROADMAP.md) under v1.x.
