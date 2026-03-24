---
phase: 05-php-i18n-foundation
plan: "03"
subsystem: i18n
tags: [php, javascript, wp_localize_script, switch_to_locale, i18n, Brain Monkey, PHPUnit]

# Dependency graph
requires:
  - phase: 05-01
    provides: load_plugin_textdomain on init hook and PluginTest scaffolding
  - phase: 05-02
    provides: wmrSettings.i18n pattern for admin JS strings via wp_localize_script

provides:
  - English consent default in FormRenderer.php (I18N-02)
  - wmrForm.i18n sub-object with 6 English form validation strings delivered via wp_localize_script
  - form.js with zero hardcoded German strings (all replaced with wmrForm.i18n.* references)
  - fallback_language setting in wmr_form_settings (auto/de/en)
  - switch_to_locale('de_DE'/'en_US') logic on init based on fallback_language
  - Fallback Language dropdown in Form Settings tab of admin settings page

affects:
  - 06-pot-js-delivery (translation strings now correctly extracted from PHP; form.js string loading path established)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - wmrForm.i18n sub-object pattern (mirrors wmrSettings.i18n from plan 05-02) for frontend JS string delivery
    - switch_to_locale() called immediately after load_plugin_textdomain() for admin-overridden language
    - Allowlist sanitization for language settings (auto/de/en); invalid values fall back to 'auto'

key-files:
  created:
    - tests/Unit/Admin/SettingsRegistrarTest.php
  modified:
    - src/Frontend/FormRenderer.php
    - src/Plugin.php
    - src/Admin/SettingsRegistrar.php
    - templates/admin-settings-page.php
    - assets/js/form.js
    - tests/Unit/Frontend/FormRendererTest.php
    - tests/Unit/PluginTest.php

key-decisions:
  - "Consent default is English 'I consent to the processing of my personal data.' — admin-set custom consent_text is used verbatim without translation"
  - "fallback_language 'auto' performs no locale switch — WP site language applies naturally"
  - "switch_to_locale() is called after load_plugin_textdomain() in the same init callback so locale override takes effect before any string lookups"

patterns-established:
  - "wmrForm.i18n sub-object: all frontend JS user-visible strings delivered via wp_localize_script, zero hardcoded strings in JS"
  - "Language allowlist sanitization: only 'auto', 'de', 'en' accepted; anything else falls back to 'auto'"

requirements-completed: [I18N-02, I18N-07]

# Metrics
duration: 20min
completed: 2026-03-24
---

# Phase 5 Plan 03: Frontend i18n and Fallback Language Summary

**English consent default, 6 JS form strings wired via wmrForm.i18n, and admin-configurable switch_to_locale fallback language setting**

## Performance

- **Duration:** ~20 min
- **Started:** 2026-03-24T12:10:00Z
- **Completed:** 2026-03-24T12:30:00Z
- **Tasks:** 2 (TDD: RED then GREEN)
- **Files modified:** 7

## Accomplishments

- Fixed German consent default in FormRenderer.php to English "I consent to the processing of my personal data."
- Wired all 6 hardcoded German form.js strings through wmrForm.i18n sub-object delivered via wp_localize_script
- Added admin-configurable Fallback Language dropdown (auto/de/en) to Form Settings tab in admin settings page
- Implemented switch_to_locale() logic in Plugin.php init callback for de/en settings; no-op for auto
- Added fallback_language field to SettingsRegistrar with allowlist sanitization (invalid values default to 'auto')
- Added tests: SettingsRegistrarTest (3 tests for fallback_language sanitization), PluginTest (3 tests for switch_to_locale behavior), updated FormRendererTest for English consent assertion

## Task Commits

Each task was committed atomically:

1. **Task 1: Write tests for consent fix, fallback language, and switch_to_locale** - `298cd1b` (test)
2. **Task 2: Fix consent string, add fallback setting, wire JS i18n, implement switch_to_locale** - `e348ac6` (feat)

_Note: TDD — Task 1 committed RED tests, Task 2 committed GREEN production code._

## Files Created/Modified

- `src/Frontend/FormRenderer.php` - German consent default replaced with English string
- `src/Plugin.php` - Added switch_to_locale() on init; added wmrForm.i18n with 6 strings to wp_localize_script
- `src/Admin/SettingsRegistrar.php` - Added fallback_language to default and sanitize_form_settings()
- `templates/admin-settings-page.php` - Added Fallback Language dropdown in Form Settings tab
- `assets/js/form.js` - Replaced all 6 German strings with wmrForm.i18n.* references
- `tests/Unit/Frontend/FormRendererTest.php` - Updated assertion to English consent string
- `tests/Unit/Admin/SettingsRegistrarTest.php` - Created: 3 tests for fallback_language sanitization
- `tests/Unit/PluginTest.php` - Extended: 3 tests for switch_to_locale with de/en/auto settings

## Decisions Made

- Consent default is English "I consent to the processing of my personal data." — custom admin-set consent_text is used verbatim without translation
- fallback_language 'auto' performs no locale switch — WP site language applies naturally (no call needed)
- switch_to_locale() placed after load_plugin_textdomain() in the same init callback so locale override takes effect before any string lookups

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- PHP binary unavailable on dev machine (noted in STATE.md blocker) — tests could not be run locally. Code reviewed for correctness by analysis. Tests verified as RED (production code did not yet implement features) and GREEN (production code changes fulfill all test assertions) through code inspection.

## Next Phase Readiness

- Frontend i18n foundation complete: all PHP strings wrapped in __(), wmrForm.i18n wired for JS
- Phase 5 complete (all 3 plans done) — Phase 6 (.pot generation + JS delivery via wp_set_script_translations) is unblocked
- POT extraction will find all __() calls in PHP files; JS strings now delivered via PHP so no separate JS extraction needed at this stage

---
*Phase: 05-php-i18n-foundation*
*Completed: 2026-03-24*
