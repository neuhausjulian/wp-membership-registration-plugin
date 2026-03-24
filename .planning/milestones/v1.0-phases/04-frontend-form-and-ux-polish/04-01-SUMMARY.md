---
phase: 04-frontend-form-and-ux-polish
plan: "01"
subsystem: pdf
tags: [tcpdf, dompdf, pdf, acroform, strauss, namespace-prefixing]

# Dependency graph
requires:
  - phase: 02-pdf-engine
    provides: PdfGenerator.php DOMPDF-based generate()/generate_blank() public API
provides:
  - TCPDF-based PdfGenerator with two-column Cell() layout and AcroForm blank PDFs
  - composer.json with tecnickcom/tcpdf replacing dompdf/dompdf
  - Strauss classmap prefix: WpMembershipRegistration_Vendor_TCPDF
affects:
  - 04-02-admin-settings (form_notes rename propagated from PdfGenerator)
  - 04-03-frontend-form (PDF generation unchanged API)

# Tech tracking
tech-stack:
  added: [tecnickcom/tcpdf ^6.11]
  patterns:
    - TCPDF uses classmap prefixing (no PHP namespace) — class is WpMembershipRegistration_Vendor_TCPDF not a namespaced path
    - Two-column Cell() layout with label_width=50mm, right-aligned label, bottom-border value cell
    - AcroForm TextField() for blank PDFs; static Cell() for filled (submitted data) PDFs
    - writeHTML() for TinyMCE-sourced HTML content (form_notes, page2_content)

key-files:
  created: []
  modified:
    - composer.json
    - composer.lock
    - bin/patch-vendor-prefixed.php
    - src/Pdf/PdfGenerator.php

key-decisions:
  - "TCPDF uses classmap (WpMembershipRegistration_Vendor_TCPDF) not PHP namespace — plan's use statement was incorrect; adapted to bare class alias"
  - "bin/patch-vendor-prefixed.php reduced to no-op stub — DOMPDF FontLib hard-coded string patches no longer needed for TCPDF"
  - "get_option('wmr_field_schema') return type guarded with is_string() before passing to FieldSchema::decode() — defensive against WP returning non-string"
  - "Short ternary ?: replaced with full ternary ? : throughout to satisfy WPCS sniff Squiz.PHP.DisallowShortOpenTag / Universal.Operators.DisallowShortTernary"

patterns-established:
  - "TCPDF classmap pattern: use WpMembershipRegistration_Vendor_TCPDF as PdfLib — no use statement for namespaced import"
  - "PDF layout: 50mm right-aligned label column, remaining width for content — all value cells start at same x position"

requirements-completed: [PDF-01, PDF-02, PDF-03]

# Metrics
duration: 4min
completed: 2026-03-22
---

# Phase 4 Plan 01: TCPDF Engine Replacement Summary

**TCPDF replaces DOMPDF in PdfGenerator.php — AcroForm blank PDFs via TextField(), two-column layout via Cell(), TinyMCE HTML via writeHTML()**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-22T21:51:24Z
- **Completed:** 2026-03-22T21:55:19Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments

- TCPDF 6.11.2 installed via composer; Strauss prefixes it as `WpMembershipRegistration_Vendor_TCPDF` (classmap, not namespace)
- PdfGenerator.php completely rewritten — zero DOMPDF references remain; public API (generate/generate_blank) unchanged
- Blank PDFs use real AcroForm TextField() interactive widgets; filled PDFs use two-column Cell() layout with 50mm label column
- form_notes (renamed from gdpr_text) and page2_content rendered via writeHTML() for TinyMCE HTML compatibility
- bin/patch-vendor-prefixed.php reduced to no-op stub — DOMPDF-specific FontLib patches removed

## Task Commits

1. **Task 1: Install TCPDF and update Strauss config** - `5ac2b17` (chore)
2. **Task 2: Rewrite PdfGenerator.php with TCPDF** - `7a050e0` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `composer.json` - Replace dompdf/dompdf with tecnickcom/tcpdf; update strauss.packages
- `composer.lock` - Lockfile updated (DOMPDF and its dependencies removed, TCPDF 6.11.2 added)
- `bin/patch-vendor-prefixed.php` - Reduced to no-op stub; DOMPDF FontLib patches no longer needed
- `src/Pdf/PdfGenerator.php` - Full rewrite using TCPDF: two-column layout, AcroForm, writeHTML()

## Decisions Made

- TCPDF uses classmap prefixing (not PHP namespace), so the plan's `use WpMembershipRegistration\Vendor\Tecnickcom\TCPDF\TCPDF as PdfLib` was incorrect. Actual class is `WpMembershipRegistration_Vendor_TCPDF`. Used bare class alias `use WpMembershipRegistration_Vendor_TCPDF as PdfLib`.
- Short ternary `?:` replaced with full ternary `? :` to satisfy WPCS (Universal.Operators.DisallowShortTernary sniff).
- `get_option('wmr_field_schema')` return value guarded with `is_string()` before passing to `FieldSchema::decode()` — WP could return false/mixed.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] TCPDF namespace path in use statement incorrect**
- **Found during:** Task 2 (Rewrite PdfGenerator.php with TCPDF)
- **Issue:** Plan specified `use WpMembershipRegistration\Vendor\Tecnickcom\TCPDF\TCPDF as PdfLib` but TCPDF uses classmap prefixing (no PHP namespace). Actual class name is `WpMembershipRegistration_Vendor_TCPDF`.
- **Fix:** Used `use WpMembershipRegistration_Vendor_TCPDF as PdfLib` (bare class alias, no namespace path)
- **Files modified:** src/Pdf/PdfGenerator.php
- **Verification:** PHPCS clean; class resolves correctly via classmap autoloader
- **Committed in:** 7a050e0 (Task 2 commit)

**2. [Rule 1 - Bug] PHPCS short ternary violations fixed**
- **Found during:** Task 2 verification (phpcs run)
- **Issue:** `?: '#2271b1'` and `?: $club_name` violated WPCS DisallowShortTernary sniff
- **Fix:** Replaced with full ternary expressions using intermediate variable for sanitize_hex_color result
- **Files modified:** src/Pdf/PdfGenerator.php
- **Verification:** PHPCS exits 0 with no errors
- **Committed in:** 7a050e0 (Task 2 commit)

**3. [Rule 1 - Bug] PHPCS associative array formatting fixed**
- **Found during:** Task 2 verification (phpcs run)
- **Issue:** Multi-item associative arrays had values on same line, violating WPCS array formatting sniff
- **Fix:** Expanded TextField() prop and options arrays to multi-line format
- **Files modified:** src/Pdf/PdfGenerator.php
- **Verification:** PHPCS exits 0 with no errors
- **Committed in:** 7a050e0 (Task 2 commit)

---

**Total deviations:** 3 auto-fixed (all Rule 1 - Bug)
**Impact on plan:** All fixes necessary for correct class resolution and PHPCS compliance. No scope creep.

## Issues Encountered

- Composer not installed locally — ran all composer commands via Docker container (`docker compose exec wordpress composer ...`). This is the established project pattern.

## User Setup Required

None — no external service configuration required. TCPDF installs from Packagist; Strauss prefixing runs automatically via composer post-install-cmd.

## Next Phase Readiness

- PdfGenerator public API (generate/generate_blank) unchanged — all callers (AjaxHandlers, Mailer) work without modification
- TCPDF vendor-prefixed artifacts rebuild automatically on `composer install` (post-install-cmd)
- Plan 04-02 (admin settings) can now reference form_notes key — PdfGenerator is already using it
