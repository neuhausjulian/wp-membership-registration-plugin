# Project Research Summary

**Project:** WordPress Membership Registration Plugin
**Domain:** WordPress plugin — form submission, PDF generation, email/webhook notification
**Researched:** 2026-03-20
**Confidence:** HIGH

## Executive Summary

This is a focused WordPress plugin that digitizes the paper membership registration process for small clubs and associations. The core value proposition is narrow and achievable: a visitor fills a shortcode-embedded form, the plugin generates a branded PDF of the submitted data, and emails it to both the member and the club admin — all without any SaaS subscription, payment gateway, or WordPress user account. Research confirms no existing free plugin solves exactly this problem; the closest alternatives (Gravity PDF + Gravity Forms, MemberPress) are paid or create WP user accounts, which clubs do not need.

The recommended approach is a PSR-4 namespaced PHP plugin using Composer, dompdf 3.x for PDF generation, Alpine.js 3.x for lightweight form UX, and WordPress core APIs exclusively for settings, email, and HTTP. The architecture follows a hook-based pipeline: after a validated form POST, a custom `do_action` fires and three independent handlers (PDFGenerator, Mailer, WebhookDispatcher) respond in priority order. This keeps each component independently testable and the pipeline extensible. Development tooling centers on `wp-env` + Docker, PHPUnit with Brain Monkey for unit tests, and PHPCS with the WordPress coding standards ruleset enforced from day one.

The most significant risks are not technical complexity but WordPress ecosystem requirements that, if skipped and retrofitted later, impose high cost: nonce verification and input sanitization must be baked into the first form handler; every user-facing string must be wrapped in translation functions from line one; all globals must carry a plugin-specific prefix; and the DOMPDF `$isRemoteEnabled` flag must be set to `false` unconditionally. Email deliverability on shared hosting is the primary operational risk post-launch — the plugin must guide admins to configure SMTP and provide a test-email button. A four-phase build order (Foundation, PDF Engine, Notifications, Admin UX) follows directly from the component dependency graph and the pitfall-to-phase mapping in research.

## Key Findings

### Recommended Stack

The plugin requires no exotic dependencies. PHP 8.0+ and WordPress 6.0+ are project constraints that happen to be well-supported by all chosen libraries. Composer is mandatory for PSR-4 autoloading and to pull in dompdf; without it, namespace-based class loading and library version pinning are unmanageable. Alpine.js 3.x is the right frontend choice specifically because it requires no build step — the shortcode form renders server-side and Alpine's HTML-attribute directives handle conditional UX inline. `@wordpress/env` with Docker provides a reproducible, CI-scriptable development environment that eliminates "works on my machine" problems.

One vendor-bundling risk is non-obvious: shipping `vendor/` with standard Composer namespaces causes fatal class collisions when another plugin ships a different dompdf version. PHP-Scoper or Strauss must prefix all vendor namespaces (e.g., `WmrVendor\Dompdf\Dompdf`) before distribution. This is Phase 2 work but must be planned from the start.

**Core technologies:**
- PHP 8.0+ / WordPress 6.0+: runtime and host platform — project constraints; named arguments and nullsafe operator materially reduce boilerplate
- Composer 2.9.x: dependency management and PSR-4 autoloading — required for dompdf and namespace-based class structure
- dompdf/dompdf 3.1.x: HTML-to-PDF generation — pure PHP, no binary required, 168M+ Packagist installs, WordPress-safe
- Alpine.js 3.15.x: form interactivity — no build step, ~15 KB gzipped, HTML-native directives fit shortcode-rendered forms
- `docker-compose.yml` with official WordPress image: Docker-based local dev — no Node.js/npm required, contributor-friendly, plain Docker Compose (preferred over @wordpress/env for this project)
- WordPress Settings API, `wp_mail()`, `wp_remote_post()`: admin config, email delivery, webhook dispatch — no external libraries needed for these core features

### Expected Features

Research maps features into three tiers with clear dependency ordering. The Settings page (field schema, PDF template, email addresses) must exist before anything else — the form renderer, PDF generator, and mailer all derive their behavior from it.

**Must have (table stakes):**
- Admin field configuration UI (label, type, required toggle) — nothing else works without it
- Frontend registration form via `[membership_form]` shortcode — the member-facing surface
- Client-side and server-side input validation — basic form correctness
- GDPR/privacy consent checkbox with configurable text — legal requirement in EU jurisdictions
- Honeypot spam protection — public forms are spammed immediately without it
- PDF generation on submission with all field values — the core product
- Member receives PDF by email after submission — core value delivery
- Admin receives PDF copy by email — club operations requirement
- Configurable admin recipient email(s) — clubs have different contact people
- Success message after submission — basic UX closure
- `wp-env` Docker dev environment — required to develop and test the above
- User guide (install, configure, use) — non-technical admin needs this on day one

**Should have (differentiators):**
- Admin-configurable PDF template layout with club branding — most competitors use fixed templates
- Blank PDF download link (same template, empty fields) — paper fallback for members without internet
- Webhook on submission with configurable URL — enables Make/Zapier/n8n integration without code
- JSON payload by email — minimal addition once webhook data shape exists
- Developer guide (CONTRIBUTING.md) — enables community contributions
- "Send test email" button in admin UI — critical for diagnosing SMTP configuration before go-live

**Defer to v2+:**
- Member database / CSV export — turns the plugin into a CRM; massive scope increase
- Gutenberg block — shortcode works in all editors; block adds React build toolchain overhead
- Conditional field logic — significant JS complexity; no validated demand from pilot club
- Payment / dues collection — a different product entirely
- File upload fields — storage, MIME validation, and GDPR implications

### Architecture Approach

The plugin follows a strict hook-based pipeline with constructor-free hook registration. A single `Plugin` bootstrap class constructs all components with injected dependencies, then calls an explicit `register()` method on each. No component instantiates another; no hooks fire inside constructors. Form submission triggers `do_action('wmr_form_submitted', $data)` and three independent listeners respond at priorities 10 (PDFGenerator), 20 (Mailer), and 30 (WebhookDispatcher). Settings are centralized in `SettingsManager` which provides typed getter methods (`getFieldSchema()`, `getPdfTemplate()`, `getAdminEmail()`); all other components receive it by injection. The component dependency graph is strictly layered: SettingsManager has no plugin dependencies, PDFGenerator depends only on SettingsManager, Mailer depends on PDFGenerator and SettingsManager, FormHandler depends on SettingsManager and fires the hook consumed by layers above it.

**Major components:**
1. `SettingsManager` — CRUD for all plugin options via WP Options API; base dependency for all other components
2. `AdminPage` — renders settings UI via WordPress Settings API (handles nonce, capability checks, sanitize callbacks automatically)
3. `FormRenderer` — shortcode callback; renders nonce-protected HTML form using field schema from SettingsManager
4. `FormHandler` — processes AJAX submission; verifies nonce, sanitizes, validates, fires `wmr_form_submitted` action
5. `PDFGenerator` — receives submission data, renders HTML template, calls dompdf, writes temp file, annotates `$data['pdf_tmp_path']`
6. `Mailer` — attaches temp PDF to `wp_mail()` calls for member and admin; deletes temp file after send
7. `WebhookDispatcher` — sends JSON payload via `wp_remote_post()` to admin-configured URL (optional, fires last)
8. `Plugin` (bootstrap) — constructs and wires all components; the only class allowed to call `register()` on others

### Critical Pitfalls

1. **Missing nonce verification on the frontend form** — use `wp_nonce_field()` in the form and `check_ajax_referer()` in the AJAX handler; this is a WordPress.org rejection criterion and cannot be retrofitted safely; must be in Phase 1

2. **DOMPDF `$isRemoteEnabled = true`** — this single flag enabled an RCE vulnerability (CVE-2022-28368) in versions prior to 2.0; set it to `false` unconditionally and serve all template assets (logo, CSS, fonts) as local `file://` paths; no exceptions

3. **Generated PDFs stored in `wp-content/uploads/`** — the default writable path is web-accessible by design; personal data (name, DOB, address) in PDF filenames is a GDPR breach; use `sys_get_temp_dir()` exclusively and `unlink()` immediately after `wp_mail()` returns

4. **Hardcoded strings without i18n wrapping** — WordPress.org rejects plugins with user-facing strings not wrapped in `__()` or `esc_html__()`; retrofitting a grown codebase is expensive; enforce via PHPCS from the first commit

5. **Email delivery failure on shared hosting** — `wp_mail()` uses PHP `mail()` by default, which Gmail and Yahoo reject since February 2024 without SPF/DKIM; the plugin cannot fix this but must document SMTP as a requirement and provide a "Send test email" button in admin before the admin configures live email addresses

## Implications for Roadmap

Based on the component dependency graph and pitfall-to-phase mapping, a four-phase build order is strongly indicated. Each phase has a clear deliverable and is unblocked by the previous one.

### Phase 1: Plugin Foundation and Settings

**Rationale:** `SettingsManager` sits at Layer 0 of the dependency graph — every other component depends on it. Without field schema configuration, the form renderer has nothing to render; without email config, the mailer has nowhere to send. The plugin foundation (namespace, prefix, autoloading, lifecycle hooks) must also be established here because retrofitting these is the highest-cost pitfall category. Phase 1 produces no user-facing output beyond an admin settings page, but it is the complete prerequisite for all subsequent phases.

**Delivers:** Working admin settings page (field schema editor, PDF template config, admin email addresses), `Plugin` bootstrap class, `SettingsManager`, `AdminPage`, `uninstall.php`, PHPCS + wp-env configured in CI

**Addresses:** Admin field configuration UI, admin recipient email config, Docker dev environment

**Avoids:** Missing nonce/sanitization (Settings API handles admin forms), missing i18n (enforce PHPCS from first commit), missing prefix (establish naming convention now), deactivation/uninstall hook confusion (set up correctly once)

### Phase 2: PDF Engine

**Rationale:** PDF generation is the core product and the highest-complexity component. It has no WordPress runtime dependency (pure PHP data-in, bytes-out) which makes it the most testable component in isolation — unit tests with Brain Monkey can cover the full rendering path without Docker. Doing it before email means the temp-file lifecycle and HTML template are validated before Mailer depends on them. The DOMPDF security configuration (`$isRemoteEnabled = false`, namespace prefixing via Strauss) must be locked in this phase before any template work proceeds.

**Delivers:** `PDFGenerator`, `TemplateRenderer`, default HTML/CSS PDF template, namespace-prefixed vendor bundle (Strauss), unit tests covering template rendering and temp file lifecycle, CI memory-limit test (simulated 64 MB environment)

**Uses:** dompdf 3.1.x, `wp_tempnam()` / `sys_get_temp_dir()`, Strauss for namespace prefixing

**Implements:** PDFGenerator and TemplateRenderer (Architecture Layer 1)

**Avoids:** DOMPDF remote access RCE, PDF stored in uploads (use temp path), PDF memory exhaustion (test against 64 MB limit), large PDF triggering spam filters (target < 500 KB)

### Phase 3: Notifications (Email and Webhook)

**Rationale:** Mailer and WebhookDispatcher both depend on `SettingsManager` (Phase 1) and PDFGenerator producing a temp file path (Phase 2). Email is a table-stakes feature; webhook is a differentiator. They share the same submission data shape, so implementing both in the same phase is efficient. This phase also delivers the "Send test email" admin button — a critical operational feature that admins need before going live.

**Delivers:** `Mailer` (member email + admin email with PDF attachment, temp file cleanup), `WebhookDispatcher` (JSON POST via `wp_remote_post()`, optional HMAC signature), "Send test email" button in admin UI, SMTP documentation in user guide

**Uses:** `wp_mail()`, `wp_remote_post()`, `wp_schedule_single_event()` for async webhook dispatch

**Implements:** Mailer and WebhookDispatcher (Architecture Layer 2)

**Avoids:** Email delivery failure (document SMTP dependency, provide test button), PDF attachment URL vs filesystem path confusion, synchronous webhook blocking form response (schedule via WP cron)

### Phase 4: Frontend Form and Admin UX Polish

**Rationale:** `FormRenderer` and `FormHandler` depend on `SettingsManager` for field schema (Phase 1) and fire the hook consumed by the full notification pipeline (Phases 2-3). Building the form last means it can be tested end-to-end immediately — a form submission in Phase 4 exercises the entire stack. Admin UX polish (plain-language labels, inline help text, activation notice, success message UX) is grouped here because it requires the complete settings page to exist and should be validated with a non-technical user before release.

**Delivers:** `FormRenderer` (shortcode, Alpine.js form validation, conditional honeypot), `FormHandler` (AJAX endpoint, nonce verification, sanitization, validation, `do_action` trigger), success message with JS scroll, activation admin notice, complete user guide, end-to-end integration test (form submit → PDF → email)

**Uses:** Alpine.js 3.15.x (enqueued locally, no CDN), `wp_nonce_field()` / `check_ajax_referer()`, `sanitize_text_field()` / `sanitize_email()` / `esc_html()`

**Implements:** FormRenderer and FormHandler (Architecture Layers 3-4), AdminPage usability improvements (Layer 5)

**Avoids:** Missing nonce on AJAX handler (built in from first line), unsanitized form values in PDF template, no success message UX, admin UI overwhelming non-technical users (usability test before phase is closed)

### Phase Ordering Rationale

- **Dependencies are strictly bottom-up:** SettingsManager (Phase 1) → PDFGenerator (Phase 2) → Mailer (Phase 3) → FormRenderer/FormHandler (Phase 4). Building in this order means each phase is immediately testable against real dependencies, not stubs.
- **Security requirements front-loaded:** The four "never acceptable" technical debt items (hardcoded strings, DOMPDF remote access, PDFs in uploads, missing nonces) each map to a phase where they are addressed at construction time, not retrofitted.
- **PDF engine isolated:** Phase 2 is the highest-risk phase (DOMPDF security, memory, file size). Isolating it from email and form concerns allows focused testing and security review before those systems depend on it.
- **Form last enables full-stack testing immediately:** A Phase 4 form submission exercises all three preceding layers simultaneously, making end-to-end integration tests meaningful from the moment the form exists.

### Research Flags

Phases likely needing deeper research during planning:

- **Phase 1 (Settings):** The field schema editor requires a repeatable field list in the admin UI. The WordPress Settings API does not natively support repeater fields — this will need a custom approach. Research during planning should confirm the simplest pattern (e.g., serialized JSON in a single option field, managed via custom JS in the admin).
- **Phase 2 (PDF Engine):** Strauss (namespace prefixing tool) configuration for dompdf requires verification against the specific version bundle. PHP-Scoper is the alternative; the choice should be confirmed during planning based on CI compatibility.
- **Phase 4 (Frontend Form):** Alpine.js integration with WordPress's `wp_enqueue_script()` and the AJAX endpoint pattern (`admin-ajax.php` vs REST API) should be confirmed against current best practices before implementation begins.

Phases with standard, well-documented patterns (research-phase can be skipped):

- **Phase 3 (Notifications):** `wp_mail()` with attachments and `wp_remote_post()` are thoroughly documented in WordPress core. The patterns are standard and the research files provide sufficient implementation detail.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All library versions confirmed against Packagist, npm, and official WordPress docs as of March 2026; alternatives documented with clear rationale |
| Features | HIGH (core), MEDIUM (differentiators) | Table-stakes features verified against competitor analysis and common membership plugin patterns; differentiator prioritization based on ecosystem analysis, not user interviews |
| Architecture | HIGH | Patterns sourced from official WordPress Plugin Handbook, documented anti-patterns with cited real-world examples, two-tier testing strategy confirmed from multiple 2025 sources |
| Pitfalls | HIGH (security/WordPress.org), MEDIUM (email deliverability) | Security pitfalls sourced from official WordPress.org review criteria and CVE documentation; email deliverability patterns sourced from multiple providers but actual delivery rates depend on hosting environment |

**Overall confidence:** HIGH

### Gaps to Address

- **Repeater field UI pattern for settings:** The field schema editor needs a dynamic repeater (add/remove/reorder rows) that Settings API does not natively support. The implementation approach (custom JS + serialized option) should be confirmed during Phase 1 planning to avoid mid-phase pivots.
- **Strauss vs PHP-Scoper for vendor prefixing:** Both tools achieve namespace prefixing; Strauss is simpler to configure for WordPress plugins. Confirm tool choice and CI integration before Phase 2 starts to avoid a late-phase refactor of the distribution build.
- **SMTP dependency framing:** The plugin cannot configure SMTP itself, but the strength of the admin-notice guidance and user guide language will determine whether admins succeed on first install. The exact wording and which free SMTP plugins to recommend should be decided during Phase 3 planning with the pilot club admin in mind.
- **PDF template configurability scope:** "Admin-configurable PDF template layout" is a high-complexity differentiator. The research does not resolve whether this means a visual drag-and-drop layout builder (too complex for v1) or a simpler field-order + branding (logo, color, club name) configuration. The Phase 2 planning should bound this explicitly before implementation.

## Sources

### Primary (HIGH confidence)
- Packagist — dompdf/dompdf: https://packagist.org/packages/dompdf/dompdf (v3.1.5, March 2026)
- Packagist — wp-coding-standards/wpcs: https://packagist.org/packages/wp-coding-standards/wpcs (v3.3.0)
- npm — @wordpress/env: https://www.npmjs.com/package/@wordpress/env (v11.2.0)
- Alpine.js GitHub: https://github.com/alpinejs/alpine/releases (v3.15.8)
- WordPress Developer Docs — wp_mail(): https://developer.wordpress.org/reference/functions/wp_mail/
- WordPress Developer Docs — Settings API: https://developer.wordpress.org/plugins/settings/settings-api/
- WordPress Developer Docs — Nonces: https://developer.wordpress.org/apis/security/nonces/
- WordPress Plugin Handbook — Common Issues: https://developer.wordpress.org/plugins/wordpress-org/common-issues/
- WordPress Plugin Review Checklist: https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/
- WordPress Plugin Best Practices: https://developer.wordpress.org/plugins/plugin-basics/best-practices/
- WordPress Uninstall Methods: https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
- DOMPDF RCE — Snyk (CVE-2022-28368): https://snyk.io/blog/security-alert-php-pdf-library-dompdf-rce/
- WordPress Internationalization Handbook: https://developer.wordpress.org/plugins/internationalization/

### Secondary (MEDIUM confidence)
- Nate Weller — Unit Testing WordPress Plugins in 2025: https://blog.nateweller.com/2025/05/09/unit-testing-wordpress-plugins-in-2025-with-wordpress-env-and-phpunit/
- juanma.codes — Setting up Unit and Integration Testing (2025): https://juanma.codes/2025/08/12/setting-up-unit-and-integration-testing-for-wordpress-plugins/
- BuddyX Theme — Building Advanced WordPress Plugins (2025): https://buddyxtheme.com/building-advanced-wordpress-plugins-oop-namespaces-autoloading-and-modern-architecture/
- Gary Jones — The Pitfalls of Adding WordPress Hooks to a Class Constructor: https://garyjones.io/constructor-hooks
- peterdev.pl — PHP PDF library comparison 2025: https://peterdev.pl/picking-a-php-tool-to-generate-pdfs/
- WebToffee — dompdf vs mPDF: https://www.webtoffee.com/info-library/accounting-invoicing/dompdf-vs-mpdf/
- WP Mail SMTP — WordPress Emails Going to Spam: https://wpmailsmtp.com/wordpress-emails-going-to-spam/
- 10up — wp_options Autoload Performance: https://10up.com/blog/2017/wp-options-table/
- Gravity PDF: https://gravitypdf.com/
- WPBeginner — Best WordPress Membership Plugins 2026: https://www.wpbeginner.com/plugins/5-best-wordpress-membership-plugins-compared/

---
*Research completed: 2026-03-20*
*Ready for roadmap: yes*
