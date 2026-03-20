# Feature Research

**Domain:** WordPress membership registration plugin for small clubs and associations
**Researched:** 2026-03-20
**Confidence:** HIGH (core features), MEDIUM (differentiators and anti-features informed by ecosystem analysis)

## Feature Landscape

### Table Stakes (Users Expect These)

Features a club admin assumes exist. Missing any of these and the plugin feels broken or unusable.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Frontend registration form via shortcode | Every membership plugin embeds a form this way; club admin expects paste-and-done | LOW | `[membership_form]` shortcode; works in Classic, Gutenberg, and page builders |
| Configurable form fields (add/remove/reorder) | Clubs differ — sports clubs need emergency contacts, hobby clubs don't; one fixed form fits nobody | MEDIUM | Admin UI to define field list; at minimum: text, email, textarea, select, radio, checkbox, date |
| Required/optional field toggle per field | Club admin must be able to mark fields mandatory without code | LOW | Standard form builder expectation |
| Field label and placeholder configuration | Admin sets the label text they want ("Date of birth" vs "Geburtsdatum") | LOW | Per-field label + placeholder config in admin UI |
| On-submit PDF generation | This is the core value proposition; without PDF the plugin has no reason to exist | HIGH | HTML-to-PDF server-side; must include all submitted field values |
| Member receives PDF by email after submission | Standard expectation from any digital form that produces a document | MEDIUM | Attaches generated PDF; uses wp_mail(); email body configurable |
| Admin receives PDF copy by email | Club admins need to receive and file the form; no member database in v1 | LOW | Second wp_mail() call to admin recipient(s); same PDF attached |
| Configurable admin recipient email(s) | Different clubs have different club secretaries; hardcoding fails immediately | LOW | Settings field; comma-separated or textarea for multiple addresses |
| Spam/bot protection | Public registration forms are immediately targeted by bots | MEDIUM | Honeypot field (no external service required) as minimum; optional reCAPTCHA integration |
| Success message after submission | Member needs confirmation the form went through | LOW | Configurable text shown after successful submit |
| Basic input validation (required fields, email format) | Standard form behavior; users expect broken submissions to be caught | LOW | Client-side + server-side validation |
| GDPR/privacy consent checkbox | Required by law in most EU jurisdictions; clubs operating in EU need this | LOW | Configurable consent text; checkbox required before submit; logged with submission |

### Differentiators (Competitive Advantage)

These features align with the project's core value: digitize the paper membership form for clubs that currently use spreadsheets, with zero SaaS cost.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Admin-configurable PDF template layout | Most form-to-PDF plugins map data to fixed templates; this plugin lets the admin control how the PDF looks without touching code | HIGH | Visual or field-order-based layout config; club branding (logo, club name, colors) in the PDF header |
| Blank PDF download (manual fallback) | Clubs still need a paper form for members without internet access or who prefer paper; the same template renders an empty form | MEDIUM | Same PDF template, rendered with empty fields; available as static download link |
| Webhook on submission (optional) | Enables integration with Make, Zapier, n8n, Google Sheets, CRM, etc. without writing code | MEDIUM | HTTP POST with JSON payload; configurable URL in admin; optional HMAC signature for security |
| JSON payload by email (optional) | Some clubs have a tech-savvy secretary who wants to import data into a spreadsheet or database | LOW | Additional email with JSON body of the submission; same admin recipient list |
| Docker-based local dev environment | Lowers the barrier for contributors significantly; no MAMP/WAMP setup required | MEDIUM | docker-compose.yml with official WordPress image + MySQL; plugin directory mounted as volume |
| Developer guide (setup, local dev, testing, PR workflow) | Open-source plugins without documentation get zero community contributions | LOW | CONTRIBUTING.md + docs/dev-guide.md covering Docker setup, test running, PR expectations |
| User guide (installation, configuration, usage) | Non-technical club admins need a step-by-step guide; no guide = support burden | LOW | docs/user-guide.md covering install from ZIP, settings walkthrough, shortcode usage |
| No SaaS dependency, fully self-hosted | Unlike JotForm, Typeform, or MembershipWorks, nothing leaves the WordPress server; appeals to privacy-conscious clubs and EU data residency requirements | LOW (by design) | Architecture constraint: no third-party API calls for core features |

### Anti-Features (Commonly Requested, Often Problematic)

Build these and v1 never ships, or the plugin becomes unmaintainable.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Member database / admin list view | "I want to see all my members in WordPress" | Turns the plugin into a CRM; massive scope increase; GDPR implications for stored PII; v1 delays by months | Email delivery is sufficient for v1; member records land in the admin's inbox; a CSV export add-on is a clean v2 feature |
| Payment / dues collection | "Charge members at registration" | Requires payment gateway integration (Stripe, PayPal), refund logic, receipts, PCI compliance considerations; completely different product | Clubs handle dues separately; redirect to a payment page or mention dues in confirmation email |
| Filling existing AcroForm PDFs | "We already have our PDF form" | Mapping values to AcroForm fields requires field name discovery, breaks when the PDF changes, and has poor PHP library support; fragile by design | Generate PDF from a configured template; same visual result, full admin control |
| Gutenberg block (v1) | "Blocks are the modern way" | Adds React build toolchain, block.json registration, editor preview complexity; shortcode serves the same embed purpose with a fraction of the code; community PRs become harder | Shortcode works in all editors including Gutenberg (Shortcode block), Classic Editor, and all major page builders; add block in v2 if demand exists |
| Member approval workflow | "Admin should approve before PDF is sent" | Adds a pending state, re-notification logic, approval UI, and rejection emails; doubles the email flow complexity | v1 is self-service; PDF is sent immediately on submission; admin vetting happens offline via the emailed PDF |
| OAuth / social login | "Let members log in with Google" | Members don't need WP accounts for this plugin; login implies a member portal, stored sessions, and profile management — a different product | No WP account needed; submission is stateless |
| Multi-language / i18n in v1 | "Our club has members who speak French" | i18n-ready architecture (text domains) is essential; full translation UI in WP settings is not; translatable strings are table stakes for the plugin API, not a UI feature | Use standard WordPress i18n functions (`__()`, `_e()`) from day one so strings are translatable; let the WP translation ecosystem handle actual translations |
| Conditional field logic | "Show emergency contact only if age < 18" | Adds significant JS complexity to the form renderer and the admin field configurator; high maintenance surface | Provide a fixed field set that club admins configure at the field level; conditional logic is a v2 feature if real demand emerges |
| File upload field (e.g., photo, medical cert) | "We need a profile photo" | File upload adds server storage, file size limits, MIME type validation, and GDPR questions about stored files | Exclude in v1; if photo is needed, ask member to email it separately; add as optional field type in v2 |

## Feature Dependencies

```
[Admin field configuration]
    └──requires──> [Settings page / admin UI]
                       └──required by──> [PDF template configuration]
                       └──required by──> [Email recipient configuration]

[Frontend registration form]
    └──requires──> [Admin field configuration] (to know what fields to render)

[PDF generation on submit]
    └──requires──> [Frontend registration form] (data source)
    └──requires──> [PDF template configuration] (layout source)

[Member email with PDF]
    └──requires──> [PDF generation on submit]

[Admin email with PDF]
    └──requires──> [PDF generation on submit]
    └──requires──> [Admin recipient configuration]

[Webhook on submission]
    └──requires──> [Frontend registration form] (data source)
    └──enhances──> [JSON payload by email] (same data, different delivery)

[Blank PDF download]
    └──requires──> [PDF template configuration] (same template, empty values)

[Docker dev environment]
    └──required by──> [Developer guide]

[Spam protection]
    └──enhances──> [Frontend registration form]

[GDPR consent checkbox]
    └──enhances──> [Frontend registration form]
```

### Dependency Notes

- **Field configuration must precede everything**: The form renderer, PDF generator, and email system all derive their structure from the admin-configured field list. Settings page is Phase 1.
- **PDF template config depends on field config**: You can't configure where "First Name" appears on the PDF if "First Name" isn't defined as a field yet. These belong in the same phase or PDF config comes immediately after.
- **Webhook and JSON email share the same data shape**: Both use the same serialized submission payload; implement one and the other is a small addition.
- **Blank PDF download is nearly free once PDF generation exists**: Same code path, null-valued field data.
- **Docker env and developer guide are decoupled from user-facing features**: They can be built in parallel with any other phase and should be done early to enable contributor onboarding.

## MVP Definition

### Launch With (v1)

Minimum viable for the pilot club — real members must be able to register and receive their PDF.

- [ ] Settings page: configure form fields (label, type, required toggle) — without this nothing works
- [ ] Settings page: configure PDF template layout and club branding — without this PDF has no value
- [ ] Settings page: configure admin recipient email(s) — without this admin never gets the form
- [ ] Frontend shortcode renders the configured form — the member-facing surface
- [ ] Client-side + server-side validation (required fields, email format) — basic correctness
- [ ] GDPR consent checkbox (configurable text, required before submit) — legal necessity for EU clubs
- [ ] Honeypot spam protection — public forms are immediately spammed without this
- [ ] PDF generation on submission with all field values — the core product
- [ ] Member receives PDF by email after submission — core value delivery
- [ ] Admin receives PDF copy by email — club operations requirement
- [ ] Success message shown after submission — basic UX
- [ ] Docker dev environment (docker-compose.yml) — needed to develop and test the above
- [ ] User guide (install, configure, use) — non-technical admin needs this on day one

### Add After Validation (v1.x)

Add when pilot club confirms the core works and community interest exists.

- [ ] Webhook on submission — trigger when Make/Zapier users request it
- [ ] JSON payload by email — add alongside webhook; minimal extra work
- [ ] Blank PDF download link — add once PDF template system is stable
- [ ] Developer guide (CONTRIBUTING.md) — add once Docker env is proven and first external issue arrives
- [ ] reCAPTCHA option alongside honeypot — add if spam complaints emerge from pilot

### Future Consideration (v2+)

Defer until v1 is validated and there is demonstrated demand.

- [ ] Member database / CSV export — only if clubs report that email filing is insufficient
- [ ] Gutenberg block — only if shortcode generates complaints or block-only page builders become dominant
- [ ] Conditional field logic — only if pilot club has a concrete use case
- [ ] File upload field type — only if a club type genuinely requires it
- [ ] Multi-step / wizard form — only if long forms cause abandonment
- [ ] Payment integration — only if dues-at-registration becomes a clear pattern among users

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Admin field configuration UI | HIGH | MEDIUM | P1 |
| PDF generation on submit | HIGH | HIGH | P1 |
| Member email with PDF | HIGH | LOW | P1 |
| Admin email with PDF | HIGH | LOW | P1 |
| Frontend shortcode / form render | HIGH | MEDIUM | P1 |
| Admin recipient email config | HIGH | LOW | P1 |
| GDPR consent checkbox | HIGH | LOW | P1 |
| Admin PDF template config | HIGH | HIGH | P1 |
| Input validation (client + server) | MEDIUM | LOW | P1 |
| Honeypot spam protection | MEDIUM | LOW | P1 |
| Success message | MEDIUM | LOW | P1 |
| Docker dev environment | HIGH (developer) | MEDIUM | P1 |
| User guide | HIGH (admin) | LOW | P1 |
| Webhook on submission | MEDIUM | MEDIUM | P2 |
| JSON payload by email | MEDIUM | LOW | P2 |
| Blank PDF download | MEDIUM | LOW | P2 |
| Developer guide | MEDIUM (contributors) | LOW | P2 |
| reCAPTCHA option | LOW | LOW | P2 |
| Member database | MEDIUM | HIGH | P3 |
| Gutenberg block | LOW | MEDIUM | P3 |
| Conditional field logic | LOW | HIGH | P3 |

**Priority key:**
- P1: Must have for launch
- P2: Should have, add when possible
- P3: Nice to have, future consideration

## Competitor Feature Analysis

These are the closest analogues in the WordPress ecosystem. None solves exactly this problem (registration-form-to-printed-PDF for a non-paying club), which is why the plugin has a reason to exist.

| Feature | MemberPress / Ultimate Member | Gravity PDF + Gravity Forms | Our Approach |
|---------|-------------------------------|----------------------------|--------------|
| Member registration form | YES — full form builder; assumes WP accounts | YES — full Gravity Forms builder | YES — lightweight admin field config, no WP account required |
| PDF generation | NO native PDF; paid add-ons available | YES — primary feature; highly configurable | YES — built-in, self-hosted |
| Email PDF to member | NO — these plugins gate content, not deliver forms | YES — Gravity PDF core feature | YES — built-in |
| Email PDF to admin | NO | YES | YES — built-in |
| Configurable PDF template | NO | YES — extensive template system | YES — simpler admin-friendly config |
| No payment required | NO — all major plugins assume paid tiers | NO — Gravity Forms is paid | YES — entirely free, no subscription |
| No WP user account created | NO — these plugins create WP users | Depends on setup | YES — stateless submission, no WP user |
| Webhook support | Plugin-dependent add-ons | YES via add-ons | YES — optional, built-in |
| Self-hosted, no SaaS | YES | YES | YES |
| Blank PDF download (empty form) | NO | Manual workaround only | YES — same template, empty values |
| Suitable for non-technical admin | MEDIUM — complex settings | LOW — Gravity Forms is complex | HIGH — minimal settings, clear UI |

The gap this plugin fills: **a non-paying small club needs a printable PDF membership form that members can fill digitally and receive instantly, without the club subscribing to any SaaS or managing a WordPress user database.**

## Sources

- WPBeginner: [14 Best WordPress Membership Plugins Compared (2026)](https://www.wpbeginner.com/plugins/5-best-wordpress-membership-plugins-compared/)
- Hostinger: [15 Best WordPress Membership Plugins Reviewed (2026)](https://www.hostinger.com/tutorials/best-wordpress-membership-plugins)
- Gravity PDF: [Automate Personalized PDF Documents for Gravity Forms](https://gravitypdf.com/)
- Gravity PDF plugin (WordPress.org): [Gravity PDF – WordPress plugin](https://wordpress.org/plugins/gravity-forms-pdf-extended/)
- Formidable Forms: [Form to PDF WordPress Plugin](https://formidableforms.com/features/form-to-pdf-wordpress-plugin/)
- WPForms: [Webhooks Addon](https://wpforms.com/features/webhooks-addon/)
- Jotform templates: [Club Membership Registration Form](https://www.jotform.com/form-templates/club-membership-registration-form)
- Jotform templates: [Association Membership Application Form](https://www.jotform.com/form-templates/association-membership-application-form)
- Simple Membership Plugin: [simple-membership-plugin.com](https://simple-membership-plugin.com/)
- Plugin Machine: [Using @wordpress/env For Docker-Based Local Development](https://pluginmachine.com/using-wordpress-env-for-docker-based-local-development/)
- WordPress.org Webhook plugin: [WP Webhooks](https://wordpress.org/plugins/wp-webhooks/)
- WordPress developer docs FAQ: [Block Editor FAQ](https://developer.wordpress.org/block-editor/principles/faq)

---
*Feature research for: WordPress membership registration plugin (small clubs and associations)*
*Researched: 2026-03-20*
