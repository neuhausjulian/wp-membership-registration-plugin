---
phase: 4
slug: frontend-form-and-ux-polish
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-22
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9.6 + Brain Monkey 2.6 |
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
| TCPDF install | 01 | 1 | DEV-02 | manual | `docker-compose exec wordpress composer show tecnickcom/tcpdf` | ❌ W0 | ⬜ pending |
| PdfGenerator rewrite | 01 | 1 | DEV-02 | unit | `phpunit tests/Unit/Pdf/PdfGeneratorTest.php` | ✅ (update stubs) | ⬜ pending |
| FormRenderer::render() | 02 | 1 | FORM-01, FORM-02 | unit | `phpunit tests/Unit/Frontend/FormRendererTest.php` | ❌ W0 | ⬜ pending |
| AjaxHandlers submit | 02 | 1 | FORM-03, FORM-05 | unit | `phpunit tests/Unit/Admin/AjaxHandlersTest.php` | ❌ W0 | ⬜ pending |
| Form Settings tab | 03 | 2 | FORM-02, FORM-05 | manual | Admin settings page visual check | N/A | ⬜ pending |
| form.js validation | 03 | 2 | FORM-04 | manual | Browser submit with empty fields | N/A | ⬜ pending |
| CONTRIBUTING.md | 04 | 2 | DEV-04 | manual | `test -f CONTRIBUTING.md` | ❌ W0 | ⬜ pending |
| README.md | 04 | 2 | DEV-05 | manual | `test -f README.md` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/Frontend/FormRendererTest.php` — stubs for FORM-01, FORM-02
- [ ] `tests/Unit/Admin/AjaxHandlersTest.php` — stubs for FORM-03, FORM-05
- [ ] Update `tests/Unit/Pdf/PdfGeneratorTest.php` — change `gdpr_text` → `form_notes`; adapt for TCPDF (remove DOMPDF Options class usage)
- [ ] `CONTRIBUTING.md` at repo root — covers DEV-04
- [ ] `README.md` at repo root — covers DEV-05

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

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
