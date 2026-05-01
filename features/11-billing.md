# Prompt 11 — Billing & Plan Limits

Add subscription billing. Even though DevHub doesn't strictly need it, this is the most career-impacting code you'll write — every B2B Laravel role uses these patterns.

## Concepts demonstrated

- Laravel Cashier (Mollie or Stripe — pick based on your market; Mollie for NL/EU, Stripe for global)
- Plan limits enforcement
- Soft vs hard limits with upsell prompts
- Trial logic, proration, grandfathering
- Dunning emails
- Usage metering

## Tasks

1. **Install Cashier**
   - For NL/EU: `laravel/cashier-mollie`
   - For global: `laravel/cashier` (Stripe)
   - Configure webhook endpoint

2. **Plans (seed data, not in DB unless dynamic)**
   - **Free**: 5 posts/month, 50 followers max, no API access, no analytics
   - **Pro** (€9/mo): unlimited posts, unlimited followers, API access (1000 req/h), basic analytics, custom profile theme
   - **Pro Annual** (€90/yr): same as Pro, 2 months free framing
   - Define in `config/plans.php` with limits as structured array

3. **Limit enforcement**
   - `app/Support/PlanLimits.php` — reads user's plan, returns limit for given feature
   - Custom middleware `EnforcePlanLimit:posts` for API
   - In `CreatePostAction`: check `PlanLimits::for($user)->canCreatePost()` — throws `PlanLimitExceeded` if not
   - Soft limit: at 80% show banner "You've used 4/5 posts this month, upgrade for unlimited"

4. **Upgrade flow**
   - `/billing` Livewire page showing current plan + usage meters
   - Plan comparison table
   - Checkout via Cashier's hosted checkout
   - On success, redirect to `/billing/success` with confetti and "what's new in Pro" tour

5. **Trial**
   - 14-day Pro trial on signup, no card required
   - Day 12: email "Your trial ends in 2 days"
   - Day 14: downgrade to Free, email "Trial ended, here's what you'll lose"
   - Day 21: win-back email with discount code

6. **Proration & changes**
   - Upgrade mid-cycle: prorated charge handled by Cashier
   - Downgrade: schedule change for end of cycle, show "Your plan changes to Free on {date}"
   - Cancel: stays Pro until end of period, can resubscribe before then to undo

7. **Dunning**
   - On `invoice.payment_failed` webhook: email user, mark account in grace period
   - 3 retries over 7 days
   - After final fail: downgrade to Free, email explaining
   - Banner during grace period with "Update payment method" CTA

8. **Grandfathering**
   - When you raise prices, existing subscribers keep their price
   - Implement via `legacy_plan_price` field — ADR the strategy

9. **Receipts & invoices**
   - `/billing/invoices` lists past invoices
   - Download PDF (Cashier provides this)
   - VAT handling for EU (BTW shown separately for NL customers)

## Product thinking

- Pricing page that doesn't suck: clear comparison, FAQ, "still have questions?" link
- Show annual savings prominently ("Save €18/year")
- Cancellation flow asks why (one-question survey), offers pause instead, offers discount
- Internal admin can manually extend trial, apply discount, switch plans

## Tests

- Plan limit blocks post creation when at max
- Trial expiration job downgrades correctly
- Webhook handlers update local state
- Proration calculation matches expected
- VAT applied correctly for NL vs non-EU

## Definition of Done

- ADR: "Mollie vs Stripe choice"
- ADR: "Plan limits storage: config vs DB"
- ADR: "Grandfathering implementation"
- Manual: upgrade, downgrade, cancel, fail-payment all work end-to-end
- `composer check` clean
