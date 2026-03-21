# WP Membership Registration Plugin

WordPress plugin for digital membership registration with PDF generation, email delivery, and webhook support.

## Requirements

- PHP 8.0+
- WordPress 6.0+
- Docker + Docker Compose (for local development)
- Composer 2.x
- Node.js 20+ (optional, only needed for `@wordpress/env` if used instead of Docker Compose)

---

## Local Development Setup

### 1. Install PHP dependencies

```bash
composer install
```

This also runs Strauss automatically (via `post-install-cmd`) to scope DOMPDF under the plugin namespace. The `vendor-prefixed/` directory is generated — do not edit it manually.

### 2. Start the Docker environment

```bash
docker compose up -d
```

Services:

| Service    | URL / Port                      | Purpose                  |
|------------|---------------------------------|--------------------------|
| WordPress  | http://localhost:8080           | Site + WP Admin          |
| Mailpit    | http://localhost:8025           | Catch-all email viewer   |
| Adminer    | http://localhost:8081           | Database GUI             |
| MySQL      | port 3306 (internal)            | Database                 |

The plugin directory is mounted at `wp-content/plugins/wp-membership-registration`.

### 3. First-time WordPress setup

Visit http://localhost:8080 and complete the 5-minute WordPress install.

Then activate the plugin: **WP Admin → Plugins → WP Membership Registration → Activate**

---

## Common Dev Tasks

### Run code style checks (PHPCS/WPCS)

```bash
composer phpcs
```

### Run unit tests (PHPUnit)

```bash
composer phpunit
# or
./vendor/bin/phpunit --testsuite unit
```

### Stop / reset Docker environment

```bash
docker compose down        # stop (data preserved)
docker compose down -v     # stop + wipe database
```

---

## Testing Shortcodes

To test a shortcode (e.g. `[membership_form download="blank"]`) in WordPress:

1. Go to **WP Admin → Pages → Add New**
2. Give the page a title (e.g. "Test")
3. In the Gutenberg editor, click **+** → search for **Shortcode** → select the Shortcode block
4. Paste the shortcode into the block (e.g. `[membership_form download="blank"]`)
5. Click **Publish**, then **View Page**

The shortcode output will render on the front end.

---

## Testing the Blank PDF Download

After setting up the plugin and adding at least one form field:

```bash
curl -v "http://localhost:8080/wp-admin/admin-ajax.php?action=wmr_download_blank_pdf" \
  -o /tmp/test-blank.pdf
```

Expected: HTTP 200, `Content-Type: application/pdf`, non-empty file. Open `/tmp/test-blank.pdf` to verify it renders correctly.

Confirm no temp files are left behind:

```bash
docker compose exec wordpress find /tmp -name "wmr-*.pdf" 2>/dev/null
# Expected: no output
```

---

## Checking Emails (Mailpit)

All outgoing WordPress emails are captured by Mailpit during development. Open http://localhost:8025 to view them — no emails leave the local environment.

---

## Key Technical Decisions

- **DOMPDF** is scoped via Strauss (`vendor-prefixed/`) to avoid conflicts with other plugins. Never relax `isRemoteEnabled = false`.
- **PDFs are never stored permanently** — always write to `sys_get_temp_dir()` and `unlink()` immediately after use. Never write to `wp-content/uploads/`.
- **Field schema** is stored as a JSON-encoded string in the `wmr_field_schema` option (not PHP-serialized), safe for both PHP and JS.
- **SMTP relay** uses a mu-plugin (`dev/mu-plugins/mailpit-smtp.php`) with `SMTPAutoTLS = false` — Mailpit does not support STARTTLS.

---

## Plugin Structure

```
src/
  Admin/          # Settings pages, AJAX handlers
  Pdf/            # PdfGenerator class
  Shortcodes/     # MembershipFormShortcode
templates/
  admin-settings-page.php
  pdf/
    membership-form.php   # HTML template rendered by DOMPDF
tests/
  Unit/
    Pdf/          # PdfGeneratorTest
dev/
  mu-plugins/     # Mailpit SMTP relay (local only)
vendor-prefixed/  # Strauss-scoped DOMPDF (generated, do not edit)
```
