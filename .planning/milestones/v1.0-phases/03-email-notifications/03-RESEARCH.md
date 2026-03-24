# Phase 3: Email Notifications - Research

**Researched:** 2026-03-22
**Domain:** WordPress `wp_mail()`, action hooks, settings page PHP template extension
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Submission hook design**
- Phase 3 uses a WordPress action hook as the trigger: `do_action('wmr_form_submitted', array $field_values, string $member_email)`
- Phase 3 registers a listener on this hook (`add_action('wmr_form_submitted', [$mailer, 'handle_submission'])`)
- Phase 4 fires the hook from the AJAX form submission handler — this keeps Mailer and Form Handler decoupled
- Hook signature: `(array $field_values, string $member_email)` — explicit, typed, minimal

**Member email identification**
- Mailer scans `FieldSchema::decode()` and uses the value of the **first field with `type === 'email'`** from the submitted `$field_values` array
- If no email-type field exists in the schema: skip the member email entirely (do not send), but **display an inline warning on the Email Settings tab**
- Warning text (inline on Email Settings tab, read-only): "No email-type field is configured in the form — members will not receive a confirmation email. Add an email-type field on the Form Fields tab."
- Admin email always sends regardless of whether member email can be identified

**Email content**
- Subject lines and body text are **hardcoded for v1**, translatable via WordPress i18n (`__()`, `_e()`)
- **Member email** (to the member):
  - Subject: `__('Your registration form', 'wp-membership-registration')`
  - Body: `__('Thank you for your registration. Please find your completed membership form attached.', 'wp-membership-registration')`
- **Admin email** (to all configured recipients):
  - Subject: `__('New membership registration received', 'wp-membership-registration')`
  - Body: `sprintf(__('A new membership registration was submitted. The completed form is attached.', 'wp-membership-registration'))`
- Both emails have the generated PDF as an attachment
- Sender name and address: WordPress site defaults (whatever is configured in Settings > General — no extra config)
- **Read-only preview boxes** on the Email Settings tab show both email templates (subject + body). Two static sections: "Member email" and "Admin email". Clearly labeled as previews, not editable fields.

**Failure behavior**
- Form submission always succeeds regardless of email delivery outcome — email failure never blocks a registration
- `wp_mail()` failures are logged via `error_log()` with context (which email failed, to which address)
- No additional admin notification for failures in v1 — WP_DEBUG_LOG is sufficient
- Temp PDF file (`sys_get_temp_dir()`) is deleted via `wp_delete_file()` after email sending, whether emails succeeded or failed

**CONF-05 (test email)**
- Already fully implemented in Phase 1 (`wp_ajax_wmr_send_test_email` in `AjaxHandlers.php`)
- Phase 3 verifies it works end-to-end with the Mailpit Docker setup — no new code needed for CONF-05

### Claude's Discretion
- Exact HTML vs plain-text format of the email body (plain text is simplest)
- Whether to use `wp_mail()` directly in Mailer or a thin wrapper method
- Class name and file location for the new Mailer class (e.g., `src/Email/Mailer.php`)
- Whether `wmr_form_submitted` is documented as a plugin hook in an inline docblock

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| MAIL-01 | Member receives the generated PDF as an email attachment immediately after submitting the form | `wp_mail()` with `$attachments` parameter; PDF temp path from `PdfGenerator::generate()`; member address resolved from first `type==='email'` field in `$field_values` |
| MAIL-02 | All configured admin recipient(s) receive a copy of the generated PDF by email after each submission | Same `wp_mail()` call pattern as `AjaxHandlers::handle_send_test_email()`; reads `wmr_email_settings['recipients']` option; admin send always fires |
| CONF-05 | Admin can send a test email from settings page to verify SMTP delivery | Already implemented in `AjaxHandlers::handle_send_test_email()`; Phase 3 task is verification only, no new code |
</phase_requirements>

---

## Summary

Phase 3 is the thinnest phase in the project. The core infrastructure is already built: `wp_mail()` works (Phase 1 test email proves it), DOMPDF generates PDFs to temp files (Phase 2), and the `wmr_email_settings` option stores recipients. What Phase 3 adds is a single new `Mailer` class that wires them together — generate PDF, send to member, send to admins, clean up temp file — plus a UI extension to `admin-settings-page.php` that shows an inline warning and read-only email preview boxes on the Email Settings tab.

The hook-based decoupling (`wmr_form_submitted`) means Phase 3 produces a fully testable listener that can be exercised without a frontend form. Phase 4 simply fires the hook from the AJAX handler. All email logic is therefore confined to `src/Email/Mailer.php`.

The two non-trivial decisions for the planner are: (1) where in `handle_submission()` to build the `$attachments` array and clean up the temp file, ensuring cleanup happens even when `wp_mail()` fails; and (2) how to render the read-only preview boxes in the PHP template cleanly without duplicating the hardcoded strings.

**Primary recommendation:** Implement `src/Email/Mailer.php`, register its hook in `Plugin::register()`, extend `templates/admin-settings-page.php` with the warning notice and preview boxes, then verify CONF-05 end-to-end in Mailpit. No external libraries, no new options.

---

## Standard Stack

### Core
| Library / API | Version | Purpose | Why Standard |
|---------------|---------|---------|--------------|
| `wp_mail()` | WP 6.0+ | Send email with optional attachments | WordPress-native; routes through PHPMailer configured by the site's SMTP plugin or mu-plugin |
| `PdfGenerator::generate()` | Phase 2 | Produce temp PDF for attachment | Already implemented and tested; returns absolute path |
| WordPress Options API | WP 6.0+ | Read `wmr_email_settings` for recipients | Established in Phase 1 |
| WordPress i18n (`__()`) | WP 6.0+ | Translatable subject/body strings | Established project pattern |

### Supporting
| API | Purpose | When to Use |
|-----|---------|-------------|
| `FieldSchema::decode()` | Read admin-configured field definitions to find `type === 'email'` field | Called inside Mailer to resolve member email address from submitted values |
| `wp_delete_file()` | Delete temp PDF after sending | Must be used instead of bare `unlink()` — WPCS requirement established in Phase 2 |
| `error_log()` | Log `wp_mail()` failures | Called when either send returns false |
| `do_action()` / `add_action()` | Hook fire/listen | Plugin.php fires hook; Mailer listens |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `wp_mail()` directly | PHPMailer directly | `wp_mail()` is the correct WP abstraction; direct PHPMailer bypasses filters |
| Inline hardcoded strings | Stored email templates option | v1 scope says hardcoded; template system is v2 scope |
| `error_log()` | WP admin notice on failure | email failure must not block submission — no synchronous admin notice possible |

**No new Composer packages required.** All dependencies are already in the project.

---

## Architecture Patterns

### Recommended Project Structure Addition
```
src/
├── Email/
│   └── Mailer.php       # New — handles wmr_form_submitted hook
├── Admin/
│   └── AjaxHandlers.php # Unchanged — CONF-05 already here
├── Plugin.php           # Add add_action('wmr_form_submitted', ...) in register()
templates/
└── admin-settings-page.php  # Extend Email Settings tab with warning + preview boxes
```

### Pattern 1: Mailer::handle_submission() Structure

**What:** Single public method that receives `$field_values` and `$member_email`, calls `PdfGenerator::generate()`, sends both emails, then cleans up the temp file unconditionally via `try/finally`.

**When to use:** Any time the hook fires. The `try/finally` block guarantees temp file cleanup even if either `wp_mail()` call throws or returns false.

**Example:**
```php
// Source: wp_mail() docs — https://developer.wordpress.org/reference/functions/wp_mail/
public function handle_submission( array $field_values, string $member_email ): void {
    $generator = new PdfGenerator();
    $pdf_path  = $generator->generate( $field_values );

    try {
        $this->send_member_email( $member_email, $pdf_path );
        $this->send_admin_email( $pdf_path );
    } finally {
        wp_delete_file( $pdf_path );
    }
}
```

### Pattern 2: wp_mail() with $attachments

**What:** `wp_mail()` fourth parameter is `$attachments` — an array of absolute file paths. No base64 encoding needed; WordPress/PHPMailer handles MIME attachment encoding internally.

**When to use:** Whenever sending a PDF attached to an email.

**Example:**
```php
// Source: https://developer.wordpress.org/reference/functions/wp_mail/
$sent = wp_mail(
    $to,          // string|string[]
    $subject,     // string
    $message,     // string (plain text or HTML)
    array(),      // $headers — empty = site defaults
    array( $pdf_path )  // $attachments — absolute paths
);
if ( ! $sent ) {
    error_log( 'wmr: wp_mail() failed sending member email to ' . $to );
}
```

### Pattern 3: Recipient Parsing (established in AjaxHandlers)

**What:** The `wmr_email_settings['recipients']` option is a newline-delimited string. Must be exploded, sanitized, and filtered before passing to `wp_mail()`.

**When to use:** Every time admin recipients are needed — Mailer must use the same pattern as the existing test email handler.

**Example:**
```php
// Source: src/Admin/AjaxHandlers.php::handle_send_test_email() (existing code)
$settings   = get_option( 'wmr_email_settings', array() );
$raw        = $settings['recipients'] ?? '';
$recipients = array_values(
    array_filter(
        array_map(
            'sanitize_email',
            explode( "\n", $raw )
        )
    )
);
```

### Pattern 4: Hook Registration in Plugin.php

**What:** New `add_action` call in `Plugin::register()` following the existing pattern.

**Example:**
```php
// Source: src/Plugin.php — existing pattern
$mailer = new \WpMembershipRegistration\Email\Mailer();
add_action( 'wmr_form_submitted', array( $mailer, 'handle_submission' ), 10, 2 );
```

Note: priority `10`, `$accepted_args` **must be 2** since the hook passes two arguments. Omitting `$accepted_args` defaults to 1, which silently drops `$member_email`.

### Pattern 5: Inline Warning in PHP Template

**What:** Conditional PHP block inserted before the recipients textarea in `admin-settings-page.php`. Reads `wmr_field_schema` option and checks whether any field has `type === 'email'` using `FieldSchema::decode()`.

**Example:**
```php
// Source: FieldSchema::decode() pattern established in Phase 1
$field_schema = get_option( 'wmr_field_schema', '[]' );
$fields       = \WpMembershipRegistration\Util\FieldSchema::decode( $field_schema );
$has_email    = ! empty( array_filter( $fields, fn( $f ) => ( $f['type'] ?? '' ) === 'email' ) );

if ( ! $has_email ) : ?>
    <div class="notice notice-warning inline">
        <p><?php esc_html_e( 'No email-type field is configured in the form — members will not receive a confirmation email. Add an email-type field on the Form Fields tab.', 'wp-membership-registration' ); ?></p>
    </div>
<?php endif; ?>
```

### Anti-Patterns to Avoid

- **Passing `$accepted_args` = 1 (or omitting it) on a 2-arg hook:** `add_action('wmr_form_submitted', ..., 10, 1)` silently receives only `$field_values`; `$member_email` becomes an empty string. Always pass `10, 2`.
- **Cleaning up temp file before sending both emails:** If cleanup happens between member send and admin send, the admin attachment path is gone. Cleanup must be in `finally` after both sends.
- **Calling `unlink()` instead of `wp_delete_file()`:** WPCS flags bare `unlink()` as a violation. Established in Phase 2.
- **Blocking form submission on email failure:** `handle_submission()` must never throw or return a non-void value that the caller checks. Email delivery outcome is logged, not propagated.
- **Hard-coding recipient list in Mailer:** Always read from `get_option('wmr_email_settings', [])` — same option used by the test email handler.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| MIME attachment encoding | Custom base64/MIME builder | `wp_mail()` `$attachments` param | PHPMailer handles all MIME encoding; absolute path is sufficient |
| SMTP delivery | Direct socket SMTP client | `wp_mail()` | Routes through site's configured PHPMailer/SMTP mu-plugin |
| Temp file naming | Custom UUID scheme | `wp_generate_uuid4()` already used in `PdfGenerator::write_temp_file()` | Consistent, already tested |
| Email address validation | Custom regex | `sanitize_email()` | WordPress-native, handles edge cases |

---

## Common Pitfalls

### Pitfall 1: $accepted_args Missing on Two-Argument Hook
**What goes wrong:** `add_action('wmr_form_submitted', [$mailer, 'handle_submission'])` defaults to `$accepted_args=1`. PHP calls `handle_submission($field_values)` with only one argument; `$member_email` is never passed, so member email silently sends to an empty address.
**Why it happens:** WordPress default for `$accepted_args` is 1, not "all args."
**How to avoid:** Always specify `add_action('wmr_form_submitted', [$mailer, 'handle_submission'], 10, 2)`.
**Warning signs:** Test email arrives at admin but not member; `wp_mail()` returns false with empty `$to`.

### Pitfall 2: Temp File Leak When First wp_mail() Fails
**What goes wrong:** If `send_member_email()` throws an exception (rare but possible), the code never reaches `send_admin_email()` or `wp_delete_file()`, leaving orphaned temp files in `sys_get_temp_dir()`.
**Why it happens:** Sequential sends without a cleanup guarantee.
**How to avoid:** Wrap both sends in `try/finally`; call `wp_delete_file()` in `finally`.
**Warning signs:** Growing number of `wmr-*.pdf` files in `/tmp` over time.

### Pitfall 3: wp_mail() Returns False Silently
**What goes wrong:** `wp_mail()` returns `false` when PHPMailer encounters a delivery error, but does not throw. If the return value is not checked, failures go unnoticed.
**Why it happens:** `wp_mail()` swallows PHPMailer exceptions internally.
**How to avoid:** Always capture the return value and call `error_log()` on false. The existing `handle_send_test_email()` demonstrates this pattern.
**Warning signs:** Members report no email received; no log entries visible.

### Pitfall 4: Email Settings Tab Warning Rendered Outside the Form
**What goes wrong:** If the `notice-warning` block is placed inside the `<form>` tag but after `settings_fields()`, it may get submitted as form data or disrupt the form layout.
**Why it happens:** WordPress admin notices inside forms can cause subtle layout issues.
**How to avoid:** Place the conditional warning block *before* the `<form>` tag in the Email Settings tab panel, or use the `notice inline` pattern which renders cleanly inside table cells (per WP admin UI conventions — `notice inline` is the established WP pattern for in-page notices).

### Pitfall 5: CONF-05 Assumed Working Without Verification
**What goes wrong:** The test email AJAX handler is implemented but has never been exercised via the full Mailpit stack in a running container. A misconfiguration (wrong port, AutoTLS, etc.) would go undetected until production.
**Why it happens:** Unit tests stub `wp_mail()`; only end-to-end in Docker catches SMTP issues.
**How to avoid:** Phase 3 must include a verification task: start Docker, open Email Settings tab, click "Send Test Email," confirm message appears in Mailpit UI at `localhost:8025`.

---

## Code Examples

Verified patterns from existing codebase and WordPress docs:

### wp_mail() with Attachment (core API)
```php
// Source: https://developer.wordpress.org/reference/functions/wp_mail/
// $attachments accepts an array of absolute file system paths.
$sent = wp_mail(
    'member@example.com',
    __( 'Your registration form', 'wp-membership-registration' ),
    __( 'Thank you for your registration. Please find your completed membership form attached.', 'wp-membership-registration' ),
    array(),              // headers — empty uses site defaults
    array( '/tmp/wmr-uuid.pdf' )
);
```

### Hook Registration with Two Arguments
```php
// Source: WordPress Codex — add_action $accepted_args
// $accepted_args MUST match the number of arguments do_action passes.
add_action( 'wmr_form_submitted', array( $mailer, 'handle_submission' ), 10, 2 );

// Hook fired by Phase 4:
do_action( 'wmr_form_submitted', $field_values, $member_email );
```

### Member Email Resolution
```php
// Source: FieldSchema contract from Phase 1; src/Util/FieldSchema.php
$json   = get_option( 'wmr_field_schema', '[]' );
$fields = FieldSchema::decode( $json );

$email_field_label = null;
foreach ( $fields as $field ) {
    if ( ( $field['type'] ?? '' ) === 'email' ) {
        $email_field_label = $field['label'];
        break;
    }
}

$member_email = ( $email_field_label !== null )
    ? sanitize_email( $field_values[ $email_field_label ] ?? '' )
    : '';
```

### Namespace Declaration for New Mailer Class
```php
// Pattern from existing src/ classes
namespace WpMembershipRegistration\Email;

use WpMembershipRegistration\Pdf\PdfGenerator;
use WpMembershipRegistration\Util\FieldSchema;
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Direct PHPMailer instantiation | `wp_mail()` | WordPress ~2.0 | Site's SMTP config (mu-plugin) transparently applies |
| `mail()` PHP native | `wp_mail()` | Project Phase 1 decision | WPCS compliance, WP filter hooks, PHPMailer routing |
| `unlink()` for temp files | `wp_delete_file()` | Phase 2 decision | WPCS compliance |

**Deprecated/outdated:**
- PHP native `mail()`: Never use — bypasses WP's PHPMailer; WPCS flags it as violation.
- Storing email attachment data in the database: Out of scope; temp file + immediate delete is the project pattern.

---

## Open Questions

1. **Plain text vs HTML email body**
   - What we know: User left this to Claude's discretion
   - What's unclear: Whether `wp_mail()` sets `Content-Type: text/html` by default or requires a `$headers` argument
   - Recommendation: Use plain text (no `Content-Type` header needed; simpler; universally readable). If HTML is desired later, add `Content-Type: text/html; charset=UTF-8` to `$headers`. **Plain text is the right v1 choice.**

2. **Content-Type header for wp_mail()**
   - What we know: `wp_mail()` defaults to `text/plain` unless `Content-Type` header is passed
   - What's unclear: Nothing — plain text is confirmed default
   - Recommendation: Pass empty `$headers` array; let WordPress use its default. No action needed.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 9.6 + Brain Monkey 2.6 |
| Config file | `phpunit.xml` (project root) |
| Quick run command | `vendor/bin/phpunit --testsuite unit` |
| Full suite command | `vendor/bin/phpunit` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| MAIL-01 | `Mailer::handle_submission()` calls `wp_mail()` with member address and PDF attachment path | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php -x` | Wave 0 |
| MAIL-02 | `Mailer::handle_submission()` calls `wp_mail()` with admin recipients and PDF attachment path | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php -x` | Wave 0 |
| MAIL-01 | Member email skipped (no send) when no email-type field in schema | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php::test_member_email_skipped_when_no_email_field -x` | Wave 0 |
| MAIL-02 | Admin email still sends when member email is skipped | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php::test_admin_email_sends_even_without_member_email -x` | Wave 0 |
| MAIL-01/02 | Temp PDF file is deleted after both sends (success and failure paths) | unit | `vendor/bin/phpunit tests/Unit/Email/MailerTest.php::test_temp_file_cleaned_up -x` | Wave 0 |
| CONF-05 | Test email button triggers `wp_ajax_wmr_send_test_email` and mail reaches Mailpit | manual/smoke | Start Docker, open Email Settings tab, click "Send Test Email", confirm in Mailpit at localhost:8025 | manual-only |

**CONF-05 is manual-only:** Requires live Docker stack + Mailpit; cannot be automated via PHPUnit without a running WordPress installation.

### Sampling Rate
- **Per task commit:** `vendor/bin/phpunit tests/Unit/Email/MailerTest.php`
- **Per wave merge:** `vendor/bin/phpunit`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/Email/MailerTest.php` — covers MAIL-01, MAIL-02 (member send, admin send, skip-when-no-email-field, cleanup)
- [ ] `tests/Unit/` directory already exists; no new test infrastructure needed — Brain Monkey stubs suffice

---

## Sources

### Primary (HIGH confidence)
- `src/Admin/AjaxHandlers.php` — `handle_send_test_email()` is the canonical `wp_mail()` + recipient-parsing reference implementation already in this project
- `src/Pdf/PdfGenerator.php` — confirmed `generate(array $field_values): string` signature and temp file contract
- `src/Util/FieldSchema.php` — confirmed `decode()` returns `array{label, type, required}[]`
- `src/Admin/SettingsRegistrar.php` — confirmed `wmr_email_settings['recipients']` option structure
- `templates/admin-settings-page.php` — confirmed Email Settings tab structure for template extension
- `src/Plugin.php` — confirmed `register()` hook registration pattern
- `phpunit.xml` + `tests/bootstrap.php` — confirmed test infrastructure; PHPUnit 9.6, Brain Monkey, no WP bootstrap
- https://developer.wordpress.org/reference/functions/wp_mail/ — `$attachments` parameter accepts array of absolute paths (HIGH — official WP docs)

### Secondary (MEDIUM confidence)
- https://developer.wordpress.org/plugins/hooks/actions/ — `add_action` `$accepted_args` behavior documented

### Tertiary (LOW confidence)
- None

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all APIs are native WordPress or already in use in this codebase; no new dependencies
- Architecture: HIGH — Mailer class structure and hook wiring follow patterns already established in Phase 1/2 code
- Pitfalls: HIGH — `$accepted_args` and temp file cleanup pitfalls verified against WordPress source behavior and existing code patterns

**Research date:** 2026-03-22
**Valid until:** 2026-04-22 (stable WordPress API, unlikely to change)
