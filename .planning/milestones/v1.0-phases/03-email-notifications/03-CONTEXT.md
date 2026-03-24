# Phase 3: Email Notifications - Context

**Gathered:** 2026-03-22
**Status:** Ready for planning

<domain>
## Phase Boundary

Attach the generated PDF to two emails on every form submission — one confirmation email to the member, one notification email to all configured admin recipients. CONF-05 (test email button) is already implemented in Phase 1 and just needs verification. No frontend form work in this phase — Phase 4 builds the form. Phase 3 delivers the email infrastructure that Phase 4 will trigger.

</domain>

<decisions>
## Implementation Decisions

### Submission hook design
- Phase 3 uses a WordPress action hook as the trigger: `do_action('wmr_form_submitted', array $field_values, string $member_email)`
- Phase 3 registers a listener on this hook (`add_action('wmr_form_submitted', [$mailer, 'handle_submission'])`)
- Phase 4 fires the hook from the AJAX form submission handler — this keeps Mailer and Form Handler decoupled
- Hook signature: `(array $field_values, string $member_email)` — explicit, typed, minimal

### Member email identification
- Mailer scans `FieldSchema::decode()` and uses the value of the **first field with `type === 'email'`** from the submitted `$field_values` array
- If no email-type field exists in the schema: skip the member email entirely (do not send), but **display an inline warning on the Email Settings tab**
- Warning text (inline on Email Settings tab, read-only): "No email-type field is configured in the form — members will not receive a confirmation email. Add an email-type field on the Form Fields tab."
- Admin email always sends regardless of whether member email can be identified

### Email content
- Subject lines and body text are **hardcoded for v1**, translatable via WordPress i18n (`__()`, `_e()`)
- **Member email** (to the member):
  - Subject: `__('Your registration form', 'wp-membership-registration')`
  - Body: `__('Thank you for your registration. Please find your completed membership form attached.', 'wp-membership-registration')`
- **Admin email** (to all configured recipients):
  - Subject: `__('New membership registration received', 'wp-membership-registration')`
  - Body: `sprintf(__('A new membership registration was submitted. The completed form is attached.', 'wp-membership-registration'))`
- Both emails have the generated PDF as an attachment
- Sender name and address: WordPress site defaults (whatever is configured in Settings > General — no extra config)
- **Read-only preview boxes** on the Email Settings tab show both email templates so admins can see exactly what will be sent. Two static sections: "Member email" and "Admin email", each showing subject and body. Clearly labeled as previews, not editable fields.

### Failure behavior
- Form submission always succeeds regardless of email delivery outcome — email failure never blocks a registration
- `wp_mail()` failures are logged via `error_log()` with context (which email failed, to which address)
- No additional admin notification for failures in v1 — WP_DEBUG_LOG is sufficient
- Temp PDF file (`sys_get_temp_dir()`) is deleted via `wp_delete_file()` after email sending, whether emails succeeded or failed

### CONF-05 (test email)
- Already fully implemented in Phase 1 (`wp_ajax_wmr_send_test_email` in `AjaxHandlers.php`)
- Phase 3 verifies it works end-to-end with the Mailpit Docker setup — no new code needed for CONF-05

### Claude's Discretion
- Exact HTML vs plain-text format of the email body (plain text is simplest)
- Whether to use `wp_mail()` directly in Mailer or a thin wrapper method
- Class name and file location for the new Mailer class (e.g., `src/Email/Mailer.php`)
- Whether `wmr_form_submitted` is documented as a plugin hook in an inline docblock

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 3 covers MAIL-01, MAIL-02, CONF-05
- `.planning/PROJECT.md` — Constraints: PHP 8.0+, WP 6.0+, no external SaaS, GPL-2.0; email-only for v1 (no member database)

### Existing email infrastructure (Phase 1)
- `src/Admin/AjaxHandlers.php` — `handle_send_test_email()` shows the existing `wp_mail()` pattern with `wmr_email_settings['recipients']`; Mailer must use the same option
- `src/Admin/SettingsRegistrar.php` — `wmr_email_settings` option structure: `['recipients' => 'newline-delimited emails']`
- `src/Admin/SettingsPage.php` — Email Settings tab renders here; Phase 3 must add the warning notice (no email field) and read-only email preview boxes to this tab

### PDF generation contract (Phase 2)
- `src/Pdf/PdfGenerator.php` — `generate(array $field_values): string` returns temp file path; caller must call `wp_delete_file()` after use
- `.planning/phases/02-pdf-engine/02-CONTEXT.md` — Temp file handling decisions: `sys_get_temp_dir()` + `wp_delete_file()`, never `wp-content/uploads/`

### Field schema contract (Phase 1)
- `src/Util/FieldSchema.php` — `FieldSchema::decode()` returns array of field definitions; each has a `type` key (e.g., `'email'`, `'text'`, `'date'`)
- `.planning/phases/01-plugin-foundation-and-settings/01-CONTEXT.md` — Field types supported: text, email, date, tel, textarea, select

### Hook orchestrator
- `src/Plugin.php` — All hooks registered here; new `add_action('wmr_form_submitted', ...)` goes in `register()`

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Admin/AjaxHandlers.php::handle_send_test_email()` — Reference implementation of `wp_mail()` with admin recipient parsing; Mailer should follow the same recipient-parsing pattern (`explode("\n", ...)`, `sanitize_email()`, `array_filter()`)
- `src/Util/FieldSchema.php::decode()` — Returns the admin-configured field array; Mailer uses this to find the email-type field
- `src/Pdf/PdfGenerator.php::generate()` — Called by Mailer with submitted field values; returns temp path for attachment

### Established Patterns
- PSR-4 autoloading via `spl_autoload_register` in main plugin file — new `Mailer` class goes in `src/Email/Mailer.php` under namespace `WpMembershipRegistration\Email`
- WPCS coding standards enforced — `wp_delete_file()` not bare `unlink()`; `wp_mail()` not raw `mail()`
- WordPress options API: `get_option('wmr_email_settings', [])` for admin recipients
- All AJAX handlers use `check_ajax_referer()` before processing — Phase 3 does not add new AJAX endpoints (uses action hook instead)

### Integration Points
- `Plugin::register()` — Add `add_action('wmr_form_submitted', [$mailer, 'handle_submission'])` here
- `SettingsPage` — Email Settings tab must be extended with: (1) inline warning if no email-type field, (2) read-only email preview boxes
- Phase 4 will fire `do_action('wmr_form_submitted', $field_values, $member_email)` from the form submission AJAX handler

</code_context>

<specifics>
## Specific Ideas

- The read-only email preview boxes on the settings tab are a nice UX touch so non-technical admins know exactly what their members and colleagues will receive without needing to test first.
- CONF-05 is already done in code — Phase 3 should confirm it works in the Mailpit environment rather than implementing it from scratch.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 03-email-notifications*
*Context gathered: 2026-03-22*
