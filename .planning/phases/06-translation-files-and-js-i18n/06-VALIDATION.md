---
phase: 6
slug: translation-files-and-js-i18n
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-24
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9.6 + Brain Monkey 2.6 |
| **Config file** | `phpunit.xml` (project root) |
| **Quick run command** | `docker compose exec wordpress vendor/bin/phpunit --testsuite unit` |
| **Full suite command** | `docker compose exec wordpress vendor/bin/phpunit` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `docker compose exec wordpress vendor/bin/phpunit --testsuite unit`
- **After every plan wave:** Run `docker compose exec wordpress vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | I18N-04 | manual-only | `ls languages/*.pot` | ❌ W0 | ⬜ pending |
| 06-01-02 | 01 | 1 | I18N-05 | manual-only | `ls languages/*de_DE*` | ❌ W0 | ⬜ pending |
| 06-02-01 | 02 | 1 | I18N-06a | unit | `docker compose exec wordpress vendor/bin/phpunit --filter test_enqueue_calls_set_script_translations` | ❌ W0 | ⬜ pending |
| 06-02-02 | 02 | 1 | I18N-06b | unit | `docker compose exec wordpress vendor/bin/phpunit --filter test_admin_enqueue_calls_set_script_translations` | ❌ W0 | ⬜ pending |
| 06-02-03 | 02 | 1 | I18N-06c | manual-only | `ls languages/*de_DE*.json` | ❌ W0 | ⬜ pending |
| 06-02-04 | 02 | 1 | I18N-06d | unit | covered by enqueue test stubs | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/PluginTest.php` — add stub for `wp_set_script_translations` and tests asserting it's called for `wmr-form` handle (covers I18N-06a)
- [ ] `tests/Unit/SettingsPageTest.php` — add stub for `wp_set_script_translations` and tests asserting it's called for `wmr-admin-settings` handle (covers I18N-06b)
- [ ] `languages/` directory — must exist before POT generation step

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| `.pot` file contains all wrapped strings | I18N-04 | File generation output, not unit-testable | Run `wp i18n make-pot`, verify `languages/*.pot` exists and contains expected strings |
| `de_DE` `.po`/`.mo` render German in browser | I18N-05 | Integration: requires WP locale + browser | Set site to `de_DE`, visit admin settings and frontend form, confirm German text |
| JSON locale files exist for script handles | I18N-06c | Filesystem check after `make-json` | Run `ls languages/*de_DE*.json`, verify files for both script handles |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
