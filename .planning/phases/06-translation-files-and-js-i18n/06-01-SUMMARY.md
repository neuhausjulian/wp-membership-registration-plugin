---
phase: 06-translation-files-and-js-i18n
plan: 01
subsystem: js-i18n
tags: [i18n, javascript, wp.i18n, wp_set_script_translations, php, unit-tests]
dependency_graph:
  requires: []
  provides: [wp.i18n API in JS, wp_set_script_translations wiring, JS i18n unit tests]
  affects: [assets/js/form.js, assets/js/admin-settings.js, src/Plugin.php, src/Admin/SettingsPage.php]
tech_stack:
  added: [wp.i18n.__(), wp_set_script_translations()]
  patterns: [WordPress standard JS i18n via wp-i18n script dependency]
key_files:
  modified:
    - assets/js/form.js
    - assets/js/admin-settings.js
    - src/Plugin.php
    - src/Admin/SettingsPage.php
    - tests/Unit/PluginTest.php
  created:
    - tests/Unit/Admin/SettingsPageEnqueueTest.php
decisions:
  - wp.i18n.__() calls use exact English msgid strings matching PHP __() calls to ensure .pot consistency
  - WMR_PLUGIN_DIR constant used for languages path in both PHP files (already globally defined)
  - is_a() stubbed via Functions\when in PluginTest to avoid Brain Monkey PHP builtin limitation
metrics:
  duration: 177s
  completed_date: "2026-03-24"
  tasks_completed: 3
  files_modified: 5
  files_created: 1
---

# Phase 6 Plan 01: JS i18n Migration to wp.i18n.__() Summary

**One-liner:** Migrated 16 JS i18n strings from wp_localize_script pattern to WordPress-standard wp.i18n.__() API with wp_set_script_translations() wiring for both script handles.

## What Was Built

Replaced the Phase 5 interim `wmrForm.i18n.*` / `wmrSettings.i18n.*` pattern in both JS files with direct `wp.i18n.__()` calls. Wired `wp_set_script_translations()` in PHP for both `wmr-form` and `wmr-admin-settings` handles, added `wp-i18n` as a script dependency, and removed the `i18n` sub-objects from `wp_localize_script`. Added a new test file and extended `PluginTest` to verify the wiring.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Migrate JS files from wp_localize_script i18n to wp.i18n.__() | 383353c | assets/js/form.js, assets/js/admin-settings.js |
| 2 | Wire wp_set_script_translations() and remove i18n from wp_localize_script | 883476b | src/Plugin.php, src/Admin/SettingsPage.php |
| 3 | Add unit tests for wp_set_script_translations wiring | 495559c | tests/Unit/PluginTest.php, tests/Unit/Admin/SettingsPageEnqueueTest.php |

## Decisions Made

- **Exact msgid strings:** All `wp.i18n.__()` calls use character-for-character identical strings to the PHP `__()` calls removed from `wp_localize_script`. This is required so `wp-i18n-make-pot` and `wp i18n make-json` can match them correctly.
- **WMR_PLUGIN_DIR for languages path:** Both PHP files use `WMR_PLUGIN_DIR . 'languages'` (not `plugin_dir_path()`) since the constant is already defined globally and is the established pattern.
- **is_a() stubbing in PluginTest:** Brain Monkey cannot stub PHP built-in functions like `is_a()`. Used `Functions\when('is_a')->justReturn(true)` which Brain Monkey handles via its function mock layer.

## Verification Results

1. `form.js wmrForm.i18n.` count: 0 (was 6)
2. `admin-settings.js wmrSettings.i18n.` count: 0 (was 10)
3. `form.js wp.i18n.__` count: 6
4. `admin-settings.js wp.i18n.__` count: 10
5. `Plugin.php wp_set_script_translations` count: 1
6. `SettingsPage.php wp_set_script_translations` count: 1
7. `Plugin.php 'i18n'` key: absent
8. `SettingsPage.php 'i18n'` key: absent
9. `Plugin.php 'wp-i18n'` dep: present
10. `SettingsPage.php 'wp-i18n'` dep: present

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED
