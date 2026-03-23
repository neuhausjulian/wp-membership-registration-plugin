---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: milestone
status: executing
stopped_at: Completed 04-09-PLAN.md
last_updated: "2026-03-23T22:23:03.936Z"
last_activity: 2026-03-22 — Plan 03-04 complete; Phase 3 e2e verification confirmed in Docker + Mailpit (CONF-05 gate passed)
progress:
  total_phases: 4
  completed_phases: 4
  total_plans: 20
  completed_plans: 20
  percent: 91
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-20)

**Core value:** A member can register digitally and receive a legally valid, pre-filled membership form (PDF) without the club needing to pay for any software or manage a spreadsheet.
**Current focus:** Phase 4 — Form Handler

## Current Position

Phase: 4 of 4 (Form Handler)
Plan: 0 of ? in current phase
Status: In progress
Last activity: 2026-03-22 — Plan 03-04 complete; Phase 3 e2e verification confirmed in Docker + Mailpit (CONF-05 gate passed)

Progress: [█████████░] 91%

## Performance Metrics

**Velocity:**
- Total plans completed: 1
- Average duration: 2 min
- Total execution time: 2 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-plugin-foundation-and-settings | 1 | 2 min | 2 min |

**Recent Trend:**
- Last 5 plans: 01-01 (2 min)
- Trend: —

*Updated after each plan completion*
| Phase 01-plugin-foundation-and-settings P02 | 3 | 2 tasks | 6 files |
| Phase 01-plugin-foundation-and-settings P03 | 3 | 2 tasks | 4 files |
| Phase 01-plugin-foundation-and-settings P03 | 30 | 3 tasks | 8 files |
| Phase 02-pdf-engine P02 | 3 | 2 tasks | 2 files |
| Phase 02-pdf-engine P01 | 5 | 2 tasks | 9 files |
| Phase 02-pdf-engine P03 | 35 | 3 tasks | 4 files |
| Phase 02-pdf-engine P04 | continuation | 3 tasks | 5 files |
| Phase 03-email-notifications P01 | 8 | 1 tasks | 1 files |
| Phase 03-email-notifications P03 | 2 | 1 tasks | 1 files |
| Phase 03-email-notifications P02 | 20 | 2 tasks | 4 files |
| Phase 04-frontend-form-and-ux-polish P02 | 3 | 2 tasks | 2 files |
| Phase 04-frontend-form-and-ux-polish P01 | 4 | 2 tasks | 4 files |
| Phase 04-frontend-form-and-ux-polish P03 | 15 | 2 tasks | 6 files |
| Phase 04-frontend-form-and-ux-polish P05 | 2 | 2 tasks | 2 files |
| Phase 04-frontend-form-and-ux-polish P04 | 525504 | 2 tasks | 3 files |
| Phase 04-frontend-form-and-ux-polish P08 | 5 | 1 tasks | 1 files |
| Phase 04-frontend-form-and-ux-polish P07 | 8 | 1 tasks | 2 files |
| Phase 04-frontend-form-and-ux-polish P06 | 6 | 3 tasks | 6 files |
| Phase 04-frontend-form-and-ux-polish P09 | 15 | 2 tasks | 1 files |

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- Roadmap: 4-phase build order derived from component dependency graph (SettingsManager → PDFGenerator → Mailer → FormHandler)
- Research: DOMPDF remote access must be disabled (`$isRemoteEnabled = false`) — implement in Phase 2, never relax
- Research: All PDFs must use `sys_get_temp_dir()` + immediate `unlink()` — never write to `wp-content/uploads/`
- Research: Strauss preferred over PHP-Scoper for vendor namespace prefixing — confirm during Phase 2 planning
- 01-01: mu-plugin phpmailer_init hook used for SMTP relay (not WORDPRESS_CONFIG_EXTRA — that doesn't hook phpmailer_init)
- 01-01: wordpress:latest unpinned for development; pin before v1.0 release
- 01-01: PSR-4 filename sniffs (WordPress.Files.FileName.*) excluded in .phpcs.xml to allow ClassName.php files in src/
- [Phase 01-02]: Field schema stored as JSON-encoded string (wmr_field_schema) not PHP serialized array — enables safe json_decode on both PHP and JS sides
- [Phase 01-02]: wp_unslash() applied before json_decode() in sanitize_field_schema to handle WP magic quotes on POST data
- [Phase 01-02]: Hook-suffix guard on admin_enqueue_scripts prevents wp_enqueue_media() loading on every admin page
- [Phase 01-02]: AjaxHandlers stub created in plan 02 so Plugin.php can instantiate; full implementation deferred to Plan 03
- [Phase 01-plugin-foundation-and-settings]: PHP sets initial tab panel visibility (active/hidden) to avoid flash before JS loads; JS takes over tab switching from that state
- [Phase 01-plugin-foundation-and-settings]: wmrExistingFields bootstrapped via inline script block using FieldSchema::decode() — eliminates extra AJAX round-trip on page load
- [Phase 01-plugin-foundation-and-settings]: Tab persistence via sessionStorage — survives options.php redirect without requiring tab query parameter
- [Phase 01-plugin-foundation-and-settings]: spl_autoload_register used instead of vendor autoloader — avoids fatal error when vendor/ not present in container
- [Phase 01-plugin-foundation-and-settings]: SMTPAutoTLS = false in mailpit-smtp.php — Mailpit does not support STARTTLS; PHPMailer AutoTLS caused delivery failure
- [Phase 02-02]: wp_kses_post() for gdpr_text, footer_text, page2_content — these fields accept HTML links/formatting for legal text
- [Phase 02-02]: sanitize_text_field() for document_title — plain text, no HTML expected
- [Phase 02-01]: Strauss chosen over PHP-Scoper for namespace scoping — simpler config, no build step
- [Phase 02-01]: vendor-prefixed/ excluded from git — generated artifact rebuilt via post-install-cmd
- [Phase 02-01]: file_exists() guard on vendor-prefixed/autoload.php prevents fatal error on fresh clone
- [Phase 02-pdf-engine]: wp_delete_file() used in AjaxHandlers smoke-test stub instead of bare unlink() to satisfy WPCS
- [Phase 02-pdf-engine]: phpcs:disable/enable block required for multi-line ternary nonce suppression — single-line phpcs:ignore insufficient
- [Phase 02-pdf-engine]: Brain Monkey stubs for esc_attr/esc_html/wp_kses_post must be added explicitly — not auto-stubbed by Brain Monkey
- [Phase 02-pdf-engine]: Strauss package names must use dompdf/php-font-lib not phenx/ — phenx packages not installed so Strauss silently skips scoping them
- [Phase 02-pdf-engine]: bin/patch-vendor-prefixed.php patches hard-coded FontLib\ strings Strauss cannot rewrite (upstream PR #148)
- [Phase 02-pdf-engine]: wp_ajax_wmr_generate_pdf_stub is logged-in only (no nopriv) — dev/test smoke-test endpoint, Phase 4 replaces
- [Phase 03-email-notifications]: error_log not stubbed in Mailer tests — PHP internal not patchwork-redefinable without patchwork.json change
- [Phase 03-email-notifications]: Mailer accepts optional PdfGenerator constructor param for test injection (constructor injection pattern)
- [Phase 03-email-notifications]: Warning notice placed BEFORE <form> tag in Email Settings tab — inside form disrupts layout (Pitfall 4)
- [Phase 03-email-notifications]: Email preview boxes placed OUTSIDE form to prevent accidental submission of read-only content
- [Phase 03-email-notifications]: filter_var(FILTER_VALIDATE_EMAIL) used instead of is_email() in Mailer — WP function not stubbed by Brain Monkey in unit test context
- [Phase 03-email-notifications]: wp_delete_file stub moved from setUp to individual tests in MailerTest — Brain Monkey 2.7 skips redefineUsingExpectation when function already registered as stub
- [Phase 03-email-notifications]: Single admin recipient passed as string not array to wp_mail() — tests assert string equality on  parameter
- [Phase 03-email-notifications]: __() i18n stub added to tests/bootstrap.php — not auto-stubbed by Brain Monkey, required for Mailer translated strings
- [Phase 04-frontend-form-and-ux-polish]: gdpr_text migration fallback retained in sanitize_pdf_branding to migrate existing installs — reads input[gdpr_text] as fallback for form_notes but never writes it back
- [Phase 04-frontend-form-and-ux-polish]: Form Settings tab uses data-tab='form-settings' — existing JS activateTab() handles it dynamically, no JS changes needed
- [Phase 04-frontend-form-and-ux-polish]: TCPDF uses classmap (WpMembershipRegistration_Vendor_TCPDF) not PHP namespace — plan's use statement incorrect; adapted to bare class alias
- [Phase 04-frontend-form-and-ux-polish]: bin/patch-vendor-prefixed.php reduced to no-op stub — DOMPDF FontLib hard-coded string patches no longer needed for TCPDF
- [Phase 04-frontend-form-and-ux-polish]: phpcs:disable/enable block used for multi-line $_POST['wmr_fields'] ternary — single-line phpcs:ignore only suppresses first line of multi-line expression
- [Phase 04-05]: README.md targets club admins (non-developers) — no docker setup, focused on WP admin UI and shortcode usage
- [Phase 04-04]: wp_send_json_success must not be pre-registered as stub in setUp when using Functions\expect() — Brain Monkey 2.7 skips redefineUsingExpectation for already-registered stubs; use expect() per-test exclusively
- [Phase 04-04]: addToAssertionCount(1) required in Mockery-only test methods to suppress PHPUnit risky flag when no PHPUnit assertions are present
- [Phase 04-04]: esc_html_e stub must use regular function syntax with echo, not arrow function with print — Patchwork preprocessor fails to parse print language construct in arrow function body
- [Phase 04-frontend-form-and-ux-polish]: Consent checkbox rendered unconditionally — GDPR compliance requires it always appear regardless of admin config state
- [Phase 04-frontend-form-and-ux-polish]: Default consent label 'Ich stimme der Verarbeitung meiner personenbezogenen Daten zu.' used as translatable fallback via __()
- [Phase 04-06]: PDF download uses transient-stored base64 (not temp file on disk) — satisfies no uploads/ constraint; member_email_sent reflects email address availability not wp_mail() delivery status
- [Phase 04-frontend-form-and-ux-polish]: inline <style> tag prepended to HTML string before writeHTML() — TCPDF CSS scoped to block, no global state; margin-top:0 suppresses default top margin after Ln(4) spacer; wpautop() applied after wp_kses_post() for correct ordering

### Pending Todos

None yet.

### Blockers/Concerns

- Phase 1: Settings API does not natively support repeater fields; custom JS + serialized JSON option approach needs to be confirmed during Phase 1 planning before implementation
- Phase 2: Strauss vs PHP-Scoper choice must be locked before Phase 2 starts to avoid late-phase distribution build refactor
- Phase 2: PDF template configurability scope needs explicit bounding during Phase 2 planning (field-order + branding only, not a visual layout builder)

## Session Continuity

Last session: 2026-03-23T22:23:03.933Z
Stopped at: Completed 04-09-PLAN.md
Resume file: None
