---
phase: 4
slug: frontend-form-and-ux-polish
status: complete
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-22
updated: 2026-03-23
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9.6 + Brain Monkey 2.7 |
| **Config file** | `phpunit.xml` (root) |
| **Quick run command** | `docker-compose exec wordpress vendor/bin/phpunit tests/Unit/ --stop-on-failure` |
| **Full suite command** | `docker-compose exec wordpress vendor/bin/phpunit` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `docker-compose exec wordpress vendor/bin/phpunit tests/Unit/ --stop-on-failure`
- **After every plan wave:** Run `docker-compose exec wordpress vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| TCPDF install | 01 | 1 | DEV-02 | infra | `ls vendor-prefixed/tecnickcom/` | ✅ | ✅ green |
| PdfGenerator rewrite | 01 | 1 | DEV-02 | unit | `phpunit tests/Unit/Pdf/PdfGeneratorTest.php` | ✅ | ✅ green |
| FormRenderer::render() | 02 | 1 | FORM-01, FORM-02 | unit | `phpunit tests/Unit/Frontend/FormRendererTest.php` | ✅ | ✅ green |
| AjaxHandlers submit | 02 | 1 | FORM-03, FORM-05 | unit | `phpunit tests/Unit/Admin/AjaxHandlersTest.php` | ✅ | ✅ green |
| Form Settings tab | 03 | 2 | FORM-02, FORM-05 | manual | Admin settings page visual check | N/A | ○ manual |
| form.js validation | 03 | 2 | FORM-04 | manual | Browser submit with empty fields | N/A | ○ manual |
| CONTRIBUTING.md | 04 | 2 | DEV-04 | infra | `test -f CONTRIBUTING.md` | ✅ | ✅ green |
| README.md | 04 | 2 | DEV-05 | infra | `test -f README.md` | ✅ | ✅ green |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky · ○ manual*

---

## Wave 0 Requirements

- [x] `tests/Unit/Frontend/FormRendererTest.php` — 6 tests covering FORM-01, FORM-02
- [x] `tests/Unit/Admin/AjaxHandlersTest.php` — 3 tests covering FORM-03, FORM-05
- [x] `tests/Unit/Pdf/PdfGeneratorTest.php` — updated: `gdpr_text` → `form_notes`, TCPDF stubs
- [x] `CONTRIBUTING.md` at repo root — covers DEV-04
- [x] `README.md` at repo root — covers DEV-05

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Client-side JS validation errors | FORM-04 | PHPUnit does not execute JavaScript | Load form page in browser; submit with empty required fields; verify `<span class="wmr-field-error">` messages appear inline without page reload |
| Consent checkbox blocks submission | FORM-02 (JS side) | JavaScript behavior | Submit form without checking consent; verify JS blocks AJAX call |
| Success message in-place swap | FORM-05 (JS side) | JavaScript behavior | Submit valid form; verify form container replaced by success message without page reload |
| TinyMCE editor renders in PDF Branding tab | DEV-02 (admin UX) | DOM rendering requires browser | Click PDF Branding tab; verify wp_editor() shows TinyMCE toolbar, not plain textarea |
| Full pipeline after submission | FORM-05 (integration) | Email + PDF delivery | Submit valid form; verify PDF generated, member email sent, admin email sent |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** 2026-03-23 — 18 tests, 22 assertions, 0 failures (4 risky are pre-existing Phase 3 MailerTest)

---

## Validation Audit 2026-03-23

| Metric | Count |
|--------|-------|
| Gaps found | 0 |
| Resolved | 0 |
| Escalated to manual-only | 0 |
| Total automated | 12 (tests) |
| Manual-only | 5 behaviors |
