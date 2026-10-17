# Invite & Vows

Digital invitations marketplace and builder (CodeIgniter 4, PHP 8.2+). See [PLAN.md](PLAN.md).

## Local setup

```
composer install
cp .env.example .env     # then edit: use SQLite for local dev (see below)
php spark migrate
php spark db:seed CoreSeeder
php spark db:seed LanguageSeeder # fonts per language and translated event type names
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
- Phase 2 (Canva products): admin sets a price and a Canva share link per region for `canva_design` and `canva_pdf` (only canva.com / canva.link links are accepted). Buyers pay at `/buy/{id}`; the link is only reachable by the paying customer through `/dashboard`. Order emails are sent through SMTP (see `.env.example`); a failed email is logged and never blocks the order.
- Phase 3 (languages, .com, SEO): 18 interface languages in `app/Language/*/Site.php` (English and Hindi are reviewed; the other 16 are machine-translated drafts). Languages stay off until switched on in `/admin/languages`. Language URLs `/hi`, `/hi/templates`, `/hi/templates/{slug}` with canonical and hreflang tags, a per-domain `sitemap.xml` and `robots.txt`, Product JSON-LD on template pages, Google Fonts per script, right-to-left layout for Urdu and Arabic, locale-aware prices. A language prefix the current domain does not offer returns 404.
- Phase 4 (video invites): `video` is a priced format like the others. After payment a job is queued in `video_jobs`; `php spark video:render` renders it (scenes from the invite's fields are screenshotted by headless Chromium, so every script shapes correctly, then ffmpeg joins them into a 1080x1920 MP4 in `public/videos/`). Run it every minute from cron on a machine with Chromium and ffmpeg (a VPS; shared hosting normally has neither). Jobs retry up to 3 times, one attempt per run. The customer downloads the MP4 from the dashboard and the invite page plays it above the RSVP form. Scene layout is automatic (two values per scene) until real designs define `config.video.scenes` per template.
- Without Razorpay keys, a developer-only fake payment button appears (development environment only).
- Razorpay payment was not tested against the real gateway yet; add test keys to `.env` and try a test payment.
