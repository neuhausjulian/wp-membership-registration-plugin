---
phase: 05-php-i18n-foundation
plan: "01"
subsystem: i18n
tags: [wordpress, php, i18n, load_plugin_textdomain, brain-monkey, tdd]

# Dependency graph
requires: []
provides:
  - "languages/ directory in plugin root (required for .mo file loading)"
  - "load_plugin_textdomain() registered on 'init' hook with correct domain and path"
  - "PluginTest.php with 3 tests covering I18N-03"
affects:
  - "05-02 (string wrapping) — depends on text domain being registered"
  - "05-03 (fallback locale) — hooks onto the same init registration"
  - "06 (pot + JS delivery) — needs languages/ dir and textdomain registered"

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "TDD red-green for WordPress hook registration via Brain Monkey Actions\\expectAdded + Functions\\expect"
    - "Capture add_action callbacks via Functions\\when('add_action')->alias() to test init callbacks"
    - "Use Mockery::on() matcher to assert path suffix without reference captures (avoids Patchwork closure parsing issues)"

key-files:
  created:
    - languages/.gitkeep
    - tests/Unit/PluginTest.php
  modified:
    - src/Plugin.php

key-decisions:
  - "Test 1 uses Actions\\expectAdded('init') with addToAssertionCount(1) to satisfy PHPUnit's risky-test check while Brain Monkey validates at tearDown"
  - "Tests 2 and 3 capture the init callback via Functions\\when('add_action')->alias() rather than pre-stubbing add_action, allowing Functions\\expect() to assert load_plugin_textdomain arguments"
  - "Mockery::on() closure used instead of reference capture in test 3 — avoids Patchwork code-manipulation ParseError on closures with use(&$var) or multi-param lists"
  - "load_plugin_textdomain() placed at TOP of Plugin::register() before all other hooks, consistent with plan spec"

patterns-established:
  - "Plugin hook registration tests: stub add_action via when()->alias() to capture hook callbacks for direct invocation"
  - "Avoid use(&$ref) inside closures passed to Functions\\when()->alias() — Patchwork cannot parse them reliably"

requirements-completed: [I18N-03]

# Metrics
duration: 5min
completed: 2026-03-24
---

# Phase 5 Plan 01: Text Domain Registration Summary

**`load_plugin_textdomain('wp-membership-registration')` hooked on `init` with `languages/` directory created and covered by 3 Brain Monkey unit tests**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-24T12:00:12Z
- **Completed:** 2026-03-24T12:05:46Z
- **Tasks:** 2 (Task 1 TDD RED + Task 2 GREEN)
- **Files modified:** 3

## Accomplishments

- Created `languages/` directory with `.gitkeep` so the repo tracks the dir and WordPress can load `.mo` files
- Added `add_action('init', static function() { load_plugin_textdomain(...); })` at the top of `Plugin::register()`
- Implemented `tests/Unit/PluginTest.php` with 3 tests: hook registration, text domain + args, and languages path suffix assertion
- Full suite: 24 tests, 0 failures, 0 errors

## Task Commits

Each task was committed atomically:

1. **Task 1 (RED): Create PluginTest.php and languages/.gitkeep** — `bfc81dd` (test)
2. **Task 2 (GREEN): Add load_plugin_textdomain() to Plugin::register()** — `8d442f6` (feat)

## Files Created/Modified

- `languages/.gitkeep` — Empty sentinel file that ensures the `languages/` directory is tracked by git and present for `.mo` file loading
- `tests/Unit/PluginTest.php` — 3 unit tests for I18N-03: init hook registration, text domain correctness, and `/languages` path suffix
- `src/Plugin.php` — Added `add_action('init', ...)` with `load_plugin_textdomain()` at the top of `register()`

## Decisions Made

- Used `Functions\when('add_action')->alias()` instead of `Functions\stubs(['add_action' => true])` in tests 2 and 3 so the init callback can be captured and invoked separately, allowing `Functions\expect('load_plugin_textdomain')` to assert arguments cleanly.
- Used `\Mockery::on()` closure matcher in test 3 rather than reference capture (`use (&$var)`) to avoid a Patchwork code-manipulation `ParseError` that occurs on multi-parameter closure argument lists.
- `addToAssertionCount(1)` added to test 1 so PHPUnit does not flag the Brain Monkey expectAdded pattern as a "risky test" with no assertions.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Reworked Brain Monkey stub/expect ordering to avoid conflicts**
- **Found during:** Task 2 (GREEN phase)
- **Issue:** `Functions\stubs(['load_plugin_textdomain' => true])` pre-empted `Functions\expect()`, causing tests 2 and 3 to report 0 calls even after Plugin.php was correct. Additionally Patchwork threw `ParseError` on closures with `use (&$ref)` or multi-parameter argument lists.
- **Fix:** Removed `load_plugin_textdomain` from `stubs()`, switched to `when()->alias()` pattern for capturing hooks, and replaced reference capture with `\Mockery::on()` for the path assertion.
- **Files modified:** tests/Unit/PluginTest.php
- **Verification:** All 3 PluginTest tests pass; full suite 24/24 green.
- **Committed in:** `8d442f6` (Task 2 commit, updated test alongside Plugin.php)

---

**Total deviations:** 1 auto-fixed (Rule 1 - Bug: Brain Monkey stub/expect ordering)
**Impact on plan:** Essential for test correctness. No scope creep.

## Issues Encountered

- Patchwork (Brain Monkey dependency) cannot parse closures with `use (&$ref)` or multi-parameter argument lists passed to `Functions\when()->alias()`. Resolved by using `\Mockery::on()` matcher and property-based capture via `$self = $this`.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Text domain foundation is complete. Plan 05-02 (wrap PHP strings with `__()` / `esc_html__()`) can start immediately.
- Plan 05-03 (fallback locale / `switch_to_locale()` logic) depends on this init hook.
- `languages/` directory is in place for Phase 6 `.po`/`.mo` file delivery.

---
*Phase: 05-php-i18n-foundation*
*Completed: 2026-03-24*
