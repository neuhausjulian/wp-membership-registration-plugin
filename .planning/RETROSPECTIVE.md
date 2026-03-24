# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — MVP

**Shipped:** 2026-03-24
**Phases:** 4 | **Plans:** 21 | **Timeline:** 2026-03-20 → 2026-03-24 (4 days)

### What Was Built

- **Phase 1** — WordPress plugin scaffold with full admin settings UI: configurable field schema (JSON-encoded), PDF branding (logo, color, club name), email recipients, test-email button; Docker + Mailpit dev environment; PHPCS/WPCS enforced
- **Phase 2** — TCPDF-based PDF engine (initially DOMPDF, swapped in Phase 4): pre-filled two-column layout, branded header, blank AcroForm download; vendor namespaced via Strauss classmap
- **Phase 3** — Email notification system: member and admin receive PDF attachment on submission; hook-based Mailer with try/finally cleanup guarantee; SMTP verified end-to-end in Mailpit
- **Phase 4** — Complete frontend form pipeline: `[membership_form]` shortcode renders FormRenderer output, AJAX submission, honeypot spam protection, GDPR consent checkbox (always rendered, translatable German default), inline validation, configurable success message; PHPCS clean

### What Worked

- **Component dependency order** (Settings → PDF → Email → Form) meant each phase had a stable contract to build against — zero late-phase rewrites due to interface drift
- **TDD scaffold plans** (03-01 failing tests before 03-02 implementation) gave a clear, verifiable green-to-red gate — reduced ambiguity about what "done" meant
- **Brain Monkey unit tests** ran in under 3 seconds without any WP bootstrap — fast feedback loop encouraged incremental test writing
- **Strauss classmap** for TCPDF required zero special build step in dev and worked reliably across Docker restarts
- **Decimal gap-closure phases** (04-06 through 04-10) kept UAT-found issues organized and individually verifiable without reopening the main phase plans

### What Was Inefficient

- **DOMPDF → TCPDF swap** happened in Phase 4 (plan 04-01) after DOMPDF was already implemented in Phase 2 — the engine choice was deferred rather than decided at architecture time; cost: one full plan of throwaway work
- **Paragraph CSS / wpautop() fix** required three separate gap-closure attempts (04-08, 04-09, plan 04-10's verification) because the TCPDF writeHTML() CSS scoping behavior wasn't fully understood until runtime — a TCPDF spike before Phase 2 would have caught this
- **PHPCS accumulated violations** across multiple plans rather than being caught inline — final 04-10 clean-up plan fixed 31 violations that could have been prevented by running phpcs in each executor's self-check
- **Brain Monkey stub ordering gotcha** (wp_send_json_success must not be pre-registered as a stub when using Functions\expect()) appeared in Phase 4 tests after the same pattern was already established in Phase 3 — better cross-phase test conventions would have prevented the repeat

### Patterns Established

- **`sys_get_temp_dir()` + immediate `unlink()`** for all PDF handling — never write to uploads/; this became a hard project constraint documented in PROJECT.md
- **`wp_kses_post()` for HTML-accepting settings fields** — gdpr_text/form_notes/footer_text/page2_content all go through kses before storage
- **`wp_unslash()` before `json_decode()`** for all JSON-encoded options — WP magic quotes on POST data make this required
- **`file_exists()` guard on vendor-prefixed/autoload.php** — prevents fatal on fresh clone before Strauss runs; add this whenever Strauss is used
- **Consent checkbox always rendered, never gated** — GDPR compliance cannot be contingent on admin configuration state

### Key Lessons

1. **Decide the PDF engine before starting PDF work** — TCPDF vs DOMPDF affects the entire PdfGenerator API, template approach, and Strauss config; it cannot be deferred to a later phase without paying a rewrite cost
2. **Run PHPCS in each plan's self-check** — violations accumulate invisibly and require a dedicated clean-up plan; `vendor/bin/phpcs src/ templates/ assets/js/form.js` is fast and should be a standard exit gate
3. **Brain Monkey test isolation rule**: never pre-register a function as both a `stub` (in setUp) and a `Functions\expect()` (in a test) — Brain Monkey 2.7 silently skips the expectation; use `expect()` exclusively per-test
4. **TCPDF writeHTML() CSS is block-scoped** — inject `<style>` inline before the HTML string; global CSS set via `SetDefaultHTMLFontSize` does not affect paragraph spacing; `wpautop()` must be applied after `wp_kses_post()` not before
5. **Transient-stored base64 for PDF download** sidesteps the uploads/ constraint cleanly — the key is to set a short TTL (5 minutes) and never write the file to disk

### Cost Observations

- Model mix: ~100% sonnet (all executor and verifier agents ran on claude-sonnet-4-6)
- Sessions: ~6 distinct sessions over 4 days
- Notable: Wave-based parallel execution in Phase 4 (7 plans in Wave 1) completed all foundation work in a single session with no cross-plan conflicts

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Timeline | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.0 MVP | 4 days | 4 | Initial build — greenfield, component dependency order worked well |

### Cumulative Quality

| Milestone | Tests | Notes |
|-----------|-------|-------|
| v1.0 | 21 PHPUnit (Brain Monkey) | Zero failures, zero PHPCS violations at ship |
