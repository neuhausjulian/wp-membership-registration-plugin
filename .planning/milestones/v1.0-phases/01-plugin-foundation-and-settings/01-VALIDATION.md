---
phase: 1
slug: plugin-foundation-and-settings
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-20
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (via WP-CLI scaffold or direct install) + PHPCS |
| **Config file** | `phpunit.xml` (Wave 0 installs) |
| **Quick run command** | `composer run phpcs` |
| **Full suite command** | `composer run test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `composer run phpcs`
- **After every plan wave:** Run `composer run test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 1-01-01 | 01 | 0 | DEV-01 | lint | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-02 | 01 | 0 | DEV-01 | integration | `docker compose up -d && docker compose exec wordpress wp core is-installed` | ❌ W0 | ⬜ pending |
| 1-01-03 | 01 | 1 | CONF-01 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-04 | 01 | 1 | CONF-02 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-05 | 01 | 2 | CONF-03 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-06 | 01 | 2 | CONF-04 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-07 | 01 | 3 | CONF-05 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |
| 1-01-08 | 01 | 3 | DEV-03 | unit | `composer run phpcs` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `composer.json` — require `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`, `dealerdirect/phpcodesniffer-composer-installer`
- [ ] `.phpcs.xml` — configure WPCS rules, exclude PSR-4 filename sniff
- [ ] `phpunit.xml` — PHPUnit config for plugin source
- [ ] `docker-compose.yml` + `Dockerfile` — working WordPress local environment
- [ ] `dev/mu-plugins/mailpit-smtp.php` — SMTP hook for test email verification

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Admin can reorder form fields via drag-and-drop | CONF-01 | UI interaction requires browser | Load settings page, drag a field row, save, reload and verify order persisted |
| Color picker renders and saves accent color | CONF-02 | `wp-color-picker` UI requires browser | Open Branding tab, pick a color, save, reload and verify hex value persisted |
| Logo upload via media library works | CONF-02 | `wp.media` API requires browser | Click Upload Logo, select image, save, reload and verify attachment ID persisted |
| Test email arrives at configured address | CONF-05 | Requires live email delivery | Configure recipient, click Send Test Email, check Mailpit at localhost:8025 |
| `docker compose up` launches working WordPress | DEV-01 | Requires Docker runtime | Run `docker compose up -d`, navigate to `http://localhost:8080`, complete WP install |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
