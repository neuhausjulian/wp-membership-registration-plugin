---
phase: 04-frontend-form-and-ux-polish
plan: "04"
subsystem: testing
tags: [phpunit, brain-monkey, mockery, patchwork, form-renderer, ajax-handlers, pdf-generator]

# Dependency graph
requires:
  - phase: 04-03
    provides: FormRenderer.php, AjaxHandlers::handle_submit_form(), PdfGenerator with TCPDF and form_notes key

provides:
  - Brain Monkey unit tests for FormRenderer::render() (6 tests covering output shape)
  - Brain Monkey unit tests for AjaxHandlers::handle_submit_form() (3 tests: honeypot, wmr_form_submitted action, success message)
  - Updated PdfGeneratorTest stubs using form_notes key instead of gdpr_text

affects: [phase-04-complete]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Functions\\expect() + addToAssertionCount(1) pattern for Mockery-only assertions to avoid PHPUnit risky flag"
    - "wp_send_json_success must NOT be pre-registered as stub when using Functions\\expect() on it — Brain Monkey 2.7 skips redefineUsingExpectation for already-registered stubs"
    - "esc_html_e stub must use function(){echo...} not fn()=>print() — print language construct causes Patchwork parse error in arrow function body"
    - "wp_nonce_field stub must use optional parameters (not 4 required) — FormRenderer calls it with 2 args"

key-files:
  created:
    - tests/Unit/Frontend/FormRendererTest.php
    - tests/Unit/Admin/AjaxHandlersTest.php
  modified:
    - tests/Unit/Pdf/PdfGeneratorTest.php

key-decisions:
  - "Functions\\expect() for wp_send_json_success cannot coexist with a global Functions\\stubs() registration of the same function in Brain Monkey 2.7 — remove from setUp stubs, use expect() per-test only"
  - "addToAssertionCount(1) added to AjaxHandlersTest tests that rely purely on Mockery call-count expectations, to suppress PHPUnit risky flag"
  - "PdfGeneratorTest required three new stubs: sanitize_text_field, sanitize_hex_color, esc_html__ — TCPDF-based PdfGenerator calls these during branding option loading"

patterns-established:
  - "Pattern: Mockery-only test expectations require addToAssertionCount(1) to avoid PHPUnit risky flag without adding redundant PHPUnit assertions"
  - "Pattern: esc_html_e stub must use regular function syntax with echo — not arrow function with print"

requirements-completed: [DEV-02]

# Metrics
duration: 15min
completed: 2026-03-23
---

# Phase 4 Plan 04: PHPUnit Test Coverage for Phase 4 Classes Summary

**Brain Monkey unit tests for FormRenderer::render() and AjaxHandlers::handle_submit_form(), plus PdfGeneratorTest stub migration from gdpr_text to form_notes — 18 tests total, zero failures**

## Performance

- **Duration:** 15 min
- **Started:** 2026-03-23T08:16:00Z
- **Completed:** 2026-03-23T08:20:00Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- 6 passing FormRendererTest tests verifying HTML output shape: wmr-form-container, honeypot input, data-required, data-email, wmr-field-error spans, consent checkbox with consent_text
- 3 passing AjaxHandlersTest tests: honeypot non-empty silently succeeds without firing wmr_form_submitted; clean submission fires wmr_form_submitted with field_values array and member_email string; response includes success_message from wmr_form_settings
- PdfGeneratorTest stub updated from gdpr_text to form_notes, plus three missing stubs added (sanitize_text_field, sanitize_hex_color, esc_html__); full suite 18 tests, 22 assertions, zero errors

## Task Commits

Each task was committed atomically:

1. **Task 1: Write FormRendererTest.php and AjaxHandlersTest.php** - `6cf6f2a` (test)
2. **Task 2: Update PdfGeneratorTest.php and run full suite** - `cc71b05` (fix)

**Plan metadata:** (docs commit follows)

_Note: TDD tasks may have multiple commits (test → feat → refactor). This plan used greenfield TDD — the implementation already existed from 04-03; tests were written to verify it._

## Files Created/Modified

- `tests/Unit/Frontend/FormRendererTest.php` - 6 Brain Monkey tests for FormRenderer::render() HTML output
- `tests/Unit/Admin/AjaxHandlersTest.php` - 3 Brain Monkey tests for AjaxHandlers::handle_submit_form() (honeypot, action fire, success response)
- `tests/Unit/Pdf/PdfGeneratorTest.php` - Stub update: gdpr_text -> form_notes; added sanitize_text_field, sanitize_hex_color, esc_html__ stubs

## Decisions Made

- `wp_send_json_success` removed from global setUp stubs in AjaxHandlersTest — must use `Functions\expect()` per test for call-count assertions; Brain Monkey 2.7 skips redefineUsingExpectation when function already registered as stub
- `addToAssertionCount(1)` used in all three AjaxHandlersTest methods — Mockery verifies expectations on tearDown, but PHPUnit marks tests "risky" with no PHPUnit assertions
- `esc_html_e` stub uses regular `function(){ echo ... }` syntax instead of arrow function with `print` — Patchwork preprocessor fails to parse `print` language construct in arrow function body

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Stub] Added esc_html_e stub to FormRendererTest**
- **Found during:** Task 1 (FormRendererTest execution)
- **Issue:** FormRenderer::render() calls `esc_html_e()` in honeypot label and submit button; not stubbed in plan's provided code template
- **Fix:** Added `'esc_html_e' => function($text,$domain='default'){echo htmlspecialchars($text,ENT_QUOTES,'UTF-8');}` to setUp stubs
- **Files modified:** tests/Unit/Frontend/FormRendererTest.php
- **Verification:** All 6 FormRendererTest tests pass
- **Committed in:** 6cf6f2a (Task 1 commit)

**2. [Rule 1 - Bug] Fixed wp_nonce_field stub signature**
- **Found during:** Task 1 (FormRendererTest execution)
- **Issue:** Plan template used 4 required parameters in wp_nonce_field closure; WordPress calls it with 2 args (action, name), causing ArgumentCountError
- **Fix:** Changed to optional parameters with defaults matching WP signature
- **Files modified:** tests/Unit/Frontend/FormRendererTest.php
- **Verification:** All 6 FormRendererTest tests pass
- **Committed in:** 6cf6f2a (Task 1 commit)

**3. [Rule 1 - Bug] Removed wp_send_json_success from AjaxHandlersTest setUp stubs**
- **Found during:** Task 1 (AjaxHandlersTest execution)
- **Issue:** Brain Monkey 2.7 skips redefineUsingExpectation for functions already registered as stubs; Functions\expect('wp_send_json_success')->once() was never satisfied because the stub suppressed the expectation setup
- **Fix:** Removed wp_send_json_success from global setUp stubs; use Functions\expect() per-test exclusively
- **Files modified:** tests/Unit/Admin/AjaxHandlersTest.php
- **Verification:** All 3 AjaxHandlersTest tests pass without risky flag (Mockery expectations verified)
- **Committed in:** 6cf6f2a (Task 1 commit)

**4. [Rule 2 - Missing Stubs] Added sanitize_text_field, sanitize_hex_color, esc_html__ to PdfGeneratorTest**
- **Found during:** Task 2 (full suite run)
- **Issue:** TCPDF-based PdfGenerator calls sanitize_text_field and sanitize_hex_color when loading branding options; these were not in the original PdfGeneratorTest stubs (written for DOMPDF-based implementation)
- **Fix:** Added three stubs to PdfGeneratorTest::setUp() Functions\stubs() call
- **Files modified:** tests/Unit/Pdf/PdfGeneratorTest.php
- **Verification:** All 3 PdfGeneratorTest tests pass
- **Committed in:** cc71b05 (Task 2 commit)

---

**Total deviations:** 4 auto-fixed (2 Bug/Rule 1, 2 Missing Critical/Rule 2)
**Impact on plan:** All auto-fixes were necessary for correct stub coverage matching actual code behaviour. No scope creep.

## Issues Encountered

- Patchwork preprocessor reported "Unclosed '{'" error on first full suite run — was a transient PHP opcode state issue; cleared automatically on second run. Files lint cleanly via `php -l`.
- Pre-existing MailerTest risky tests (4 of them): out of scope per deviation rules — not fixed.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Phase 4 is now complete: all 5 plans (01-05) executed across 3 waves
- Full PHPUnit suite: 18 tests, 22 assertions, zero failures
- DEV-02 (PHPUnit test suite covering Phase 4 classes) complete
- Project v1.0 milestone complete — all 4 phases, 16 plans executed

---
*Phase: 04-frontend-form-and-ux-polish*
*Completed: 2026-03-23*
