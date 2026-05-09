# DevHub

> A calmer place for developers to read and write deep technical content.

[![CI](https://github.com/vitalii/devhub/actions/workflows/ci.yml/badge.svg)](https://github.com/vitalii/devhub/actions)
[![Coverage](https://img.shields.io/badge/coverage-85%25-brightgreen)](docs/PERFORMANCE.md)

DevHub is a developer community platform built deliberately around depth, calm, and respect for the reader's time. No engagement-bait, no trending feed, no streaks.

**🧭 New here?** Start with [docs/PRODUCT.md](docs/PRODUCT.md) to understand who this is for and why it exists.

---

## Why This Project Exists

DevHub is a portfolio project demonstrating senior-level Laravel patterns *and* product engineering thinking. Every feature is built deliberately with documented reasoning — see [docs/decisions/](docs/decisions/) for the architecture decision records.

If you're a hiring manager: read three random ADRs and one section of [docs/PRODUCT.md](docs/PRODUCT.md) before scrolling code. That's where the senior signal lives.

## Stack

Laravel 11 · PHP 8.3 · PostgreSQL 16 · Redis · Livewire 3 + Volt · Reverb · Meilisearch · Filament 3 · Cashier (Mollie) · Pest 3

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the full picture.

## Getting Started

```bash
git clone git@github.com:vitalii/devhub.git
cd devhub
cp .env.example .env
composer install && npm install
php artisan sail:install --no-interaction --with=pgsql,redis,meilisearch,mailpit --php=8.4
./vendor/bin/sail up -d
sail artisan key:generate
sail artisan migrate --seed
sail npm run dev
```

Visit `http://localhost`.

Full setup details: [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)

## Documentation

Everything beyond the code lives in `docs/`:

- [PRODUCT.md](docs/PRODUCT.md) — the product, the user, the principles
- [ROADMAP.md](docs/ROADMAP.md) — what's shipped, next, cut
- [METRICS.md](docs/METRICS.md) — what we measure (and don't)
- [ARCHITECTURE.md](docs/ARCHITECTURE.md) — system design
- [DEPLOYMENT.md](docs/DEPLOYMENT.md) — how it ships
- [RUNBOOK.md](docs/RUNBOOK.md) — operations
- [PERFORMANCE.md](docs/PERFORMANCE.md) — budgets and how we enforce
- [SECURITY.md](docs/SECURITY.md) — threat model and posture
- [CONTRIBUTING.md](docs/CONTRIBUTING.md) — how to work in this codebase
- [decisions/](docs/decisions/) — architecture decision records

## Status

Pre-launch. See [ROADMAP.md](docs/ROADMAP.md) for the current milestone.

## Contact

Vitalii — [LinkedIn / email / etc.]

## License

MIT (or whatever you choose)
