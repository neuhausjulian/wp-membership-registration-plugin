---
phase: 2
slug: pdf-engine
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-21
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9.x + Brain Monkey |
| **Config file** | `phpunit.xml` (Wave 0 installs) |
| **Quick run command** | `./vendor/bin/phpunit --testsuite unit --filter PdfGenerator` |
| **Full suite command** | `./vendor/bin/phpunit` |
| **Estimated runtime** | ~10 seconds |

---

## Sampling Rate

- **After every task commit:** Run `./vendor/bin/phpunit --testsuite unit --filter PdfGenerator`
- **After every plan wave:** Run `./vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 2-01-01 | 01 | 0 | PDF-01 | unit stub | `./vendor/bin/phpunit --filter PdfGeneratorTest` | ❌ W0 | ⬜ pending |
| 2-01-02 | 01 | 0 | PDF-01 | infra | `./vendor/bin/phpunit` | ❌ W0 | ⬜ pending |
| 2-01-03 | 01 | 1 | PDF-01 | unit | `./vendor/bin/phpunit --filter PdfGeneratorTest::testGenerateReturnsPdfBytes` | ❌ W0 | ⬜ pending |
| 2-02-01 | 02 | 1 | PDF-02 | unit | `./vendor/bin/phpunit --filter PdfGeneratorTest::testBrandingApplied` | ❌ W0 | ⬜ pending |
| 2-02-02 | 02 | 1 | PDF-02 | unit | `./vendor/bin/phpunit --filter PdfGeneratorTest::testLogoBase64Embedded` | ❌ W0 | ⬜ pending |
| 2-03-01 | 03 | 1 | PDF-03 | unit | `./vendor/bin/phpunit --filter BlankPdfDownloadTest` | ❌ W0 | ⬜ pending |
| 2-03-02 | 03 | 2 | PDF-03 | manual | browser | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/bootstrap.php` — PHPUnit + Brain Monkey bootstrap
- [ ] `phpunit.xml` — test suite config with `testsuite unit` definition
- [ ] `tests/Unit/PdfGeneratorTest.php` — stubs for PDF-01 and PDF-02
- [ ] `tests/Unit/BlankPdfDownloadTest.php` — stubs for PDF-03
- [ ] PHPUnit 9.x + Brain Monkey installed via `composer require --dev`

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Blank PDF downloads correctly in browser via shortcode link | PDF-03 | Requires browser + live WP instance to verify headers, file stream, and cleanup | Load page with `[membership_form download="blank"]`, click link, verify PDF opens/downloads, confirm no file left in uploads/ |
| Generated PDF readable in PDF viewer with correct field values | PDF-01 | Binary PDF output cannot be fully verified by PHPUnit alone | Submit test form, open generated PDF, verify all field values appear in correct positions |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
