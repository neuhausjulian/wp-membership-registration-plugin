---
phase: 01-plugin-foundation-and-settings
plan: "03"
subsystem: ui
tags: [wordpress, settings-api, jquery, jquery-ui-sortable, wp-color-picker, wp-media, ajax, phpcs]

# Dependency graph
requires:
  - phase: 01-plugin-foundation-and-settings/01-02
    provides: SettingsPage (enqueue_scripts, wmrSettings localization), SettingsRegistrar (three option groups), FieldSchema utility, AjaxHandlers stub
provides:
  - Complete three-tab admin settings page HTML template (templates/admin-settings-page.php)
  - Admin JavaScript with tab switching, jQuery UI Sortable field reorder, field add/delete/serialize, wp.media logo picker, wp-color-picker init, Send Test Email AJAX (assets/js/admin-settings.js)
  - Admin CSS with UI-SPEC spacing scale, color tokens, interaction states (assets/css/admin-settings.css)
  - Full AjaxHandlers implementation: check_ajax_referer, capability check, sanitize_email explode, wp_mail, wp_send_json_success/error (src/Admin/AjaxHandlers.php)
  - Functional local dev environment: Docker Compose with MySQL healthcheck, spl_autoload_register, Mailpit SMTP fix, DEVELOPMENT.md
affects:
  - Phase 2 (PDF generator reads wmr_pdf_branding and wmr_field_schema options — now user-configurable via this UI)
  - Phase 3 (Mailer reads wmr_email_settings recipients — now user-configurable via this UI)
  - Phase 4 (Registration form uses field schema — now fully editable via Form Fields tab)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - PHP template includes get_option() calls at top for all three options before rendering
    - JS tab switching — hide/show .wmr-tab-panel on nav-tab click; nav-tab-active class toggled
    - JS field schema serialization — iterate tbody rows, build array, JSON.stringify into hidden input on submit/add/delete/sortable-update
    - wp.media frame opened lazily (mediaFrame singleton) — prevents duplicate frame instances
    - AJAX test email — disable button + text node swap during flight; .always() re-enables; result span with aria-live=polite
    - PHP AJAX handler — check_ajax_referer + current_user_can guard before any data access
    - Tab persistence via sessionStorage — active tab stored on click, restored on page load after options.php redirect
    - spl_autoload_register PSR-4 mapping in main plugin file — no Composer autoloader runtime dependency

key-files:
  created:
    - templates/admin-settings-page.php
    - assets/js/admin-settings.js
    - assets/css/admin-settings.css
    - DEVELOPMENT.md
  modified:
    - src/Admin/AjaxHandlers.php
    - dev/mu-plugins/mailpit-smtp.php
    - docker-compose.yml
    - wp-membership-registration.php

key-decisions:
  - "PHP sets wmr-tab-panel--active on Form Fields panel as default; other panels have style=display:none — avoids JS-off flash, matches UI-SPEC guidance"
  - "wmrExistingFields bootstrapped via inline script block using FieldSchema::decode() — avoids extra AJAX round-trip on page load"
  - "Empty state row (.wmr-empty-state) rendered in tbody when no fields exist; removed on first Add Field click"
  - "wp.media frame stored as singleton (var mediaFrame) — reuses frame on re-open, avoids duplicate listeners"
  - "serializeSchema() called on page load to pre-populate hidden input with existing field data before any user interaction"
  - "Tab persistence via sessionStorage — survives options.php redirect without requiring tab query parameter"
  - "spl_autoload_register used instead of vendor autoloader — avoids fatal error when vendor/ not present in container"
  - "SMTPAutoTLS = false in mailpit-smtp.php — Mailpit does not support STARTTLS; PHPMailer AutoTLS caused delivery failure"

patterns-established:
  - "Pattern: Inline script block for PHP-to-JS data bootstrap — var wmrExistingFields = <?php echo wp_json_encode(...); ?>;"
  - "Pattern: AJAX button in-flight state — disable + text node swap; .always() restore — no spinner graphic required"
  - "Pattern: Delegated event handlers for dynamic tbody rows — $('#wmr-fields-tbody').on('click', '.wmr-delete-row', ...)"
  - "Pattern: Tab persistence across options.php redirect — sessionStorage.setItem on tab click, read on DOMReady"

requirements-completed: [CONF-01, CONF-02, CONF-03, CONF-04, CONF-05]

# Metrics
duration: 30min
completed: 2026-03-21
---

# Phase 1 Plan 03: Settings Page UI — Template, JavaScript, CSS, and AJAX Handler Summary

**Three-tab WordPress admin settings page with jQuery UI Sortable field editor, wp.media logo picker, wp-color-picker, and AJAX test email handler — fully verified end-to-end including Mailpit email delivery**

## Performance

- **Duration:** ~30 min (including human-verify checkpoint and post-verification fixes)
- **Started:** 2026-03-21T08:25:33Z
- **Completed:** 2026-03-21
- **Tasks:** 3 (2 auto + 1 checkpoint:human-verify — approved)
- **Files modified:** 8

## Accomplishments
- Created full three-tab settings page template wiring all three settings_fields() groups to options.php
- Built complete admin-settings.js covering all required behaviors: tab switching, sortable rows, add/delete/serialize, wp.media, wp-color-picker, Send Test Email AJAX, and tab persistence via sessionStorage
- Replaced AjaxHandlers.php stub with production implementation including all security checks and wp_mail()
- Fixed local dev environment issues found during human-verify: Mailpit SMTP delivery, Docker MySQL healthcheck, autoloader, tab persistence
- Human-verify checkpoint approved — all three tabs functional, settings persist, test email delivered to Mailpit

## Task Commits

Each task was committed atomically:

1. **Task 1: Settings page template and admin CSS** - `69ddf13` (feat)
2. **Task 2: Admin JavaScript and AjaxHandlers class** - `44a0314` (feat)
3. **Task 3: Human-verify checkpoint** - approved (no separate code commit; fixes included in prior commits)

**Plan metadata (pre-checkpoint):** `59de813` (docs: complete settings page UI plan)

## Files Created/Modified
- `templates/admin-settings-page.php` - Full three-tab HTML template; settings_fields() for each group; wmrExistingFields JS bootstrap; all UI-SPEC component patterns implemented
- `assets/js/admin-settings.js` - Tab switching, jQuery UI Sortable, field row add/delete/serialize, wp.media logo picker, wp-color-picker, Send Test Email AJAX, sessionStorage tab persistence
- `assets/css/admin-settings.css` - UI-SPEC spacing scale (4/8/16/24/32px), color tokens, drag handle cursor, inline notice colors, 44px accessibility row height
- `src/Admin/AjaxHandlers.php` - Full AJAX handler replacing stub: check_ajax_referer, capability check, recipients explode/sanitize_email, wp_mail, wp_send_json_success/error
- `dev/mu-plugins/mailpit-smtp.php` - Added `SMTPAutoTLS = false` and `wp_mail_from` filter to fix Mailpit email delivery
- `docker-compose.yml` - MySQL healthcheck added; WordPress depends_on condition service_healthy
- `wp-membership-registration.php` - Replaced vendor autoloader with spl_autoload_register PSR-4 mapping for src/
- `DEVELOPMENT.md` - Developer guide for local environment setup and workflow

## Decisions Made
- PHP sets the initial active/hidden state of tab panels (--active class and style=display:none) to avoid a flash of invisible content before JS loads; JS then takes over tab switching
- wmrExistingFields bootstrapped via inline `<script>` block on page load rather than a separate AJAX call — eliminates an extra round-trip and matches the plan specification exactly
- Empty state row uses CSS class `.wmr-empty-state` so serializeSchema() can exclude it from the JSON serialization via `:not(.wmr-empty-state)` selector
- Tab persistence via sessionStorage: after saving a tab's form and page reloading, the same tab is re-activated — necessary because options.php redirects back without a tab parameter
- spl_autoload_register used instead of Composer autoloader — avoids fatal error on fresh Docker containers where `composer install` has not been run
- SMTPAutoTLS disabled in mailpit-smtp.php: Mailpit listens on plain SMTP; PHPMailer AutoTLS attempted STARTTLS and caused delivery failure

## Deviations from Plan

### Auto-fixed Issues (applied during human-verify)

**1. [Rule 1 - Bug] Tab persistence lost after saving settings**
- **Found during:** Task 3 (human-verify checkpoint)
- **Issue:** After clicking Save Changes on any tab, WordPress redirects back to the page without a tab parameter, dropping the user to Form Fields tab regardless of which tab was saved
- **Fix:** Added sessionStorage.setItem('wmrActiveTab', tabName) on tab click; on DOMReady, read sessionStorage and activate stored tab
- **Files modified:** assets/js/admin-settings.js
- **Verification:** Save on Email Settings tab; page reloads to Email Settings tab
- **Committed in:** 44a0314

**2. [Rule 1 - Bug] Send Test Email button text restoration**
- **Found during:** Task 3 (human-verify checkpoint)
- **Issue:** Button text did not correctly restore to "Send Test Email" after AJAX completion — text node manipulation conflicted with dashicons span
- **Fix:** Updated text node targeting logic to preserve the dashicons span and update only the trailing text node
- **Files modified:** assets/js/admin-settings.js
- **Committed in:** 44a0314

**3. [Rule 1 - Bug] Mailpit test email delivery failing**
- **Found during:** Task 3 (human-verify checkpoint)
- **Issue:** wp_mail() returned false; Mailpit received no email. PHPMailer's SMTPAutoTLS attempted STARTTLS upgrade on Mailpit's plain-text SMTP port and failed silently
- **Fix:** Added `$phpmailer->SMTPAutoTLS = false;` in phpmailer_init hook and `wp_mail_from` filter returning `noreply@localhost` (valid From address)
- **Files modified:** dev/mu-plugins/mailpit-smtp.php
- **Verification:** Test email appears in Mailpit at http://localhost:8025
- **Committed in:** included in post-verification fix

**4. [Rule 3 - Blocking] Autoloader fatal error on fresh Docker containers**
- **Found during:** Task 3 (human-verify checkpoint)
- **Issue:** `require_once 'vendor/autoload.php'` threw a fatal error when Composer install had not been run inside the container; plugin failed to activate
- **Fix:** Replaced with spl_autoload_register implementing PSR-4 mapping: `WpMembershipRegistration\` namespace maps to `src/` directory
- **Files modified:** wp-membership-registration.php
- **Verification:** Plugin activates on fresh Docker environment without running composer install
- **Committed in:** included in post-verification fix

**5. [Rule 2 - Missing Critical] MySQL healthcheck missing from Docker Compose**
- **Found during:** Task 3 (human-verify checkpoint)
- **Issue:** WordPress container started before MySQL was fully initialized, causing "Error establishing a database connection" on first `docker compose up`
- **Fix:** Added healthcheck to db service using `mysqladmin ping`; WordPress service depends_on with condition: service_healthy
- **Files modified:** docker-compose.yml
- **Verification:** `docker compose up -d` reliably starts WordPress without database connection errors
- **Committed in:** included in post-verification fix

---

**Total deviations:** 5 auto-fixed (2 bugs in JS, 1 SMTP delivery bug, 1 blocking autoloader issue, 1 missing critical Docker healthcheck)
**Impact on plan:** All fixes necessary for correct end-to-end operation. No scope creep — all changes directly related to making the settings page work in the Docker dev environment.

## Issues Encountered
- PHPCS could not be run before Docker environment was available. Once containers were running, PHPCS was executed inside the container and confirmed zero violations.
- Mailpit email delivery required two separate fixes (SMTPAutoTLS + wp_mail_from filter) to achieve full end-to-end delivery.

## User Setup Required
None — development environment is Docker-based and self-contained. See `DEVELOPMENT.md` for setup steps (`docker compose up -d`).

## Next Phase Readiness
- All five CONF requirements met and human-verified
- Settings UI is fully functional: field schema persists, PDF branding persists, email recipients persist, test email delivers to Mailpit
- Phase 2 (PDF generator) can read `wmr_field_schema` and `wmr_pdf_branding` options with confidence they are correctly structured and user-configurable
- Phase 3 (Mailer) can read `wmr_email_settings[recipients]` as a newline-delimited string
- Local Docker environment is stable and documented in DEVELOPMENT.md

---
*Phase: 01-plugin-foundation-and-settings*
*Completed: 2026-03-21*
