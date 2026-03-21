---
phase: 01-plugin-foundation-and-settings
plan: "01"
subsystem: infra
tags: [composer, phpcs, wpcs, docker, mailpit, wordpress, php]

# Dependency graph
requires: []
provides:
  - composer.json with PHPCS/WPCS dev dependencies (squizlabs/php_codesniffer ^3.8, wp-coding-standards/wpcs ^3.0, dealerdirect/phpcodesniffer-composer-installer ^1.0)
  - .phpcs.xml ruleset enforcing WordPress standard with PSR-4 filename exclusions and text domain wp-membership-registration
  - docker-compose.yml with four services: wordpress:latest, mysql:8.0, axllent/mailpit, adminer
  - dev/mu-plugins/mailpit-smtp.php routing wp_mail() through Mailpit SMTP at mailpit:1025
  - .gitignore excluding vendor/
affects:
  - 01-02-plugin-skeleton
  - all subsequent plans (PHPCS linting; Docker dev environment)

# Tech tracking
tech-stack:
  added:
    - squizlabs/php_codesniffer ^3.8
    - wp-coding-standards/wpcs ^3.0
    - dealerdirect/phpcodesniffer-composer-installer ^1.0
    - axllent/mailpit (Docker service)
    - mysql:8.0 (Docker service)
    - adminer (Docker service)
  patterns:
    - Composer dev dependencies for PHPCS/WPCS — reproducible across all contributor machines via single `composer install`
    - dealerdirect auto-installer removes manual `--config-set installed_paths` step
    - mu-plugin phpmailer_init hook for zero-config SMTP capture in Docker (no WP admin plugin required)
    - PSR-4 filename sniffs excluded in .phpcs.xml to allow class files named by class name (not class-*.php)

key-files:
  created:
    - composer.json
    - .phpcs.xml
    - .gitignore
    - docker-compose.yml
    - dev/mu-plugins/mailpit-smtp.php
  modified: []

key-decisions:
  - "Used mu-plugin phpmailer_init hook for SMTP relay rather than WORDPRESS_CONFIG_EXTRA — WORDPRESS_CONFIG_EXTRA defines constants only and does not hook phpmailer_init directly"
  - "Used wordpress:latest (not pinned) — pin to specific version before v1 release"
  - "Excluded WordPress.Files.FileName.NotHyphenatedLowercase and WordPress.Files.FileName.InvalidClassFileName from PHPCS to allow PSR-4 PascalCase filenames in src/"
  - "vendor/bin/phpcs populated by running composer install — requires PHP/Composer locally (or docker run composer:latest)"

patterns-established:
  - "Pattern: Docker dev environment — docker compose up -d starts WP at 8080, Mailpit at 8025, Adminer at 8081"
  - "Pattern: PHPCS lint — vendor/bin/phpcs --standard=.phpcs.xml src/ wp-membership-registration.php"
  - "Pattern: SMTP in dev — phpmailer_init mu-plugin, no WP admin config required"

requirements-completed:
  - DEV-01
  - DEV-03

# Metrics
duration: 2min
completed: 2026-03-21
---

# Phase 1 Plan 01: Dev Tooling Foundation Summary

**Composer-managed PHPCS/WPCS linting and Docker Compose environment with WordPress, MySQL, Mailpit, and Adminer wired for zero-click SMTP capture via phpmailer_init mu-plugin**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-21T14:20:05Z
- **Completed:** 2026-03-21T14:22:10Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments

- Authored `composer.json` with exact PHPCS/WPCS dev dependency versions and dealerdirect auto-installer; `vendor/bin/phpcs` is populated on `composer install`
- Authored `.phpcs.xml` enforcing WordPress standard with PSR-4 filename exclusion, text domain `wp-membership-registration`, and PHP 8.0+ minimum
- Authored `docker-compose.yml` with all four services (wordpress, db, mailpit, adminer) on their designated ports with plugin source and mu-plugins volumes mounted
- Created `dev/mu-plugins/mailpit-smtp.php` hooking `phpmailer_init` to route all `wp_mail()` calls through Mailpit SMTP at `mailpit:1025` — no WP admin plugin needed

## Task Commits

Each task was committed atomically:

1. **Task 1: Composer setup with PHPCS/WPCS dev dependencies** - `c54badb` (chore)
2. **Task 2: Docker Compose environment with Mailpit SMTP mu-plugin** - `81e8709` (chore)

## Files Created/Modified

- `composer.json` - Dev dependency definitions: phpcs ^3.8, wpcs ^3.0, dealerdirect ^1.0; PSR-4 autoload for src/; phpcs script
- `.phpcs.xml` - PHPCS ruleset: WordPress standard, PSR-4 filename exclusions, text domain wp-membership-registration, testVersion 8.0-
- `.gitignore` - Excludes vendor/ from version control
- `docker-compose.yml` - Four-service local dev environment on ports 8080/8025/1025/8081 with plugin and mu-plugins volume mounts
- `dev/mu-plugins/mailpit-smtp.php` - phpmailer_init hook routing wp_mail() to Mailpit at mailpit:1025

## Decisions Made

- **mu-plugin over WORDPRESS_CONFIG_EXTRA for SMTP:** The RESEARCH.md documents that `WORDPRESS_CONFIG_EXTRA` injects PHP constants into wp-config.php but does NOT directly configure `wp_mail()` — which uses PHPMailer. The `phpmailer_init` hook in a mu-plugin is the correct zero-plugin mechanism. This matches the plan's explicit requirement (and Pitfall 6 warning).
- **wordpress:latest (unpinned):** Pin to a specific version before v1.0 release to ensure reproducibility; unpinned during development for easy WP updates.
- **PSR-4 filename sniffs excluded:** The project uses Composer PSR-4 autoloading with `ClassName.php` filenames. Two WPCS sniffs enforcing `class-my-class.php` naming are excluded to avoid false positives on valid PSR-4 code.

## Deviations from Plan

### Environment Constraint

**composer install not executed in this environment**
- **Found during:** Task 1 verification
- **Issue:** Neither PHP/Composer nor the Docker daemon were available in the execution environment (`composer: command not found`; Docker socket at `/home/jean-luc/.rd/docker.sock` is not running)
- **Impact:** `vendor/bin/phpcs` does not yet exist on disk
- **Resolution:** All files are correctly authored. Any developer running `composer install` (or `docker run --rm -v "$(pwd):/app" -w /app composer:latest install`) will populate `vendor/bin/phpcs` immediately
- **Not a code deviation:** The `composer.json` content matches the plan exactly; this is an execution environment constraint only

---

**Total deviations:** 0 code deviations (1 environment constraint — composer install not executable without PHP/Docker daemon)
**Impact on plan:** Files are correctly authored per spec. `vendor/bin/phpcs` will exist after first `composer install`.

## Issues Encountered

None beyond the environment constraint documented above.

## User Setup Required

None — no external service configuration required. Developers run `composer install` and `docker compose up -d` as documented.

## Next Phase Readiness

- PHPCS/WPCS tooling ready: `composer install` populates `vendor/bin/phpcs`
- Docker environment ready: `docker compose up -d` starts WordPress at 8080, Mailpit at 8025, Adminer at 8081
- SMTP capture ready: `dev/mu-plugins/mailpit-smtp.php` auto-loads via Docker volume mount; no WP admin steps needed
- Phase 1 Plan 02 (plugin skeleton and main file) can proceed

## Self-Check: PASSED

All created files verified to exist on disk. Both task commits verified in git log.

- FOUND: composer.json
- FOUND: .phpcs.xml
- FOUND: .gitignore
- FOUND: docker-compose.yml
- FOUND: dev/mu-plugins/mailpit-smtp.php
- FOUND: 01-01-SUMMARY.md
- FOUND: commit c54badb (Task 1)
- FOUND: commit 81e8709 (Task 2)

---
*Phase: 01-plugin-foundation-and-settings*
*Completed: 2026-03-21*
