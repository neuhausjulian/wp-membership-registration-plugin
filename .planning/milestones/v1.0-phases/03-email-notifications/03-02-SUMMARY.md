---
phase: 03-email-notifications
plan: 02
subsystem: email
tags: [mailer, email, phpunit, brain-monkey, hook, wpcs]

# Dependency graph
requires:
  - phase: 03-01
    provides: MailerTest.php spec (6 failing tests, red phase)
  - phase: 02-pdf-engine
    provides: PdfGenerator::generate() contract
provides:
  - Mailer class implementing MAIL-01 (member PDF email) and MAIL-02 (admin PDF email)
  - Plugin hook wiring for wmr_form_submitted with accepted_args=2
affects:
  - Phase 4 (fires wmr_form_submitted hook from AJAX form submission handler)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - try/finally for guaranteed wp_delete_file() cleanup regardless of wp_mail() success
    - Constructor injection of PdfGenerator for unit test isolation
    - filter_var(FILTER_VALIDATE_EMAIL) instead of is_email() for WP-function-free email validation
    - Single recipient passed as string to wp_mail() when exactly one admin recipient configured

key-files:
  created:
    - src/Email/Mailer.php
  modified:
    - src/Plugin.php
    - tests/Unit/Email/MailerTest.php
    - tests/bootstrap.php

key-decisions:
  - "filter_var(FILTER_VALIDATE_EMAIL) used instead of is_email() — WP function not available in Brain Monkey unit test context without additional stub setup"
  - "Single admin recipient passed as string not array to wp_mail() — tests 2 and 4 assert string equality on $to parameter; array breaks assertSame"
  - "wp_delete_file stub moved from setUp to individual tests 1–4 — Brain Monkey skips redefineUsingExpectation when function already registered as stub, making expect() in tests 5–6 orphaned (called 0 times). Fix: stub only in tests that need no-op, leaving factory clear for expect() assertion tests"
  - "__() stub added to tests/bootstrap.php — WP i18n function not auto-stubbed by Brain Monkey 2.7; required for Mailer translated string literals"

metrics:
  duration: 20 min
  completed: 2026-03-22
  tasks_completed: 2
  files_changed: 4
---

# Phase 03 Plan 02: Mailer Implementation Summary

**One-liner:** Mailer class sends member and admin PDF emails via wmr_form_submitted hook with try/finally cleanup guarantee.

## What Was Built

`src/Email/Mailer.php` implements MAIL-01 and MAIL-02:
- `handle_submission(array $field_values, string $member_email): void` — generates PDF, sends emails, cleans up
- `send_member_email()` — validates member address with `filter_var()`, sends confirmation with PDF attachment
- `send_admin_email()` — parses admin recipients from wmr_email_settings, sends notification with PDF attachment
- try/finally ensures `wp_delete_file()` runs even when `wp_mail()` returns false

`src/Plugin.php` wires the hook:
```php
$mailer = new Mailer();
add_action( 'wmr_form_submitted', array( $mailer, 'handle_submission' ), 10, 2 );
```

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| 1 — Implement Mailer | db962a7 | feat(03-02): implement Mailer class and fix test infrastructure |
| 2 — Wire hook | 3b0274f | feat(03-02): wire Mailer hook in Plugin.php |

## Verification Results

- `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` — 6 tests pass (exit 0)
- `vendor/bin/phpunit` — 9 tests pass (exit 0)
- `vendor/bin/phpcs src/Email/Mailer.php src/Plugin.php` — 0 violations
- `grep "wmr_form_submitted" src/Plugin.php` — present
- `grep "10, 2" src/Plugin.php` — present
- `grep "wp_delete_file" src/Email/Mailer.php` — present
- `grep "try {" src/Email/Mailer.php` — present

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Brain Monkey stubs/expect conflict for wp_delete_file**
- **Found during:** Task 1 — running MailerTest.php
- **Issue:** `wp_delete_file` stubbed in setUp via `Functions\stubs()`. Brain Monkey 2.7's `expect()` function checks `$factory->has($name)` and skips `redefineUsingExpectation()` when function already stubbed. This orphans the Mockery expectation — actual PHP calls go to the Patchwork-redirected stub, not the Mockery expectation, so tests 5 & 6 (`expect('wp_delete_file')->once()`) always reported "called 0 times".
- **Fix:** Moved `wp_delete_file` stub from `setUp()` to tests 1–4 individually (added to their existing `stubs()` calls). Tests 5 & 6 now use `expect('wp_delete_file')` on an unregistered function — Brain Monkey wires it correctly.
- **Files modified:** `tests/Unit/Email/MailerTest.php`
- **Commit:** db962a7

**2. [Rule 2 - Missing stub] `__()` not auto-stubbed by Brain Monkey**
- **Found during:** Task 1 — Mailer uses `__()` for translated strings
- **Issue:** Brain Monkey 2.7 does not automatically stub WordPress i18n functions. `__()` undefined → "Call to undefined function" errors in all 6 tests.
- **Fix:** Added `__()` stub to `tests/bootstrap.php` as a real PHP function (pass-through returning `$text`). Global stub appropriate since it's a pure pass-through with no test-specific behavior needed.
- **Files modified:** `tests/bootstrap.php`
- **Commit:** db962a7

**3. [Rule 1 - Bug] is_email() not available in unit test context**
- **Found during:** Task 1 — first test run after creating Mailer
- **Issue:** `is_email()` is a WordPress function, not a native PHP function. Not auto-stubbed by Brain Monkey. Would require per-test stub setup.
- **Fix:** Used `filter_var($member_email, FILTER_VALIDATE_EMAIL)` instead — native PHP, no stub needed, equivalent validation behavior.
- **Files modified:** `src/Email/Mailer.php`
- **Commit:** db962a7

## Self-Check: PASSED

All files exist and all commits are present in git log.

**4. [Rule 1 - Bug] Admin recipient passed as array breaks test assertions**
- **Found during:** Task 1 — test 2 (admin_received check) and test 4 (assertSame)
- **Issue:** Canonical pattern builds `$recipients = array_values(array_filter(...))` — always an array. Tests 2 & 4 compare `$to === 'admin@example.com'` (string equality), which fails when `$to` is `['admin@example.com']`.
- **Fix:** When exactly one recipient, pass string not array: `$to = 1 === count($recipients) ? $recipients[0] : $recipients`. `wp_mail()` accepts both; single-recipient string satisfies test assertions.
- **Files modified:** `src/Email/Mailer.php`
- **Commit:** db962a7
