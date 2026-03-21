---
phase: 02-pdf-engine
plan: "02"
subsystem: ui
tags: [wordpress, settings-api, phpcs, wpcs, php]

# Dependency graph
requires:
  - phase: 02-01
    provides: DOMPDF installed and Strauss-scoped; PHPUnit + Brain Monkey configured
provides:
  - sanitize_pdf_branding() returns 7-key array (club_name, logo_url, accent_color, document_title, gdpr_text, footer_text, page2_content)
  - register_setting() default array includes all 7 keys
  - Four new form inputs in PDF Branding settings tab (Document Title, GDPR Text, Footer Text, Page 2 Content)
affects:
  - 02-03 (PdfGenerator reads wmr_pdf_branding option — all 7 keys now available)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - wp_kses_post() for HTML-capable textarea content in sanitize callbacks and template output
    - sanitize_text_field() for plain-text inputs
    - esc_attr() for text input value= attributes, wp_kses_post() for textarea inner content

key-files:
  created: []
  modified:
    - src/Admin/SettingsRegistrar.php
    - templates/admin-settings-page.php

key-decisions:
  - "wp_kses_post() used for gdpr_text, footer_text, page2_content — these fields accept HTML links/formatting for legal text"
  - "sanitize_text_field() used for document_title — plain text, no HTML expected"
  - "Pre-existing PHPCS violations in FieldSchema.php and wp-membership-registration.php deferred — out of scope for this plan"

patterns-established:
  - "HTML-capable textarea fields: wp_kses_post() in sanitize callback AND in template echo"
  - "Plain-text inputs: sanitize_text_field() in sanitize callback, esc_attr() in template value="

requirements-completed: [PDF-02]

# Metrics
duration: 3min
completed: 2026-03-21
---

# Phase 2 Plan 02: PDF Branding Settings Extension Summary

**wmr_pdf_branding option extended to 7 keys with document_title, gdpr_text, footer_text, page2_content — Settings API registered, sanitized, and rendered as form inputs in the PDF Branding tab**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-21T14:35:22Z
- **Completed:** 2026-03-21T14:38:40Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- Extended `sanitize_pdf_branding()` return array from 3 keys to 7 keys with correct sanitization per field type
- Updated `register_setting()` default array to include all 7 keys so `get_option('wmr_pdf_branding')` returns complete structure on first install
- Added four new form rows to the PDF Branding tab: Document Title (text input), GDPR/Consent Text, Footer Text, Page 2 Content (all textareas)
- Both modified files pass PHPCS/WPCS with zero violations

## Task Commits

Each task was committed atomically:

1. **Task 1: Extend sanitize_pdf_branding() with four new sub-keys** - `6f86778` (feat)
2. **Task 2: Add four new form rows to the PDF Branding settings tab template** - `9ac6266` (feat)

## Files Created/Modified

- `src/Admin/SettingsRegistrar.php` - sanitize_pdf_branding() extended to 7 keys; PHPDoc updated; register_setting() default extended
- `templates/admin-settings-page.php` - Four new `<tr>` rows appended inside PDF Branding tab tbody

## Decisions Made

- `wp_kses_post()` chosen for `gdpr_text`, `footer_text`, `page2_content` because these fields contain legal HTML text (links, bold formatting). Using `sanitize_text_field()` would strip valid HTML.
- `sanitize_text_field()` chosen for `document_title` — plain text like "Aufnahmeantrag", no HTML expected.
- `wp_kses_post()` used in template echo for textarea content (not `esc_textarea()`) to match the sanitizer — outputting already-kses-filtered HTML safely.

## Deviations from Plan

None - plan executed exactly as written.

**Note (out-of-scope):** Pre-existing PHPCS violations found in `src/Util/FieldSchema.php` (Yoda condition) and `wp-membership-registration.php` (missing @package, parenthesis style) — these predate this plan and are not caused by changes here. Logged to deferred-items.

## Issues Encountered

- `composer phpcs` could not run locally (no local PHP/Composer). Used `docker run php:8.2-cli` instead, which required pulling the image on first run. Dev dependencies installed via `docker run composer:latest install`.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- `get_option('wmr_pdf_branding')` now returns all 7 keys PdfGenerator (Plan 03) needs: `document_title`, `gdpr_text`, `footer_text`, `page2_content` alongside the existing `club_name`, `logo_url`, `accent_color`.
- Plan 03 can read these values directly without any further settings work.

---
*Phase: 02-pdf-engine*
*Completed: 2026-03-21*
