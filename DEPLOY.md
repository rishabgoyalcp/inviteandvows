# Deploying on Hostinger

This is a checklist, not a guarantee: the code was tested on PHP 8.3 with MariaDB 10.11 and SQLite, not on Hostinger itself.
Do the steps on a staging domain first (for example a Hostinger temporary URL) before pointing inviteandvows.in at it.

## 1. What the server needs

- PHP 8.2 or newer, with these extensions switched on in hPanel > PHP Configuration: `intl`, `mbstring`, `mysqli`, `curl`, `zip`, `sodium`, `fileinfo`, `gd` (optional).
- A MySQL or MariaDB database (hPanel > Databases): note the database name, user and password.
- HTTPS (free SSL in hPanel) on every domain.
- SSH is helpful (migrations, Composer) but not required: see 4b.

## 2. Domains

- inviteandvows.in is the main site, inviteandvows.com the global one, and wd2.in the short-link domain.
- All three must serve the same folder. In hPanel add the other two as additional or parked domains pointing at the same document root.
- Point the document root at the project's `public/` folder (hPanel > Websites > Manage > Advanced, or the domain's folder setting).
- If you cannot change the document root, copy `deploy/root.htaccess` to the project root as `.htaccess`. It sends everything to `public/` and blocks `app/`, `writable/`, `.env` and the rest.

## 3. Get the code on the server

Option A: Git. hPanel > Advanced > Git: connect `rishabgoyalcp/inviteandvows` (branch `main`) and deploy.
Option B: upload the files with the File Manager or FTP.

Then install the PHP libraries:

- With SSH: `composer install --no-dev --optimize-autoloader`
- Without SSH: run that command on your own computer and upload the resulting `vendor/` folder.

Make `writable/` and `public/uploads/` and `public/videos/` writable by the web server (permission 755 or 775).

## 4. Database

a) With SSH: `php spark migrate`, then `php spark db:seed CoreSeeder` and `php spark db:seed LanguageSeeder`.

b) Without SSH: in hPanel > phpMyAdmin, open the empty database and import `deploy/install.sql`. It holds the tables plus the starter data (regions, languages, event types). Regenerate it after schema changes with `mariadb-dump` on a freshly migrated and seeded database.

Create your admin user. Generate a password hash on your computer: `php -r 'echo password_hash("YOUR-PASSWORD", PASSWORD_DEFAULT);'` and run this SQL (change the email and the hash):

```sql
INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
VALUES ('Admin', 'you@example.com', '<hash>', 'admin', 1, NOW(), NOW());
```

(With SSH you can instead set `admin.email` and `admin.password` in `.env` before running the `CoreSeeder`, then remove them.)

## 5. The `.env` file

Copy `.env.example` to `.env` on the server and fill it in. Important lines:

```
CI_ENVIRONMENT = production
app.baseURL = 'https://inviteandvows.in/'
app.forceGlobalSecureRequests = true
cookie.secure = true
app.shortDomain = 'wd2.in'
database.default.hostname = localhost
database.default.database = ...
database.default.username = ...
database.default.password = ...
razorpay.keyId = rzp_live_...
razorpay.keySecret = ...
razorpay.webhookSecret = ...
email.* = your Hostinger mailbox SMTP settings
business.name / business.email / business.phone / business.address / business.gstin
updates.publicKey = (see section 8)
```

`CI_ENVIRONMENT = production` also turns the fake "simulate payment" button off. Without Razorpay keys, checkout shows an error in production.
The domain of each region is stored in the `regions` table (`inviteandvows.in` and `inviteandvows.com`). Change it there if you use a different domain while testing.

## 6. Razorpay

1. Add your website to the Razorpay dashboard and submit the pages it asks for. Ready-made drafts are at `/privacy`, `/terms`, `/refunds` and `/contact`. Have them reviewed by someone qualified before you go live; they are templates, not legal advice.
2. Settings > Webhooks: URL `https://inviteandvows.in/webhooks/razorpay`, events `payment.captured` and `order.paid`, and put the secret you choose into `razorpay.webhookSecret`.
3. For the `.com` site, ask Razorpay to enable international payments, or add another payment driver (see `app/Libraries/Payments`).
4. Make a small real payment and check that the invite publishes and the order email arrives.

## 7. First look

- Sign in at `/login`, open Admin and set up: event types and fields, templates (Design in browser), prices per region, languages (switch on only reviewed ones), coupons.
- Remove or deactivate the placeholder demo templates before launch if you ran `DemoSeeder`.

## 8. Updating the site later (Admin > Updates)

On your own computer, once:

```
php tools/make-keys.php        # writes release.key (keep it secret, never upload it) and prints the public key
```

Put the printed `updates.publicKey = ...` line into the server's `.env`.

For each release:

```
# change VERSION is done for you by the build; write the notes in the command
php tools/build-release.php 1.1.0 "What changed" --no-vendor
```

Use `--no-vendor` for code-only releases (small). If you changed Composer packages, run `composer install --no-dev --optimize-autoloader` first and leave the flag off.
This writes `dist/invite-and-vows-1.1.0.zip` and `.zip.sig`. In Admin > Updates upload both. The site then:

1. checks the signature (a package not signed with your key is refused),
2. checks every file path and every PHP file's syntax,
3. makes a backup (database plus the files it will replace),
4. shows visitors an update notice while it copies files and runs the new database migrations,
5. puts everything back automatically if any step fails.

Admin > Updates also lists backups (download, restore, delete) and the history. Backups do not include `public/uploads` and `public/videos`; copy those yourself now and then, or use Hostinger's backups.

## 9. Before you tell anyone

- Run through the site on a phone: build an invite, pay, open the invite link, RSVP, make a video.
- Check the order email arrives and is not in spam (set up SPF and DKIM for your sending domain in hPanel).
- Check `https://inviteandvows.in/sitemap.xml` and `/robots.txt`, then add the site to Google Search Console.
- Switch on only the languages a native speaker has reviewed.
