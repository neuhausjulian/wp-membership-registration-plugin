---
phase: 5
slug: php-i18n-foundation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-24
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (version from composer.json) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `./vendor/bin/phpunit --filter {RelevantTestClass}` |
| **Full suite command** | `./vendor/bin/phpunit` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `./vendor/bin/phpunit --filter {RelevantTestClass}`
- **After every plan wave:** Run `./vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 05-01-01 | 01 | 1 | I18N-03 | unit | `./vendor/bin/phpunit --filter PluginTest` | ❌ W0 | ⬜ pending |
| 05-01-02 | 01 | 1 | I18N-01 | unit | `./vendor/bin/phpunit --filter SettingsPageTest` | ❌ W0 | ⬜ pending |
| 05-02-01 | 02 | 1 | I18N-01 | unit | `./vendor/bin/phpunit --filter SettingsPageTest` | ❌ W0 | ⬜ pending |
| 05-03-01 | 03 | 2 | I18N-02 | unit | `./vendor/bin/phpunit --filter FormRendererTest` | ✅ (update) | ⬜ pending |
| 05-03-02 | 03 | 2 | I18N-07 | unit | `./vendor/bin/phpunit --filter SettingsRegistrarTest` | ❌ W0 | ⬜ pending |
| 05-03-03 | 03 | 2 | I18N-07 | unit | `./vendor/bin/phpunit --filter PluginTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/PluginTest.php` — stubs for I18N-03 (load_plugin_textdomain on init) and I18N-07 (switch_to_locale for 'de'/'en')
- [ ] `tests/Unit/Admin/SettingsPageTest.php` — stubs for I18N-01 (admin strings through i18n functions)
- [ ] `tests/Unit/Admin/SettingsRegistrarTest.php` — extend existing to cover fallback_language sanitize and default
- [ ] Update `tests/Unit/Frontend/FormRendererTest.php::test_render_includes_consent_checkbox_when_consent_text_empty` — assert English default, not German

*Existing test infrastructure covers framework setup — no new framework install needed.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Admin page renders translated labels when locale is de_DE | I18N-01 | Requires WP admin visual confirmation | Switch site to de_DE, load settings page, verify all labels in German |
| Frontend form renders translated strings when locale is de_DE | I18N-02 | Requires frontend rendering with locale switch | Switch site to de_DE, load registration form, verify all labels/messages in German |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
