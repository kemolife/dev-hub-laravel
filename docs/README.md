# DevHub Documentation

This folder contains everything someone needs to understand DevHub beyond the code itself: who it's for, why it exists, how it's built, how to run it, and the decisions that shaped it.

## Start Here

If you're new to this project, read in this order:

1. [PRODUCT.md](./PRODUCT.md) — who DevHub is for and why it exists
2. [ROADMAP.md](./ROADMAP.md) — what's shipped, what's next, what we're not doing
3. [decisions/](./decisions/) — architectural decision records (ADRs)

## Document Map

| Document | Purpose | Audience |
|---|---|---|
| [PRODUCT.md](./PRODUCT.md) | Product vision, target user, principles, non-goals | Everyone |
| [ROADMAP.md](./ROADMAP.md) | What we're building, in what order, what we cut | Everyone |
| [METRICS.md](./METRICS.md) | What we measure and what we deliberately ignore | Product + engineering |
| [CHANGELOG.md](./CHANGELOG.md) | Released changes by version | Users + contributors |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | High-level system design | Engineering |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | How to deploy, rollback, manage envs | Engineering / ops |
| [RUNBOOK.md](./RUNBOOK.md) | Procedures for incidents and operations | On-call |
| [PERFORMANCE.md](./PERFORMANCE.md) | Performance budgets and targets | Engineering |
| [SECURITY.md](./SECURITY.md) | Security posture, threat model, reporting | Everyone |
| [CONTRIBUTING.md](./CONTRIBUTING.md) | How to work in this codebase | Contributors |
| [decisions/](./decisions/) | All ADRs | Engineering + product |

## How These Docs Are Maintained

- All docs are versioned in git, updated via PR
- Each PR that changes behavior should update the relevant doc
- Docs that go stale are worse than no docs — when in doubt, delete or mark as "needs review"
- The CHANGELOG is updated for every user-facing change at release time
- ADRs are append-only — never edit a decision, supersede it
