# ADR-0001: Record Architecture Decisions

**Date:** 2026-04-30
**Status:** Accepted
**Deciders:** Vitalii

---

## Context

DevHub is a multi-month, multi-feature project that will accumulate dozens of decisions: technical (which queue driver, which search engine, which auth strategy) and product (which features to cut, which user segments to target, which pricing model). Without a record, these decisions get lost. Six months later, someone (often me) asks "why did we do it this way?" and there's no answer — just code that looks arbitrary.

The cost of *not* recording decisions:
- Re-litigating settled questions
- Reverting good decisions because the rationale is invisible
- Onboarding new contributors slowly because context lives only in my head
- Interview answers like "I think we used Postgres because... I don't remember" instead of confident reasoning

## Decision

We will record every architecturally or product-significant decision as an Architecture Decision Record (ADR) in `docs/decisions/`.

### Format

- Filename: `NNNN-kebab-case-title.md` (zero-padded sequential number)
- Template: this document's structure (Context, Decision, Consequences, Alternatives Considered)
- Status values: Proposed, Accepted, Deprecated, Superseded by ADR-XXXX

### What qualifies as "architecturally significant"

An ADR is required when the decision:
- Is hard to reverse (database choice, API versioning scheme, pricing model)
- Affects multiple parts of the system (auth strategy, caching layer)
- Trades off competing values (privacy vs personalization, simplicity vs flexibility)
- Will be questioned later (any choice that isn't the obvious default)
- Was a close call between two reasonable options

An ADR is NOT required for:
- Conventional choices (using Pest because the project standardized on it — that's already an ADR)
- Implementation details within a single class
- Style/formatting decisions (handled by Pint config)
- Reversible experiments behind feature flags

### Workflow

1. When making a qualifying decision, draft an ADR before or during implementation
2. ADR may start as Proposed if soliciting feedback
3. Move to Accepted when the code lands
4. Never edit an Accepted ADR's decision section — instead, write a new ADR that supersedes it, and mark the old one Superseded
5. ADRs are linked from the relevant code's docblock when non-obvious

### What goes in each section

**Context** — Why this decision needs to be made. The problem, the constraints, the relevant history. Should be readable by someone joining the project who has no prior context.

**Decision** — What we're doing. Specific. "We will use X" not "We should consider X."

**Consequences** — What this decision causes, both positive and negative. Future readers need to know what trade-offs we accepted. This is the most undervalued section — be honest about what we gave up.

**Alternatives Considered** — At least two options we rejected, with brief rationale. This shows the decision was made deliberately, not by default.

## Consequences

**Positive:**
- Future-me (and anyone else) can audit reasoning
- ADRs become interview gold — concrete examples of senior decision-making
- Slows down decisions just enough to make them better
- Forces alternatives consideration before committing
- New contributors can read the decisions folder to understand the project's intent

**Negative:**
- Overhead per decision (10-30 min to write a good ADR)
- Risk of over-documenting trivial choices
- Risk of ADRs going stale if not maintained
- Risk of ADRs becoming performative — written to look thorough rather than to capture real reasoning

**Mitigations for the negatives:**
- Strict bar for what qualifies (see above) — most decisions don't need an ADR
- ADRs are short by default (1-2 pages, not essays)
- Quarterly review: are any ADRs misleading because reality has diverged?

## Alternatives Considered

**1. No formal record, rely on commit messages and code comments.**
Rejected. Commit messages get lost in git log. Code comments answer "what" but not "why this approach over alternatives."

**2. Wiki / Notion / Confluence.**
Rejected. Decision context lives best with the code that implements it. Versioned alongside code, reviewed in PRs, immutable history. External wikis drift.

**3. RFC process (longer, with explicit review period).**
Rejected for now. Solo project; lightweight ADR is sufficient. May upgrade to RFC for major decisions if/when team grows.

**4. Lightweight format (just a paragraph in the PR description).**
Rejected. PR descriptions get squashed away. Future readers can't easily browse "all decisions made on this project."

## References

- Michael Nygard's original ADR proposal (the format that inspired this)
- ThoughtWorks Tech Radar's adoption of ADRs
- `docs/PRODUCT.md` for product principles ADRs should align with
