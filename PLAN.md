# Invite & Vows: Product and Technical Plan (DRAFT v1)

Status: for discussion. Nothing is built yet.

## 1. What we are building

A marketplace and builder for digital invitations. A customer picks a template, personalises it, pays once per template, and receives the invite in the format they bought.

- Any event type: weddings, birthdays, baby showers and more. **Nothing is hardcoded.** Event types, fields, formats and languages are all data in the database and are managed from the admin panel.
- Inspired by shaadipath.com (website invites, live builder, WhatsApp sharing), but wider in scope: more formats and more event types.

### Domains and markets

| Domain | Market | Currency | Languages |
|---|---|---|---|
| inviteandvows.in | India | INR | 10-12 Indian languages plus English |
| inviteandvows.com | Global | USD (more later) | major world languages |
| wd2.in | short links for shared invites | n/a | n/a |

The same codebase serves both sites. The domain decides the default region, currency, language list, payment setup and template catalogue.

## 2. Products (formats sold per template)

Each template can offer one or more formats. Each format has its own price.

| Format | What the buyer gets | Delivery |
|---|---|---|
| **Website invite** | A live invite page with events, gallery, map and RSVP | Hosted link, e.g. `wd2.in/aB3xK9` |
| **Video invite** | A short personalised video | MP4 download plus a share link |
| **Editable Canva design** | A Canva template the buyer edits themselves (image/social size) | Canva template link after payment |
| **Editable Canva PDF** | A print-ready editable invite | Canva link or PDF after payment (to confirm, see section 9) |

Pricing is stored per template and per format. Example: Template #12 costs ₹1,499 as a website, ₹999 as a video and ₹499 as a Canva design. Price is a database field, so it can change without code changes. Coupons and regional prices (INR vs USD) are included.

## 3. Key design principle: nothing hardcoded

- **Event types** (wedding, birthday, baby shower, anniversary, engagement and so on) are rows in `event_types`. An admin can add a new one without a developer.
- **Fields** (bride name, baby name, venue, date and so on) are defined per event type in `field_definitions` as JSON schemas: key, label per language, type (text, date, image, map, list), required or not.
- **Templates** are built against a field schema. A template says which fields it uses and where they go.
- **Sections** (events list, gallery, RSVP, gift registry, countdown and so on) are reusable blocks that a template switches on or off.
- **UI text** lives in language files plus database translations. No visible text is hardcoded in views.

## 4. Languages

- **.in:** English plus about 10-12 Indian languages: Hindi, Bengali, Marathi, Telugu, Tamil, Gujarati, Kannada, Malayalam, Punjabi, Odia, Urdu (right-to-left) and Assamese.
- **.com:** English, Spanish, French, German, Portuguese, Arabic (right-to-left), plus others we decide later.
- Mechanism: CodeIgniter 4 localisation files for the UI. Invite content (the couple's text) is entered by the customer in their chosen language.
- Needs: Noto fonts for each Indic script, right-to-left CSS support, and per-language template font choices.
- Translation of the interface is a content task. I can machine-translate a first draft, but a native speaker should review each language before launch.

## 5. Technical architecture

**Stack:** CodeIgniter 4 (PHP 8.1+), MySQL/MariaDB, Hostinger hosting, Razorpay payments. Frontend: server-rendered views, Tailwind (compiled in CI, committed as a CSS file), and Alpine.js or vanilla JS for the builder.

**Why this works on Hostinger:** no Node server is needed in production. The builder is JavaScript running in the browser, and invite pages are rendered by PHP from stored JSON.

### Main modules

1. **Public site:** home, template gallery with filters (event, format, language, price), template preview, pricing, blog and guides.
2. **Builder:** a form on the left and a live preview on the right, with a mobile preview. It autosaves drafts to the database. The customer can edit freely before paying.
3. **Checkout:** Razorpay Orders API with server-side signature verification and webhooks. Orders unlock the format purchased.
4. **Invite pages:** `wd2.in/{code}` redirects to or serves the invite. It includes OG tags so WhatsApp previews look right, a QR code and an optional password.
5. **RSVP and host dashboard:** guest responses, counts, export to CSV, and edits after publishing.
6. **Admin panel:** event types, field definitions, templates, formats and prices, languages, coupons, orders, users.
7. **Short links:** the `wd2.in` domain points to the same app and only resolves short codes.

### Data model (outline)

`users`, `event_types`, `field_definitions`, `templates`, `template_formats` (price per format and region), `template_translations`, `invites` (customer's content as JSON, status, language, short code), `invite_assets` (photos, music), `rsvps`, `orders`, `payments`, `coupons`, `downloads`, `languages`, `regions` (domain settings).

### Hosting notes (Hostinger)

- Point the document root at CodeIgniter's `public/` folder.
- Use Hostinger's Git deployment from this GitHub repo, or a GitHub Actions deploy.
- Use cron for cleanup of draft invites, expired invites and email reminders.
- Use a cloud plan or VPS if traffic or video rendering needs it. Shared hosting is fine to start.

## 6. Risks and decisions

1. **Video invites are the hardest part.** Shared hosting normally has no ffmpeg or headless rendering. Options:
   - a. A pre-made video template with text and photo slots, rendered by an external service (Creatomate or Shotstack) through an API. Costs per render, simple to run.
   - b. A small VPS with ffmpeg that renders in a queue. Cheaper per render, more work to build and maintain.
   - c. Ship website invites first and add video in a later phase. **Recommended.**
2. **Canva editable products:** Canva does not allow fully automated personalisation of a buyer's copy through a simple API. Normal approach: after payment the buyer receives a "use template" link to a Canva template you design. This is manual to create but needs no code beyond secure delivery of the link.
3. **Razorpay for .com:** international payments must be enabled on the Razorpay account and need approval. A backup for the global site is Stripe or PayPal. The code will use a payment-provider interface so this can be added.
4. **Hosting capacity:** photo uploads and many visitors to wedding pages can spike. Use image resizing on upload and a CDN such as Cloudflare in front.
5. **Legal:** privacy policy, terms and refund policy for digital goods, GST invoices for India.

## 7. Phases

**Phase 0: Foundation** (this repo)
- CodeIgniter 4 project, database migrations, multi-domain config, language switching, admin login.

**Phase 1: Website invites MVP**
- Event types and field definitions in admin, two or three placeholder templates, the live builder, publishing, short links, RSVP, Razorpay checkout and the host dashboard.

**Phase 2: Editable Canva products**
- Product delivery after payment, secure download or link pages, and order emails.

**Phase 3: Languages and the .com site**
- All Indian languages on .in, global languages on .com, currency and region pricing, SEO pages.

**Phase 4: Video invites**
- Pick option 1a or 1b above and add video as a format.

**Phase 5: Growth**
- Coupons, referral, guest list import with personalised links, WhatsApp reminders, blog and planning tools.

## 8. What I need from you later

- Template designs (you said you will provide them). I will build the template system first so designs can be dropped in as data plus a view file.
- Razorpay account keys (test mode first), Hostinger access or a deploy method, and DNS control for the three domains.
- Brand colours, logo, and the exact event types you want at launch.

## 9. Open questions

1. **Canva PDF:** is "Editable Canva PDF" a Canva link to an editable print design (A5 or A4), or an actual PDF with fillable fields? I assumed a Canva link.
2. **Video invites:** are you happy to start with website invites and add video in Phase 4, or is video needed at launch?
3. **Hostinger plan:** which plan do you have (shared, cloud or VPS)? It decides what we can run.
4. **Accounts:** do customers need to register, or can they build first and sign up at checkout? I recommend the second.
5. **Launch order:** should we launch .in first and add .com later? I recommend this.
6. **Which languages first:** should we launch with English and Hindi and add the rest one at a time?
