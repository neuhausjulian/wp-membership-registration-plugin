---
phase: 03-email-notifications
plan: 01
subsystem: testing
tags: [phpunit, brain-monkey, mailer, tdd, email]

# Dependency graph
requires:
  - phase: 02-pdf-engine
    provides: PdfGenerator::generate() contract and Brain Monkey test infrastructure pattern
provides:
  - Failing PHPUnit test scaffold for Mailer class (6 tests, red phase)
  - Behavioral contract for Mailer::handle_submission() covering MAIL-01 and MAIL-02
affects: 03-02 (Mailer implementation must make these tests green)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - PHPUnit mock via createMock() injected into Mailer constructor (constructor injection for PdfGenerator)
    - Brain Monkey Functions\stubs() for WP functions in setUp(); expect() for assertion-bearing calls per test
    - wp_delete_file() expectation with once() to verify cleanup in finally block

key-files:
  created:
    - tests/Unit/Email/MailerTest.php
  modified: []

key-decisions:
  - "Removed error_log stub from setUp — it is a PHP internal, not patchwork-redefinable without patchwork.json config change; Mailer implementation will call error_log natively"
  - "Constructor injection chosen for PdfGenerator in Mailer — allows mock injection in tests without touching file system"

patterns-established:
  - "Pattern: inject PdfGenerator as optional constructor param (default null = new PdfGenerator()) for testability"
  - "Pattern: Brain Monkey expect('wp_delete_file')->once()->with(path) to assert cleanup in finally"

requirements-completed:
  - MAIL-01
  - MAIL-02

# Metrics
duration: 8min
completed: 2026-03-22
---

# Phase 3 Plan 01: Email Notifications - Mailer TDD Red Phase Summary

**Six failing PHPUnit tests establishing the behavioral contract for Mailer::handle_submission() — member send, admin send, email-field skip logic, and guaranteed temp-file cleanup via finally block**

## Performance

- **Duration:** 8 min
- **Started:** 2026-03-22T11:00:00Z
- **Completed:** 2026-03-22T11:08:00Z
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments
- Created `tests/Unit/Email/MailerTest.php` with 6 failing tests (all fail with `Class not found: WpMembershipRegistration\Email\Mailer`)
- Tests fully specify the contract for MAIL-01 (member email with PDF attachment) and MAIL-02 (admin email with PDF attachment)
- Tests cover member email skip when no email-type field in schema (admin still sends)
- Tests cover temp PDF cleanup in both success path and wp_mail() failure path (finally block)
- Constructor injection pattern established: Mailer accepts optional PdfGenerator to enable mock injection in tests

## Task Commits

Each task was committed atomically:

1. **Task 1: Write failing MailerTest.php (red phase)** - `f58dac2` (test)

## Files Created/Modified
- `tests/Unit/Email/MailerTest.php` - 6 failing tests covering MAIL-01 and MAIL-02 behavioral contract

## Decisions Made
- Removed `error_log` stub from setUp — PHP internals cannot be stubbed by Brain Monkey/Patchwork without adding `redefinable-internals` to patchwork.json; since Mailer will use `error_log()` natively (not in tests), no stub is needed in this red phase
- Constructor injection pattern for PdfGenerator: `__construct(?PdfGenerator $generator = null)` — keeps tests fast and focused, avoids DOMPDF filesystem calls in unit tests

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Removed error_log from Brain Monkey stubs**
- **Found during:** Task 1 (initial test run)
- **Issue:** `error_log` is a PHP internal function; Brain Monkey/Patchwork cannot stub it without `{"redefinable-internals": ["error_log"]}` in patchwork.json, causing all 6 tests to error immediately with `NotUserDefined` exception
- **Fix:** Removed `error_log` from `Functions\stubs()` in setUp(); the Mailer implementation will call it natively (no assertion on it in these tests)
- **Files modified:** tests/Unit/Email/MailerTest.php
- **Verification:** All 6 tests now error with correct class-not-found error instead of patchwork exception
- **Committed in:** f58dac2 (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 bug — incorrect stub target)
**Impact on plan:** Minimal. error_log behavior is not tested in these unit tests; the fix aligns with Brain Monkey's constraints. No scope change.

## Issues Encountered
- PHP is not installed locally; tests run inside the WordPress Docker container via `docker compose exec wordpress php vendor/bin/phpunit`

## Next Phase Readiness
- `tests/Unit/Email/MailerTest.php` is the authoritative specification for Wave 1
- Wave 1 (03-02) must create `src/Email/Mailer.php` that makes all 6 tests pass
- Constructor injection design is already locked by the test: `new Mailer($mock_generator)` — Mailer must accept optional PdfGenerator parameter

## Self-Check: PASSED

- `tests/Unit/Email/MailerTest.php`: FOUND
- `03-01-SUMMARY.md`: FOUND
- Commit f58dac2: FOUND

---
*Phase: 03-email-notifications*
*Completed: 2026-03-22*
