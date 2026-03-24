---
phase: 3
slug: email-notifications
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-22
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 9.6 + Brain Monkey 2.6 |
| **Config file** | `phpunit.xml` (project root) |
| **Quick run command** | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` |
| **Full suite command** | `vendor/bin/phpunit` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `vendor/bin/phpunit tests/Unit/Email/MailerTest.php`
- **After every plan wave:** Run `vendor/bin/phpunit`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** ~5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 3-01-01 | 01 | 0 | MAIL-01, MAIL-02 | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` | ❌ W0 | ⬜ pending |
| 3-02-01 | 02 | 1 | MAIL-01 | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` | ❌ W0 | ⬜ pending |
| 3-02-02 | 02 | 1 | MAIL-02 | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` | ❌ W0 | ⬜ pending |
| 3-02-03 | 02 | 1 | MAIL-01, MAIL-02 | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php` | ❌ W0 | ⬜ pending |
| 3-03-01 | 03 | 2 | MAIL-01, MAIL-02 | unit | `vendor/bin/phpunit` | ❌ W0 | ⬜ pending |
| 3-04-01 | 04 | 2 | CONF-05 | manual | See Manual-Only Verifications | manual-only | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/Email/MailerTest.php` — stubs covering MAIL-01 (member send, skip-when-no-email-field), MAIL-02 (admin send, always-sends), temp file cleanup

*`tests/Unit/` directory already exists; no new test infrastructure needed — Brain Monkey stubs suffice.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Test email button triggers `wp_ajax_wmr_send_test_email` and mail reaches Mailpit | CONF-05 | Requires live Docker stack + Mailpit; cannot be automated via PHPUnit without a running WordPress installation | Start Docker (`docker compose up -d`), open Email Settings tab, click "Send Test Email", confirm message appears in Mailpit UI at `localhost:8025` |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
