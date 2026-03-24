---
phase: 02-pdf-engine
plan: 03
subsystem: pdf-engine
tags: [pdf, dompdf, tdd, ajax, phpunit]
dependency_graph:
  requires:
    - 02-01 (DOMPDF scoped in vendor-prefixed, PHPUnit scaffold)
  provides:
    - PdfGenerator::generate(array): string — PDF-01 contract
    - PdfGenerator::generate_blank(): string — PDF-03 contract
    - AjaxHandlers::handle_generate_pdf_stub() — smoke-test endpoint
  affects:
    - 02-04 (blank PDF download hook wires to generate_blank)
    - Phase 4 (form submission handler replaces stub with real handler)
tech_stack:
  added:
    - DOMPDF 3.x via WpMembershipRegistration\Vendor\Dompdf namespace (Strauss-scoped)
  patterns:
    - TDD (RED/GREEN/REFACTOR cycle with PHPUnit + Brain Monkey)
    - ob_start() + include for template rendering into DOMPDF
    - base64 data-URI for logo embedding (isRemoteEnabled=false constraint)
    - sys_get_temp_dir() for PDF temp file output; caller unlinks
key_files:
  created:
    - src/Pdf/PdfGenerator.php
    - templates/pdf/membership-form.php
  modified:
    - tests/Unit/Pdf/PdfGeneratorTest.php (replaced markTestIncomplete stubs with 3 real tests)
    - src/Admin/AjaxHandlers.php (added handle_generate_pdf_stub method)
decisions:
  - "wp_delete_file() used instead of bare unlink() in AjaxHandlers to satisfy WPCS"
  - "phpcs:disable/enable block used for nonce suppression in smoke-test stub (single-line phpcs:ignore did not cover multi-line ternary)"
  - "Brain Monkey stubs added for esc_attr, esc_html, wp_kses_post — required by template include scope"
  - "Generic.CodeAnalysis.UnusedFunctionParameter ignored on render_template() — parameters used via PHP include scope, not detectable by static analysis"
metrics:
  duration: 35 min
  completed_date: "2026-03-21"
  tasks_completed: 3
  files_created: 2
  files_modified: 2
---

# Phase 2 Plan 3: PDF Engine Core (PdfGenerator + Template) Summary

**One-liner:** DOMPDF-backed PdfGenerator with scoped WpMembershipRegistration\Vendor namespace, base64 logo data-URI, sys_get_temp_dir output, and membership-form.php HTML template covering header/fields/GDPR/signature/footer/page2.

## What Was Built

### PdfGenerator class (`src/Pdf/PdfGenerator.php`)

Two public methods:
- `generate(array $field_values): string` — renders the HTML template with submitted values, converts to PDF via DOMPDF, writes to sys_get_temp_dir(), returns path.
- `generate_blank(): string` — same flow but with `$is_blank=true`, rendering underline placeholders instead of values.

Security constraints hard-coded: `isRemoteEnabled=false` and `isPhpEnabled=false` in DOMPDF Options (cannot be relaxed by callers).

Logo is embedded as a base64 data-URI (`data:image/...;base64,...`) by resolving the logo URL to the local filesystem path via `wp_upload_dir()` — required because DOMPDF cannot fetch remote URLs.

### HTML Template (`templates/pdf/membership-form.php`)

Included via `ob_start() + include` scope. Available variables injected by PdfGenerator:
- `$club_name`, `$accent_color`, `$document_title`, `$gdpr_text`, `$footer_text`, `$page2_content`
- `$logo_data_uri` (base64 data-URI or empty string)
- `$fields` (array from FieldSchema::decode)
- `$field_values` (submitted values or empty array)
- `$is_blank` (bool)

Document structure: header (logo + club name/title) → accent color separator → dynamic field rows → GDPR text → place/date line → signature line → fixed footer → optional page 2 with page-break.

### PHPUnit Tests (`tests/Unit/Pdf/PdfGeneratorTest.php`)

Replaced the three `markTestIncomplete` stubs with real tests:
- `test_generate_returns_pdf_file` (PDF-01): confirms path ends in `.pdf`, file exists, first 4 bytes are `%PDF`.
- `test_branding_fields_appear_in_template` (PDF-02): confirms file exists and filesize > 1000 bytes (non-trivial PDF).
- `test_blank_pdf_contains_underlines` (PDF-03): same file/header assertions for `generate_blank()`.

Brain Monkey stubs: `wp_upload_dir`, `wp_generate_uuid4`, `get_option` (returns branding + field schema), `esc_attr`, `esc_html`, `wp_kses_post`.

### AJAX Stub (`src/Admin/AjaxHandlers.php`)

`handle_generate_pdf_stub()` proves the PDF-01 submission contract: reads `$_POST['field_values']`, calls `PdfGenerator::generate($field_values)`, deletes the temp file via `wp_delete_file()`, returns JSON success or error. Hook registration deferred to Plan 04.

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| 1 (TDD — tests + PdfGenerator) | `9ebd09b` | feat(02-03): implement PdfGenerator class |
| 2 (HTML template) | `3121283` | feat(02-03): add membership-form.php template |
| 3 (AJAX stub) | `e321ad2` | feat(02-03): add handle_generate_pdf_stub() |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing stubs] Added WP escaping functions to Brain Monkey stubs in test setUp**
- **Found during:** Task 1 (GREEN phase) — `esc_attr()` undefined error when template was included
- **Issue:** The HTML template calls `esc_attr`, `esc_html`, `wp_kses_post` which Brain Monkey doesn't auto-stub
- **Fix:** Added `esc_attr`, `esc_html`, `wp_kses_post` to `Functions\stubs()` array in `PdfGeneratorTest::setUp()`
- **Files modified:** `tests/Unit/Pdf/PdfGeneratorTest.php`
- **Commit:** `9ebd09b`

**2. [Rule 2 - PHPCS compliance] Added phpcs:ignore for UnusedFunctionParameter on render_template()**
- **Found during:** Task 1 PHPCS verification
- **Issue:** PHPCS flags `$field_values` and `$is_blank` as unused because they're consumed by the included template, not directly in the function body
- **Fix:** Added `// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed` inline on the function signature
- **Files modified:** `src/Pdf/PdfGenerator.php`
- **Commit:** `9ebd09b`

**3. [Rule 2 - PHPCS compliance] Added phpcs:ignore for base64_encode in get_logo_data_uri()**
- **Found during:** Task 1 PHPCS verification
- **Issue:** `WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode` warning — usage is legitimate (data-URI for logo)
- **Fix:** Extended existing `file_get_contents` phpcs:ignore to also cover `obfuscation_base64_encode`
- **Files modified:** `src/Pdf/PdfGenerator.php`
- **Commit:** `9ebd09b`

**4. [Rule 2 - PHPCS compliance] Used phpcs:disable/enable block and wp_delete_file() in AJAX stub**
- **Found during:** Task 3 PHPCS verification
- **Issue 1:** Single-line `phpcs:ignore` on the comment line before a multi-line ternary didn't suppress the nonce error on the continuation lines
- **Issue 2:** `@unlink()` flagged by `WordPress.WP.AlternativeFunctions` — should use `wp_delete_file()`
- **Fix 1:** Changed to `phpcs:disable`/`phpcs:enable` block to cover all lines of the ternary
- **Fix 2:** Replaced `@unlink($path)` with `wp_delete_file($path)`
- **Files modified:** `src/Admin/AjaxHandlers.php`
- **Commit:** `e321ad2`

## Self-Check: PASSED

- FOUND: src/Pdf/PdfGenerator.php
- FOUND: templates/pdf/membership-form.php
- FOUND: .planning/phases/02-pdf-engine/02-03-SUMMARY.md
- FOUND commit: 9ebd09b (PdfGenerator + tests)
- FOUND commit: 3121283 (HTML template)
- FOUND commit: e321ad2 (AJAX stub)
- PHPUnit: 3 tests, 8 assertions, OK
- PHPCS: exits 0 (no errors, no warnings)
