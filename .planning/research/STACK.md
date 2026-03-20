# Stack Research

**Domain:** WordPress plugin — membership registration with PDF generation, email delivery, and webhook support
**Researched:** 2026-03-20
**Confidence:** HIGH (core stack verified against Packagist, npm, and official WordPress docs)

---

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| PHP | 8.0+ | Plugin runtime | Project constraint; PHP 8 gives named arguments, union types, and nullsafe operator — materially reduces boilerplate in WordPress plugin code |
| WordPress | 6.0+ | Host platform / plugin APIs | Project constraint; 6.0 introduced full-site editing but, more importantly, it ships with a stable Settings API, `wp_mail()`, `WP_HTTP` stack, and REST API — all needed here |
| Composer | 2.9.x | PHP dependency management + PSR-4 autoloading | Required to pull in dompdf and wire up namespaced plugin classes without manual `require` chains; v2 is dramatically faster than v1 and is the current stable (v2.9.5 as of March 2026) |
| dompdf/dompdf | 3.1.x | HTML-to-PDF generation | Best-in-class for simple HTML template → PDF on PHP with no external binary; v3.1.5 released March 2026; 168M+ Packagist installs; pure-PHP, no wkhtmltopdf/Chromium dependency; WordPress-safe |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Alpine.js | 3.15.x | Frontend form interactivity (field show/hide, validation UX) | Enqueue via `wp_enqueue_script()` from local copy (not CDN) for offline-capable dev; no build step required; ~15 KB gzipped; perfect fit for a shortcode-rendered form that needs conditional logic without React overhead |
| wp-coding-standards/wpcs | 3.3.x | PHP code style enforcement (PHPCS ruleset) | Add as `require-dev`; use `WordPress` ruleset; catches escaping, sanitization, and nonce violations at lint-time before they become security bugs |
| yoast/phpunit-polyfills | 2.x | PHPUnit cross-version compatibility shim | Required when using `wp-env` + PHPUnit on PHP 8; WordPress core test bootstrap expects this library; add as `require-dev` |

### WordPress Core APIs (no external library needed)

| API / Function | Purpose | Notes |
|----------------|---------|-------|
| Settings API (`add_settings_section`, `register_setting`, `add_settings_field`) | Admin configuration pages for form fields, PDF template, email addresses | Use instead of hand-rolling `$_POST` forms; handles nonces, sanitization hooks, and WP admin styling automatically |
| `wp_mail()` | Send emails with PDF attachments to member and admin | Wraps PHPMailer; accepts `$attachments` as array of filesystem paths; generate PDF to temp file first with `wp_tempnam()`, attach, then `unlink()` after send |
| `wp_tempnam()` + `get_temp_dir()` | Temporary file for generated PDF before email attachment | WordPress-idiomatic way to get a writable tmp path; caller must `unlink()` after use |
| `wp_remote_post()` | Fire outgoing webhook (HTTP POST with JSON body) | WP HTTP API; handles redirects, timeouts, SSL; no curl dependency required; use `wp_json_encode()` for body |
| `add_shortcode()` | Embed registration form in any page or post | Project decision: shortcode over Gutenberg block for v1; works in Classic Editor, Elementor, Divi, page builders universally |
| `wp_nonce_field()` + `check_admin_referer()` / `wp_verify_nonce()` | CSRF protection on admin settings and frontend form submissions | Mandatory; Settings API handles admin forms automatically; frontend form POST needs manual nonce check |
| `sanitize_text_field()`, `sanitize_email()`, `esc_html()`, `esc_attr()` | Input sanitization and output escaping | Use at every boundary — never trust `$_POST` directly |
| REST API (`register_rest_route`) | Optional: expose form submission endpoint for JavaScript fetch | Only needed if you switch from traditional `wp-admin/admin-post.php` POST to a JSON API approach; traditional POST is simpler for v1 |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `@wordpress/env` (wp-env) | Docker-based local WordPress environment | Official WordPress tooling; zero-config default; `.wp-env.json` for PHP version pin and plugin mount; bundles WP-CLI and PHPUnit; install globally: `npm i -g @wordpress/env` (v11.2.0 current) |
| Docker + Docker Compose | Container runtime for wp-env | wp-env manages Compose internally; only need Docker Desktop / Docker Engine installed on host |
| WP-CLI (via wp-env) | Database seeding, plugin activation, test scaffolding | Available inside wp-env container via `wp-env run cli wp ...` |
| PHPUnit | PHP unit + integration tests | wp-env ships a `tests-cli` environment with PHPUnit wired to WordPress test suite; run via `wp-env run tests-cli vendor/bin/phpunit` |

---

## Installation

```bash
# 1. Initialize Composer (run from plugin root)
composer init

# 2. Add runtime dependency
composer require dompdf/dompdf:^3.1

# 3. Add dev dependencies
composer require --dev \
  wp-coding-standards/wpcs:^3.3 \
  phpcsstandards/phpcsextra:^1.5 \
  squizlabs/php_codesniffer:^3.13 \
  phpunit/phpunit:^9.0 \
  yoast/phpunit-polyfills:^2.0

# 4. Wire up PSR-4 autoload in composer.json:
#    "autoload": { "psr-4": { "WpMemberReg\\": "src/" } }
composer dump-autoload -o

# 5. Install wp-env globally (requires Node 20+ and Docker)
npm install -g @wordpress/env

# 6. Alpine.js — download and vendor locally (do NOT use CDN in production plugin)
# Download from: https://cdn.jsdelivr.net/npm/alpinejs@3.15.8/dist/cdn.min.js
# Save to: assets/js/alpine.min.js
# Enqueue in plugin with wp_enqueue_script()
```

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| PDF generation | dompdf 3.x | mPDF 8.x | mPDF is better for multilingual/RTL documents but requires `mbstring` + `gd` extensions and has had table rendering issues in comparisons; overkill for a single-language membership form |
| PDF generation | dompdf 3.x | TCPDF | TCPDF is in support-only mode; its successor `tc-lib-pdf` is unfinished; programmatic API is verbose for HTML-template workflows |
| PDF generation | dompdf 3.x | Headless Chrome (Puppeteer/WKHTMLtoPDF) | Requires a binary on the server; not available on shared hosting; breaks the "no external dependencies" constraint |
| JS form interactivity | Alpine.js 3.x | React (via `@wordpress/scripts`) | React requires a build step (Webpack/Babel), adds 40+ KB, and is designed for Gutenberg blocks; Alpine's HTML-attribute approach fits inline shortcode forms with no compilation |
| JS form interactivity | Alpine.js 3.x | Vanilla JS only | Viable but conditional field logic and client-side validation quickly become verbose in vanilla JS; Alpine adds minimal overhead for big readability wins |
| JS form interactivity | Alpine.js 3.x | Vue.js | Similar capabilities but heavier (larger bundle), requires build step for SFC format, and is less common in WordPress ecosystem |
| Dev environment | wp-env + Docker | Local by Flywheel / MAMP | GUI tools are not scriptable; cannot be committed to repo; Docker + wp-env is reproducible across machines and CI |
| Dev environment | wp-env + Docker | Custom `docker-compose.yml` (wordpress:latest image) | Custom Compose works but requires manual WP-CLI setup, PHPUnit wiring, and plugin mounting; wp-env does all of this with a single JSON config file |
| Email delivery | `wp_mail()` (native) | WP Mail SMTP plugin | A plugin dependency for core functionality is a bad pattern; `wp_mail()` is sufficient; site admins can configure SMTP separately with any SMTP plugin they choose |
| PHP dependencies | Composer + vendor bundled in repo | Manual `include`/`require` | Unmanageable at scale; no version pinning; no autoloading |
| Code style | PHPCS + WPCS ruleset | PHP-CS-Fixer | WPCS is the WordPress-native standard and is understood by WP.org reviewers; PHP-CS-Fixer does not include WordPress-specific sniffs (nonce checks, escaping, etc.) |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| AcroForm PDF filling (fpdi/setasign libraries) | Requires pre-built PDFs with named form fields; breaks when the PDF template is updated; out of scope per PROJECT.md decision | dompdf with an HTML template configured in WP settings |
| React for the shortcode form | Full React setup (`@wordpress/scripts`) requires Node build pipeline, output bundle, and is designed for Gutenberg context; total overkill for a form with ~10 fields | Alpine.js 3.x — no build step, HTML-native directives |
| jQuery for form interactivity | jQuery ships with WordPress but is global-namespace pollution; writing new jQuery code in 2025 adds a dependency pattern the ecosystem is moving away from | Alpine.js (or vanilla JS if the interactivity is truly minimal) |
| Calling `$_POST` directly without sanitization | XSS and injection vector; violates WordPress coding standards and WP.org review requirements | `sanitize_text_field()`, `sanitize_email()`, `wp_verify_nonce()` at the form handler boundary |
| Storing generated PDFs permanently in `wp-content/uploads` | Unnecessary disk growth; PDFs contain personal data (GDPR concern); email delivery is the delivery mechanism | Generate to `wp_tempnam()`, attach to email, `unlink()` immediately after |
| CDN-loaded Alpine.js in the distributed plugin | Creates external network dependency; violates WP.org guideline against phoning home to third-party servers | Bundle `alpine.min.js` in `assets/js/` and enqueue from local path |
| `wp_remote_post()` fired synchronously in the form submission handler on the critical path | If the webhook endpoint is slow, the user's form POST hangs | Fire webhook via a scheduled action (`wp_schedule_single_event` + custom hook) so it runs asynchronously after the response is sent |

---

## Stack Patterns by Variant

**If the club needs multilingual PDF output (non-Latin scripts, RTL):**
- Replace dompdf with mPDF 8.x
- Ensure server has `mbstring` and `gd` extensions (standard on most hosts)
- mPDF's CSS support is comparable; main benefit is CJK font and RTL direction handling

**If the club's WordPress host blocks outbound HTTP (webhooks):**
- Log the JSON payload to a custom database table instead
- Add an admin-accessible export (CSV/JSON download) as fallback
- `wp_remote_post()` failure handling via `is_wp_error()` check is required regardless

**If a Gutenberg block is added in v2:**
- Introduce `@wordpress/scripts` build tooling at that point
- Keep the shortcode as the v1 delivery mechanism; block can reuse the same PHP form handler

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| dompdf/dompdf ^3.1 | PHP 7.1 – 8.x | v3.1.5 tested; pure PHP, no extensions required beyond standard |
| mpdf/mpdf ^8.3 (alternative) | PHP 5.6 – 8.5 | Requires `mbstring` + `gd` extensions |
| wp-coding-standards/wpcs ^3.3 | PHP 7.2+, PHPCS ^3.13.4 | v3.3.0 released Nov 2025; use `WordPress` ruleset |
| Alpine.js ^3.15 | All modern browsers, IE11 NOT supported | v2.x supports IE11 if needed; v3 does not |
| @wordpress/env ^11.2 | Node 20+, Docker Engine 20+ | v11.2.0 current (March 2026); requires Docker Desktop on macOS/Windows |
| WordPress 6.0+ | PHP 8.0+ | Minimum per project constraints; test against latest stable (6.7.x as of March 2026) |

---

## Sources

- Packagist — dompdf/dompdf: https://packagist.org/packages/dompdf/dompdf (v3.1.5 confirmed, March 2026) — HIGH confidence
- Packagist — mpdf/mpdf: https://packagist.org/packages/mpdf/mpdf (v8.3.1 confirmed, March 2026) — HIGH confidence
- Packagist — wp-coding-standards/wpcs: https://packagist.org/packages/wp-coding-standards/wpcs (v3.3.0 confirmed) — HIGH confidence
- Alpine.js GitHub releases: https://github.com/alpinejs/alpine/releases (v3.15.8 confirmed) — HIGH confidence
- npm — @wordpress/env: https://www.npmjs.com/package/@wordpress/env (v11.2.0 confirmed) — HIGH confidence
- WordPress Developer Docs — `wp_mail()`: https://developer.wordpress.org/reference/functions/wp_mail/ — HIGH confidence
- WordPress Developer Docs — `wp_tempnam()`: https://developer.wordpress.org/reference/functions/wp_tempnam/ — HIGH confidence
- WordPress Developer Docs — Settings API: https://developer.wordpress.org/plugins/settings/settings-api/ — HIGH confidence
- WordPress Developer Docs — @wordpress/env: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/ — HIGH confidence
- peterdev.pl — PHP PDF library comparison 2025 update: https://peterdev.pl/picking-a-php-tool-to-generate-pdfs/ — MEDIUM confidence (independent benchmark)
- WebToffee — dompdf vs mPDF comparison: https://www.webtoffee.com/info-library/accounting-invoicing/dompdf-vs-mpdf/ — MEDIUM confidence
- WordPress Developer Blog — wp-env for local development: https://developer.wordpress.org/news/2023/03/quick-and-easy-local-wordpress-development-with-wp-env/ — HIGH confidence
- Alpine.js official docs — Installation: https://alpinejs.dev/essentials/installation — HIGH confidence
- Composer — Latest release (v2.9.5): https://getcomposer.org/download/ — HIGH confidence

---

*Stack research for: WordPress membership registration plugin with PDF generation and email delivery*
*Researched: 2026-03-20*
