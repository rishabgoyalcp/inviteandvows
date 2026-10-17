# Invite & Vows

Digital invitations marketplace and builder (CodeIgniter 4, PHP 8.2+). See [PLAN.md](PLAN.md).

## Local setup

```
composer install
cp .env.example .env     # then edit: use SQLite for local dev (see below)
php spark migrate
php spark db:seed CoreSeeder
php spark db:seed DemoSeeder    # optional: placeholder fields, templates and prices
php spark serve
```

Local `.env` for SQLite:

```
database.default.DBDriver = SQLite3
database.default.database = dev.db
admin.email = you@example.com
admin.password = a-strong-password
```

Admin login: `/login`, then `/admin`. Domains map to markets through the `regions` table, so test the
`.com` market with `curl -H "Host: inviteandvows.com" localhost:8080/`.

## Status

- Phase 0 (foundation): done.
- Phase 1 (website invites): built. Template gallery, live builder with photo upload, account at checkout, Razorpay checkout and webhook (`/webhooks/razorpay`), public invite page at `/i/{code}` and `wd2.in/{code}`, RSVP, customer dashboard with CSV export, admin screens for event types, fields, templates and prices.
- Without Razorpay keys, a developer-only fake payment button appears (development environment only).
- Razorpay payment was not tested against the real gateway yet; add test keys to `.env` and try a test payment.
