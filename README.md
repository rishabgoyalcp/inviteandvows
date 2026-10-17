# Invite & Vows

Digital invitations marketplace and builder (CodeIgniter 4, PHP 8.2+). See [PLAN.md](PLAN.md).

## Local setup

```
composer install
cp .env.example .env     # then edit: use SQLite for local dev (see below)
php spark migrate
php spark db:seed CoreSeeder
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

Phase 0 (foundation) done: schema, region and language handling (English + Hindi active), admin login.
