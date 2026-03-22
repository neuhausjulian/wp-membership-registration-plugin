# Phase 4: Frontend Form and UX Polish - Context

**Gathered:** 2026-03-22
**Status:** Ready for planning

<domain>
## Phase Boundary

Any WordPress page with `[membership_form]` shows a working registration form rendered from the admin-configured field schema. The form validates input client-side, blocks bots via honeypot, requires GDPR consent checkbox, submits via AJAX, and shows a configurable success message in-place. The full pipeline (PDF generation + both emails) fires on submission. Blank PDF download offers fillable AcroForm fields. Admin settings get a rich text editor for `form_notes` and `page2_content`. PHPUnit tests, CONTRIBUTING.md, and user README.md ship in this phase.

This is the final v1 phase. It completes FORM-01–05, DEV-02, DEV-04, DEV-05 and folds in five pending todos: TCPDF switch, rich text editors, PDF label alignment, form_notes→signature spacing, and fillable blank PDF fields.

</domain>

<decisions>
## Implementation Decisions

### PDF library: DOMPDF → TCPDF (full switch)
- Replace DOMPDF with TCPDF entirely — one library, not two
- TCPDF is namespace-prefixed via Strauss exactly as DOMPDF was
- TCPDF's `writeHTML()` renders the `form_notes` and `page2_content` HTML in the PDF (handles everything TinyMCE produces: bold, italic, paragraphs, lists, links)
- Blank PDF uses TCPDF AcroForm API to generate real interactive fillable fields (text inputs, date fields) instead of static underlines
- Filled PDF (submitted data) pre-fills values as static text — no AcroForm needed there

### Option rename: gdpr_text → form_notes
- `wmr_branding_settings['gdpr_text']` renamed to `wmr_branding_settings['form_notes']` (key AND UI label)
- UI label in PDF Branding tab: **"Form information"**
- Purpose: general explanatory text below form fields — can contain GDPR mention, membership conditions, anything the club wants to show before the consent checkbox
- Rendered in two places:
  1. PDF page 1 (below dynamic fields, above signature line) via `TCPDF::writeHTML()`
  2. Frontend form (below rendered fields, above consent checkbox) via `wp_kses_post()` echo

### Rich text editors for form_notes and page2_content
- Replace both plain textareas with `wp_editor()` (TinyMCE) in the PDF Branding settings tab
- Both fields already use `wp_kses_post()` sanitization — HTML output is already accepted
- `page2_content`: legal page 2 of the PDF (AGBs, Datenschutzerklärung, Bankverbindung) — PDF only, not shown on frontend form

### New Form Settings tab
- A fourth tab added to the settings page: **Form Fields | PDF Branding | Email Settings | Form Settings**
- Stores settings under a new `wmr_form_settings` option (Settings API)
- Contains:
  - **Consent checkbox text** (`consent_text`) — short label for the GDPR consent checkbox on the frontend form (e.g. "I agree to the membership terms and data processing described above"). Required to submit.
  - **Success message** (`success_message`) — message shown in-place after successful submission (e.g. "Thank you! Your registration has been received.")

### Frontend form rendering
- PHP server-side: `MembershipFormShortcode::render()` loops over `FieldSchema::decode()` and outputs HTML `<input>`, `<select>`, `<textarea>` elements for each field
- Field types: text, email, date, tel, textarea, select (all supported from Phase 1 schema)
- Field output order: dynamic fields → `form_notes` block → consent checkbox → submit button
- Honeypot: hidden input with a common bot-attracting name (e.g. `name="website"`) — server-side: if non-empty, silently discard submission (return success to avoid bot retry loops)
- Nonce: `wp_nonce_field()` output in the form; AJAX handler verifies with `check_ajax_referer()`

### Client-side validation (custom JS inline errors)
- JS validates before AJAX submit (not native HTML5 browser validation)
- Required fields: show error message below the field if empty on submit attempt
- Email fields: validate format with regex in addition to required check
- Consent checkbox: show error if unchecked
- Errors shown as `<span class="wmr-field-error">` appended below each invalid field
- Errors cleared on next submit attempt
- AJAX submit only fires if all validation passes

### Submission AJAX handler
- New action: `wp_ajax_nopriv_wmr_submit_form` (and `wp_ajax_wmr_submit_form` for logged-in)
- Handler in `AjaxHandlers.php`: verify nonce → check honeypot → sanitize fields → fire `do_action('wmr_form_submitted', $field_values, $member_email)` → return JSON success/error
- Member email extracted from submitted values: first field with `type === 'email'` in the schema (consistent with Mailer's existing pattern)
- On success: JSON `{success: true}` — JS replaces form HTML with success message from `wmr_form_settings['success_message']`
- On failure: JSON `{success: false, data: {message: '...'}}` — JS shows generic error message

### Success behavior
- In-place swap: form container replaced with success message (no page reload, no redirect)
- Success message text from `wmr_form_settings['success_message']`

### Form styling
- Minimal plugin stylesheet enqueued via `wp_enqueue_style()` on the frontend (only when shortcode is present)
- Basic structure and spacing only — inherits theme font, colors, and input styling
- BEM-style class prefix `wmr-` to avoid conflicts with theme styles

### PDF layout fixes (from pending todos)
- **Label alignment**: two-column table layout in TCPDF — fixed-width right-aligned label column (~50mm), fixed-start value column filling remaining width. All input underlines/values start at the same horizontal position.
- **Spacing**: increased margin between `form_notes` block and the signature/date section (approximately 8mm `margin-top` equivalent in TCPDF)

### Claude's Discretion
- Exact TCPDF AcroForm field types per schema field type (e.g. text → `TCPDF_FIELD_TEXT`, date → same with placeholder)
- `wmr_form_settings` sanitize callbacks (standard: `sanitize_text_field` for both fields)
- Class names and file locations for new PHP classes (e.g. `src/Frontend/FormRenderer.php`)
- PHPUnit test coverage scope for FormHandler (AJAX handler unit tests + integration smoke test)
- CONTRIBUTING.md and README.md content structure
- Whether `wmr_generate_pdf_stub` AJAX stub is removed or left as dev-only in Phase 4

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 4 covers FORM-01–05, DEV-02, DEV-04, DEV-05
- `.planning/PROJECT.md` — Constraints: PHP 8.0+, WP 6.0+, no external SaaS, GPL-2.0; no CAPTCHA (honeypot sufficient); shortcode approach confirmed

### Prior phase contracts
- `.planning/phases/01-plugin-foundation-and-settings/01-CONTEXT.md` — Field schema (`wmr_field_schema`), field types (text/email/date/tel/textarea/select), settings page tab structure, AJAX nonce pattern
- `.planning/phases/02-pdf-engine/02-CONTEXT.md` — PDF layout decisions (header, fields, form_notes/signature/footer, page 2), temp file handling (`sys_get_temp_dir()` + `wp_delete_file()`), Strauss namespace prefixing pattern
- `.planning/phases/03-email-notifications/03-CONTEXT.md` — `wmr_form_submitted` hook signature `(array $field_values, string $member_email)`, recipient parsing pattern from `wmr_email_settings['recipients']`

### Existing code (read before touching)
- `src/Shortcodes/MembershipFormShortcode.php` — Stub to complete; `render()` currently returns `''` for non-download case
- `src/Admin/AjaxHandlers.php` — Add `wmr_submit_form` handler here; existing nonce/sanitize patterns to follow
- `src/Plugin.php` — Register new hooks here (`wp_ajax_nopriv_wmr_submit_form`, `wp_enqueue_scripts` for frontend assets)
- `src/Admin/SettingsRegistrar.php` — Add `wmr_form_settings` option group and `form_notes` key rename here
- `src/Admin/SettingsPage.php` — Add Form Settings tab; replace `gdpr_text` textareas with `wp_editor()`; update key references from `gdpr_text` → `form_notes`
- `src/Util/FieldSchema.php` — `decode()` is the field schema contract; form renderer reads this
- `src/Email/Mailer.php` — Already listens on `wmr_form_submitted`; member email extraction pattern already implemented

### Testing patterns
- `tests/bootstrap.php` — Brain Monkey setup, i18n stubs, PSR-4 autoloader
- `tests/Unit/Email/MailerTest.php` — Reference for Brain Monkey unit test structure (setUp/tearDown, function stubs, assertion patterns)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Util/FieldSchema.php::decode()` — Returns `array{label: string, type: string, required: bool}[]`; FormRenderer reads this to build the HTML form
- `src/Admin/AjaxHandlers.php` — Existing nonce verification (`check_ajax_referer`), sanitization (`sanitize_text_field`, `wp_unslash`), and `wp_send_json_success/error` patterns used by new `wmr_submit_form` handler
- `src/Email/Mailer.php` — Recipient parsing pattern: `array_filter(array_map('sanitize_email', explode("\n", $raw)))` — form handler uses same pattern to find member email from submitted values

### Established Patterns
- PSR-4 autoloading via `spl_autoload_register` — new classes in `src/Frontend/` under `WpMembershipRegistration\Frontend\` namespace
- WordPress options API: `get_option('wmr_form_settings', [])` for new form settings
- WPCS coding standards enforced — `wp_enqueue_style/script` not bare HTML tags; `wp_kses_post` not raw echo
- All AJAX handlers: `check_ajax_referer()` first, then capability check if admin-only
- Strauss namespace prefixing: new `tcpdf` entry in `composer.json` `extra.strauss.packages`, same process as DOMPDF

### Integration Points
- `Plugin::register()` — Add: `wp_ajax_nopriv_wmr_submit_form`, `wp_ajax_wmr_submit_form`, `wp_enqueue_scripts` (frontend JS+CSS, only when shortcode active)
- `MembershipFormShortcode::render()` — Fill in the non-download path: instantiate FormRenderer, return HTML
- `PdfGenerator.php` — Full rewrite from DOMPDF to TCPDF; public API stays the same: `generate(array $field_values): string` and `generate_blank(): string`
- `SettingsRegistrar.php` — Rename `gdpr_text` key to `form_notes` in `wmr_branding_settings`; add `wmr_form_settings` with `consent_text` and `success_message`

</code_context>

<specifics>
## Specific Ideas

- The `form_notes` block flows to three consumers from one admin field: PDF page 1, blank PDF, and frontend form below fields — single source of truth for explanatory text
- TCPDF's `writeHTML()` renders TinyMCE HTML output (bold, paragraphs, lists) — adequate for what a club admin writes
- Fillable blank PDF: members who prefer to fill forms in a PDF viewer (Adobe Acrobat, Preview) can now do so instead of printing and writing by hand
- The pending todo "Make PDF input fields interactive fillable form fields" is now in scope — resolved by the TCPDF switch
- Honeypot field: silently discard (return success) to avoid bot retry loops — do not return error responses to bots

</specifics>

<deferred>
## Deferred Ideas

- `add single and multi-select field types` todo — `select` (single) is already supported in the field schema from Phase 1; `multiselect` adds complexity and is v2 scope
- Interactive fillable fields on the *filled* PDF (submitted data) — not needed; values are pre-filled as static text
- Scheduled/automated email retries on delivery failure — v2 scope
- Gutenberg block for the registration form — explicitly out of scope (shortcode covers v1 per PROJECT.md)
- Rich text editor for email body/subject lines — hardcoded for v1 (Phase 3 decision)

</deferred>

---

*Phase: 04-frontend-form-and-ux-polish*
*Context gathered: 2026-03-22*
