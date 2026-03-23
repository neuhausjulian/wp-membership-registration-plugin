---
phase: 04-frontend-form-and-ux-polish
plan: "09"
subsystem: pdf
tags: [tcpdf, pdf, html-rendering, paragraph-spacing, wpautop, form-notes]

# Dependency graph
requires:
  - phase: 04-frontend-form-and-ux-polish
    provides: PdfGenerator with TCPDF and form_notes/page2_content writeHTML() call sites
provides:
  - CSS paragraph margins injected into both writeHTML() HTML strings (margin-top:0; margin-bottom:6pt)
  - wpautop() applied to form_notes and page2_content before writeHTML() to convert surviving newlines to <p> tags
affects: [pdf-generation]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "TCPDF CSS injection: prepend <style>p { margin-top: 0; margin-bottom: 6pt; }</style> to HTML string before writeHTML() — TCPDF applies no default CSS margin to <p> elements"
    - "wpautop() applied after wp_kses_post() to convert TinyMCE newlines to <p> tags before PDF rendering"

key-files:
  created: []
  modified:
    - src/Pdf/PdfGenerator.php

key-decisions:
  - "inline <style> tag prepended to HTML string (not a separate writeHTML call) — TCPDF CSS scoped to the block, no global state bleed"
  - "margin-top:0 suppresses TCPDF default top margin on first <p> to avoid unwanted gap after the Ln(4) spacer already in place"
  - "wpautop() called after wp_kses_post() — safe ordering: kses strips disallowed tags first, wpautop then converts surviving newlines to paragraphs"

patterns-established:
  - "TCPDF paragraph spacing pattern: always prepend <style>p { margin-top:0; margin-bottom:6pt; }</style> when passing TinyMCE HTML to writeHTML()"

requirements-completed: [FORM-04]

# Metrics
duration: ~15min
completed: 2026-03-23
---

# Phase 4 Plan 09: TCPDF Paragraph CSS Fix Summary

**Inline CSS injection + wpautop() transforms TinyMCE multi-paragraph form_notes and page2_content into visibly spaced paragraphs in the generated membership PDF**

## Performance

- **Duration:** ~15 min (including human visual verification)
- **Started:** 2026-03-23T22:05:00Z
- **Completed:** 2026-03-23T22:22:28Z
- **Tasks:** 2 (1 code, 1 human-verify checkpoint)
- **Files modified:** 1

## Accomplishments
- Updated both writeHTML() call sites in PdfGenerator::build_pdf() to prepend an inline CSS style block with `p { margin-top: 0; margin-bottom: 6pt; }` before calling writeHTML()
- Applied wpautop() to both $form_notes and $page2_content to ensure TinyMCE-authored newlines are converted to `<p>` tags before PDF rendering
- All existing PHPUnit tests pass with zero regressions
- Human visual verification confirmed paragraphs are visibly spaced in the generated PDF (UAT gap 6 closed)

## Task Commits

Each task was committed atomically:

1. **Task 1: Inject paragraph CSS and apply wpautop() before writeHTML() calls** - `d948f3e` (fix)
2. **Task 2: Verify paragraph spacing in generated PDF** - human checkpoint, approved (no code commit)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `src/Pdf/PdfGenerator.php` - Both writeHTML() call sites updated: CSS style block prepended and wpautop() wrapping applied for form_notes and page2_content blocks

## Decisions Made
- Inline `<style>` tag prepended to HTML string (not a separate writeHTML() call) — TCPDF CSS scoped to the block, avoids global state
- `margin-top: 0` suppresses TCPDF default top margin on the first `<p>` to avoid unwanted gap after the existing `Ln(4)` spacer
- `wpautop()` applied after `wp_kses_post()` — correct ordering: kses strips disallowed tags first, wpautop converts surviving newlines to paragraph tags

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Gap 6 (form_notes paragraph spacing in PDF) is now closed
- All 6 UAT gaps (04-06 through 04-09) addressed
- Phase 4 gap-closure plans complete; project at v1.0 milestone readiness

---
*Phase: 04-frontend-form-and-ux-polish*
*Completed: 2026-03-23*
