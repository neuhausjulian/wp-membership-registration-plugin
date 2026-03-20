# Pitfalls Research

**Domain:** WordPress form + PDF generation + email plugin
**Researched:** 2026-03-20
**Confidence:** HIGH (security, WordPress.org review), MEDIUM (PDF library specifics, email deliverability)

---

## Critical Pitfalls

### Pitfall 1: Missing Nonce Verification on Form Submission

**What goes wrong:**
The frontend registration form accepts a POST submission. Without a nonce, any authenticated user (or an attacker who tricks one) can submit the form via CSRF — silently triggering PDF generation and sending emails to both the member address and admin. At worst, an attacker can spam the admin inbox or enumerate the PDF generation endpoint.

**Why it happens:**
Developers add nonce fields to admin forms but forget the public-facing shortcode form because "it's not in the admin panel." WordPress.org's plugin review team explicitly flags "missing nonce verification" and rejects plugins for it. AJAX handlers without `check_ajax_referer()` are flagged identically.

**How to avoid:**
- Add `wp_nonce_field('wmrp_register', 'wmrp_nonce')` to the frontend form.
- Verify with `wp_verify_nonce($_POST['wmrp_nonce'], 'wmrp_register')` before any processing.
- For AJAX submissions, use `check_ajax_referer('wmrp_register')`.
- Also add `current_user_can()` or at minimum confirm this is a legitimate public request before triggering PDF + email.

**Warning signs:**
- Form handler function has no call to `wp_verify_nonce()` or `check_ajax_referer()`.
- Plugin submitted to WordPress.org gets rejected with "security: nonce verification missing."
- Automated security scanners (e.g., Plugin Check plugin) flag CSRF risk.

**Phase to address:** Phase 1 (Form Submission Foundation) — bake in from the first working form handler, never retrofit.

---

### Pitfall 2: Insufficient Input Sanitization and Output Escaping

**What goes wrong:**
Form field values (name, address, email, club-specific fields) are injected into the HTML template used for PDF generation, the email body, and potentially stored in `wp_options`. Without sanitization on input and escaping on output, attackers can inject HTML/JS into the PDF template or admin email, or corrupt stored settings.

**Why it happens:**
Developers conflate sanitization (cleaning input before storage/processing) with escaping (making output safe). Using `__()` without `esc_html__()` wrappers, or echoing `$_POST` values directly into HTML passed to the PDF renderer, are the two most common forms. WordPress.org reviewers specifically look for `echo $_POST` or `echo get_option()` without escaping.

**How to avoid:**
- Sanitize every `$_POST` field with the appropriate function: `sanitize_text_field()` for names, `sanitize_email()` for email, `wp_kses()` or `sanitize_textarea_field()` for multi-line fields.
- Escape all output: `esc_html()` for plain text in HTML, `esc_attr()` for HTML attributes, `wp_kses_post()` for content that may include limited HTML.
- In the PDF HTML template, treat all user-supplied values as untrusted HTML — escape before inserting.
- Use `esc_html__()` and `esc_attr__()` instead of bare `__()` in translated strings that go to output.

**Warning signs:**
- Search for `echo $_POST` or `echo $` without `esc_` wrappers in the codebase.
- Plugin Check / PHPCS with WordPress Coding Standards reports "Output not escaped."
- PDF renders with garbled layout when a form field contains `<`, `>`, or `&`.

**Phase to address:** Phase 1 (Form Submission Foundation) — cannot be retrofitted safely.

---

### Pitfall 3: PDF Library Remote File Access Enabling RCE

**What goes wrong:**
DOMPDF's `$isRemoteEnabled` option (or equivalent) allows the library to fetch remote images, CSS, and fonts during PDF rendering. If an attacker can influence any URL in the HTML template (e.g., via an `<img src>` injected through an unsanitized form field), DOMPDF will fetch it — and in versions ≤ 1.2.0 this can lead to Remote Code Execution via malicious font loading. Even in patched versions, enabling remote fetching broadens the attack surface.

**Why it happens:**
Developers enable `$isRemoteEnabled = true` to get CSS and logo images working quickly. The convenience is obvious; the risk is buried in library documentation.

**How to avoid:**
- Set `$isRemoteEnabled = false` (default-off) unconditionally.
- Serve all assets (logo, CSS, fonts) as local file paths, not HTTP URLs.
- Use `file://` absolute paths for images embedded in the PDF template.
- Ensure DOMPDF is installed inside the plugin directory (not web-accessible) and that no `.php` files from the library are reachable via HTTP.
- Pin to DOMPDF ≥ 2.0.0 (PHP 8 support, security fixes for CVE-2022-28368 and related RCE).

**Warning signs:**
- `$isRemoteEnabled = true` anywhere in PDF configuration.
- Plugin or its Composer dependencies place DOMPDF under `wp-content/uploads/` or another web-accessible path.
- Template contains `<img src="http://...">` with a user-controlled value.

**Phase to address:** Phase 2 (PDF Generation) — configuration must be locked before any template rendering goes to production.

---

### Pitfall 4: Generated PDF Files Stored in Web-Accessible Directories

**What goes wrong:**
The plugin generates a PDF per submission and saves it to disk to attach to an email. If saved under `wp-content/uploads/` (the default writable location), the file is publicly accessible by URL — potentially exposing personally identifiable information (name, address, date of birth, membership details) of all registrants. Other plugins can scan `uploads/` too.

**Why it happens:**
`wp_upload_dir()` is the standard writable path developers reach for. It's web-accessible by design for media files. Temporary PDFs are not media files.

**How to avoid:**
- Store generated PDFs in a subdirectory protected by a `.htaccess` file denying direct access: `Deny from all` (Apache) or an equivalent Nginx config note in the README.
- Better: use `sys_get_temp_dir()` for purely ephemeral files that are attached and then deleted immediately within the same request.
- Attach the PDF from memory (pass the PDF string directly to `wp_mail()` via the `$attachments` parameter as a temp path) and delete the file immediately after `wp_mail()` returns.
- Never construct paths by concatenating user-supplied values.

**Warning signs:**
- PDF filenames include submission timestamp or sequential ID, making them guessable.
- Files persist in `uploads/wmrp/` after the email is sent.
- No `.htaccess` in the PDF temp directory.

**Phase to address:** Phase 2 (PDF Generation) + Phase 3 (Email Sending).

---

### Pitfall 5: PDF Generation Exhausting PHP Memory on Shared Hosting

**What goes wrong:**
DOMPDF and TCPDF both perform all rendering in PHP memory. Complex templates with embedded images, custom fonts, or font subsetting can easily consume 64–128 MB per generation. Many shared hosts enforce a 64 MB or 128 MB memory limit. The result is a fatal error mid-request, no email sent, and no feedback to the user.

**Why it happens:**
The plugin works fine on the developer's well-resourced machine or Docker container but fails silently on a club admin's cheap shared hosting. Font subsetting is the most common culprit — TCPDF's `setFontSubsetting(true)` default can triple memory usage for Unicode fonts.

**How to avoid:**
- Benchmark memory usage during Phase 2 testing against the default Docker setup AND against a simulated 64 MB limit (`ini_set('memory_limit', '64M')` in a test).
- Keep the PDF template minimal: avoid custom web fonts, use PDF-standard fonts (Helvetica, Times), and embed images only when legally required.
- For TCPDF: call `$pdf->setFontSubsetting(false)` unless Unicode range requires it.
- For DOMPDF: avoid complex CSS selectors and large background images.
- Document the minimum recommended PHP memory limit (128 MB) prominently in the README/user guide.
- Consider temporarily bumping `memory_limit` via `@ini_set('memory_limit', '256M')` before generation, with a warning in admin notices if the host enforces a hard cap.

**Warning signs:**
- `Fatal error: Allowed memory size of X bytes exhausted` in `debug.log` on lower-end environments.
- PDF generation takes more than 2–3 seconds on the Docker dev environment.
- Template uses Google Fonts or other remote web fonts (also triggers `$isRemoteEnabled` issue above).

**Phase to address:** Phase 2 (PDF Generation) — test against constrained memory in CI.

---

### Pitfall 6: wp_mail() Relying on PHP's mail() Function Causing Non-Delivery

**What goes wrong:**
By default, `wp_mail()` uses PHP's `mail()` function, which most shared hosts either disable, misconfigure, or route through an IP with no SPF/DKIM record. Emails containing PDF attachments from an unknown IP with no email authentication records go to spam or are silently dropped. Since February 2024, Gmail and Yahoo enforce strict SPF, DKIM, and DMARC requirements for all senders. A club admin with Gmail will never receive the notification email.

**Why it happens:**
`wp_mail()` works locally (Docker uses MailHog or similar) so the developer never sees the deliverability problem. The club admin only finds out weeks later when members report not receiving their confirmation PDF.

**How to avoid:**
- Document in the user guide (prominently, in Phase 1 of installation instructions) that an SMTP plugin or service is required for reliable delivery. Recommend free options: WP Mail SMTP with Gmail/SendGrid/Mailgun.
- In the plugin's admin settings, add a "Send test email" button so admins can verify delivery before going live.
- Do not configure an SMTP service yourself — doing so requires storing credentials in `wp_options` (sensitive), and it duplicates what dedicated plugins already do well. Declare the dependency instead.
- Keep PDF attachments lean (< 1 MB) to reduce spam scoring from large attachments.

**Warning signs:**
- No "test email" feature in admin UI.
- User guide does not mention SMTP.
- Email generated by Docker test goes through fine but production reports non-delivery.

**Phase to address:** Phase 3 (Email Sending) — add test email button; Phase documentation must include SMTP guidance.

---

### Pitfall 7: Large or Unoptimized PDF Attachments Triggering Spam Filters

**What goes wrong:**
Spam filters treat PDF attachments with extra caution — PDFs are a common malware vector. A PDF over ~1 MB attached to a transactional email from a low-reputation sender will be flagged or dropped. MIME encoding adds ~33% size overhead on top of the raw file size.

**Why it happens:**
Developers embed high-resolution logos, background images, or heavy fonts in the PDF template without considering final file size. A template that looks fine at 400 KB on disk becomes 530 KB after MIME encoding — approaching spam filter thresholds for new sender domains.

**How to avoid:**
- Target a maximum PDF output size of 500 KB raw (670 KB after MIME encoding). Test with real data.
- Use PDF-standard fonts (no embedding overhead) and low-resolution logos (72 dpi, PNG compressed).
- Strip any metadata or preview images from embedded assets.
- Log and alert in admin notices if a generated PDF exceeds a configurable size threshold.

**Warning signs:**
- PDF template includes a full-page background image or high-res club logo.
- Generated PDFs exceed 1 MB in testing.
- Email deliverability tests (e.g., Mail-Tester.com) flag attachment size or content.

**Phase to address:** Phase 2 (PDF Generation) template design; Phase 3 (Email Sending) for monitoring.

---

### Pitfall 8: Confusing Deactivation and Uninstall Hooks

**What goes wrong:**
Data deletion logic (removing plugin options, flushing caches, deleting temp directories) is placed on the deactivation hook instead of the uninstall hook. A club admin deactivates the plugin temporarily (to troubleshoot, or before updating) and loses all their configured settings — form fields, admin email addresses, PDF template configuration.

**Why it happens:**
Less experienced WordPress developers treat "deactivate" as equivalent to "remove." The WordPress documentation makes the distinction, but it is easy to miss.

**How to avoid:**
- Use `register_deactivation_hook()` only for: flushing rewrite rules, clearing transients tied to the plugin's own functionality.
- Use `uninstall.php` (preferred over `register_uninstall_hook()`) for permanent data removal: `delete_option('wmrp_settings')`, removing any custom tables or temp file directories.
- Guard `uninstall.php` with `if (!defined('WP_UNINSTALL_PLUGIN')) exit;` at the top.
- Never call `register_activation_hook()` from inside `plugins_loaded` or `init` — it will never fire.

**Warning signs:**
- `delete_option()` or `$wpdb->query('DROP TABLE...')` appears inside a `register_deactivation_hook()` callback.
- Plugin has no `uninstall.php` file at all (data is never cleaned up, leaving orphaned options).
- Activation hook is registered inside an `add_action('init', ...)` callback.

**Phase to address:** Phase 1 (Plugin Foundation/Scaffolding).

---

### Pitfall 9: Missing or Broken i18n — Blocking WordPress.org Submission

**What goes wrong:**
WordPress.org requires all user-facing strings to be wrapped in translation functions. Missing text domain declarations, hardcoded English strings, or incorrect text domain slugs (underscores instead of dashes, or mismatching the plugin slug) cause immediate rejection during plugin review. Even if not targeting WordPress.org initially, retrofitting i18n across a large codebase is expensive.

**Why it happens:**
Developers hardcode strings during rapid prototyping ("I'll add translations later") and then the codebase grows. The text domain is set once in the plugin header but the `load_plugin_textdomain()` call is omitted or the domain slug doesn't match.

**How to avoid:**
- Wrap every user-facing string with `__('...', 'wp-membership-registration')` or `esc_html__()` from the very first line of code.
- Confirm the text domain in the plugin header matches the folder/slug exactly (use dashes, not underscores).
- Call `load_plugin_textdomain()` on the `plugins_loaded` hook.
- Run PHPCS with `WordPress-Extra` ruleset from Phase 1 — it flags missing i18n wrapping automatically.
- Include a `.pot` file for translators generated by WP-CLI: `wp i18n make-pot`.

**Warning signs:**
- Plain quoted strings in `echo` or `?>...<?php` blocks without `__()` wrapping.
- No `load_plugin_textdomain()` call in the main plugin file.
- PHPCS reports "WordPress.WP.I18n" violations.
- Text domain slug in plugin header contains underscores (should be `wp-membership-registration` not `wp_membership_registration`).

**Phase to address:** Phase 1 (Plugin Foundation) — enforce via PHPCS linting in the CI pipeline from day one.

---

### Pitfall 10: Admin Settings Page Overwhelming Non-Technical Club Admins

**What goes wrong:**
The plugin exposes too many options, uses developer-centric terminology ("webhook payload", "JSON schema", "shortcode attributes"), or hides critical setup steps behind multiple sub-pages. Club admins abandon setup halfway through, configure the wrong email address, and report the plugin as "broken" in support threads.

**Why it happens:**
Developers design for the capabilities they built, not for the mental model of a non-technical club treasurer. The settings page grows organically as features are added without a UX audit.

**How to avoid:**
- Group settings into three clear sections: "Form Fields," "PDF Template," "Notifications." Nothing else for v1.
- Use plain language labels: "Send confirmation to member: yes/no" not "Member notification webhook toggle."
- Provide inline help text (`<p class="description">`) for every non-obvious setting.
- Add a "Send test email" button directly on the notifications settings page.
- Test the complete setup flow with an actual non-developer (ideally someone from the pilot club) before Phase 1 release.
- Ship with sensible defaults — admin should be able to install, enter their email address, paste the shortcode, and go live in under 5 minutes.

**Warning signs:**
- Settings page has more than 15 configurable fields at v1 launch.
- Any label uses acronyms (JSON, HTTP, SMTP, webhook) without explanation.
- No default values — every field is blank on first install.
- Pilot club admin asks "what does this mean?" for more than two settings.

**Phase to address:** Phase 4 (Admin UI) — conduct a 30-minute usability test with a non-technical user before marking the phase complete.

---

### Pitfall 11: No Prefix on Functions, Classes, and Options — Collision with Other Plugins

**What goes wrong:**
Functions named `generate_pdf()`, `send_email()`, or options named `settings` in `wp_options` collide with functions from other plugins or themes loaded in the same WordPress instance. PHP throws fatal errors on duplicate function declarations; option collisions corrupt each other's data silently.

**Why it happens:**
Developers namespace their own projects but forget that WordPress loads all active plugins into the same PHP process. This is consistently in the top five WordPress.org rejection reasons.

**How to avoid:**
- Prefix every function: `wmrp_generate_pdf()`, every class: `WMRP_Form_Handler`, every option: `wmrp_settings`, every action/filter hook: `wmrp_`.
- Use a PHP namespace (`namespace WP_Membership_Registration;`) for all classes as a secondary guard.
- Run PHPCS with WordPress Coding Standards from day one — it flags missing prefixes.

**Warning signs:**
- PHPCS reports `WordPress.NamingConventions.PrefixAllGlobals` violations.
- Function names like `send_notification()` or `get_settings()` without a plugin-specific prefix.

**Phase to address:** Phase 1 (Plugin Foundation) — establish naming conventions in the contributing guide.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Hardcode strings without `__()` wrapping | Faster to write | Full codebase i18n retrofit before WordPress.org submission | Never |
| `$isRemoteEnabled = true` in DOMPDF | Easier CSS/image loading from URLs | RCE vulnerability exposure | Never |
| Store generated PDFs in `uploads/` without `.htaccess` | One-line path via `wp_upload_dir()` | PII leakage for all registrants | Never |
| Skip nonce on frontend form | One less field to render | CSRF attack surface, WordPress.org rejection | Never |
| No SMTP guidance in docs | Simpler README | Admin never receives emails on most shared hosts | Never |
| Inline all CSS in PHP strings | Fewer files to manage | Unmaintainable, hard to override for child themes | Only in MVP if isolated to one file |
| All settings in a single `wmrp_settings` array option | Simple get/update | Entire config rewritten on every partial save; grows the autoload burden | Acceptable for v1 if total config is < 10 KB |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| `wp_mail()` | Trusting PHP `mail()` will work on all hosts | Document SMTP dependency; add test-send button in admin |
| `wp_mail()` + attachment | Passing a URL as the attachment path | Pass a local absolute filesystem path, then delete the file after send |
| DOMPDF | Using HTTP URLs for images/CSS inside template | Use absolute `file://` paths; set `$isRemoteEnabled = false` |
| DOMPDF | Loading library from a web-accessible path | Install via Composer into `vendor/` inside plugin dir; exclude with `.htaccess` |
| `wp_options` | Storing large serialized arrays with `autoload = yes` | Use `autoload = no` for large config blobs; keep autoloaded data < 1 KB |
| Webhook (optional) | Using `file_get_contents()` or `cURL` directly | Use `wp_remote_post()` — required by WordPress.org review |
| Shortcode | Enqueueing scripts globally | Use `wp_enqueue_scripts` with conditional loading only when shortcode is present |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| PDF generation in synchronous request | Page hangs for 3–10 s on submit; timeout on slow hosts | Accept form → return "success" → generate PDF in background via `wp_schedule_single_event()` (v2) | Immediately on shared hosting with 30 s `max_execution_time` |
| Font subsetting in TCPDF | Memory exhaustion on PDFs with Unicode content | `setFontSubsetting(false)` unless Unicode range required | At ~64 MB memory limit, < 10 concurrent users |
| Autoloaded plugin options bloat | Every page load is slower across entire site | Set `autoload = 'no'` for large option blobs; keep total autoloaded plugin data < 10 KB | When `wp_options` autoload total exceeds ~1–2 MB across all plugins |
| PDF file not deleted after email send | Disk fills up over time on long-running sites | Delete temp file immediately after `wp_mail()` returns, in a `try/finally` pattern | After ~1,000 submissions depending on PDF size |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| `$isRemoteEnabled = true` in DOMPDF | RCE via malicious font URL in template | Always set `false`; use local file paths only |
| Generated PDFs stored publicly in `uploads/` | PII exposure for all registrants | Store in protected directory or use temp path + delete after send |
| No nonce on frontend form | CSRF — attacker triggers spam email/PDF generation | `wp_nonce_field()` + `wp_verify_nonce()` on every POST handler |
| Unsanitized form values injected into PDF HTML template | HTML injection into PDF, potential XSS in email HTML body | `sanitize_text_field()` on input; `esc_html()` on all template output |
| Missing `if (!defined('WP_UNINSTALL_PLUGIN')) exit;` in uninstall.php | Direct execution of data deletion logic | Always guard `uninstall.php` |
| Using `$_REQUEST` or processing entire POST array | Hidden fields overriding expected values | Access only explicitly expected `$_POST` keys |
| Raw SQL with `$wpdb->query("... WHERE id = {$_GET['id']}")` | SQL injection | Always use `$wpdb->prepare()` |
| Admin settings saved without `current_user_can('manage_options')` check | Any logged-in user can overwrite plugin config | Check capability before processing settings form |

---

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| No success message after form submit | Member doesn't know if submission worked; submits twice | Display clear success message, disable submit button after first click |
| Form validation only on server side | Member fills entire form, submits, waits for page reload to see errors | Add JS client-side validation as progressive enhancement; server-side remains canonical |
| Success message appears at top of page but form is at bottom | Member never sees confirmation (page doesn't scroll) | Scroll to success/error message with JS after response |
| Admin email not configurable (hardcoded to WordPress admin email) | Admin never receives notifications if site admin email differs from club contact | Make admin notification address a required config field |
| "Plugin activated" with no guidance | Club admin doesn't know what to do next | Show a dismissible admin notice pointing to the settings page after first activation |
| Settings page uses developer field names | Club admin confused by "webhook endpoint URL," "payload schema" | Use plain language; hide advanced/optional settings behind a collapsible "Advanced" section |
| PDF layout works on developer's machine, breaks on print | Margin-less content, clipped fields, incorrect page size | Test PDF on A4 and Letter page sizes; test print from browser and from a PDF viewer |

---

## "Looks Done But Isn't" Checklist

- [ ] **Frontend form:** Nonce field present and verified on POST handler — verify with a CSRF test request
- [ ] **PDF generation:** `$isRemoteEnabled` is `false` — verify in PDF library configuration file
- [ ] **PDF generation:** Temp file deleted immediately after email send — verify no files persist in temp directory after form submit
- [ ] **Email delivery:** Test email button works in admin UI — verify on a host with PHP `mail()` disabled (simulate by configuring WP Mail SMTP in test mode)
- [ ] **i18n:** All user-facing strings wrapped in `__()` or `esc_html__()` — run `wp i18n make-pot` and verify `.pot` file captures all strings
- [ ] **Options prefixing:** Every `get_option()` / `add_option()` call uses `wmrp_` prefix — grep for bare `get_option('settings')` or similar
- [ ] **Uninstall:** `uninstall.php` exists and is guarded by `WP_UNINSTALL_PLUGIN` check — verify options are removed after uninstall
- [ ] **Settings capability check:** `manage_options` capability verified before saving settings — test as a Contributor-level user
- [ ] **PDF size:** Generated PDF is < 500 KB — measure with a filled-out sample form
- [ ] **PHPCS:** Zero violations under `WordPress-Extra` ruleset — run in CI before merge

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Missing nonces discovered post-launch | MEDIUM | Patch handler, issue plugin update, bump version, notify users |
| PII leakage via public PDF path | HIGH | Immediately add `.htaccess` deny, audit server logs for unauthorized access, notify affected registrants per GDPR if EU users involved |
| DOMPDF RCE via remote enable | HIGH | Disable remote access, push urgent update, audit server for signs of compromise |
| Unsanitized output discovered | MEDIUM | Patch with `esc_html()`, release update, check git history for when introduced |
| Email non-delivery (missing SMTP setup) | LOW | Add SMTP guidance to README and admin notices; no data lost, only notifications missed |
| Deactivation hook deleting settings | HIGH | Restore from database backup; patch immediately; no automated recovery |
| Memory exhaustion on PDF generation | LOW–MEDIUM | Reduce template complexity; document memory requirements; add admin notice if server limit is too low |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Missing nonce verification | Phase 1: Plugin Foundation | CSRF test tool + WordPress.org Plugin Check plugin |
| Insufficient sanitization/escaping | Phase 1: Plugin Foundation | PHPCS `WordPress-Extra` ruleset in CI |
| Missing function/option prefixes | Phase 1: Plugin Foundation | PHPCS `PrefixAllGlobals` rule |
| Missing i18n wrapping | Phase 1: Plugin Foundation | `wp i18n make-pot` captures all strings |
| Plugin lifecycle hooks (activation/deactivation/uninstall) | Phase 1: Plugin Foundation | Manual test: install → deactivate → re-activate preserves settings; uninstall removes all data |
| DOMPDF remote access enabled | Phase 2: PDF Generation | Code review of PDF configuration; security scan |
| PDF stored in web-accessible directory | Phase 2: PDF Generation | Attempt to access PDF URL directly after form submit — should 403 |
| PDF memory exhaustion | Phase 2: PDF Generation | CI test with `memory_limit = 64M` environment variable |
| Large PDF triggering spam | Phase 2: PDF Generation | Measure PDF file size against 500 KB target |
| wp_mail() delivery failure | Phase 3: Email Sending | Test on host with PHP mail() disabled; test email button in admin |
| PDF attachment path (URL vs filesystem path) | Phase 3: Email Sending | Verify email arrives with valid PDF attachment, not broken attachment |
| Admin UI overloading non-technical users | Phase 4: Admin UI | Usability test with pilot club admin |
| Admin settings without capability check | Phase 4: Admin UI | Attempt settings save as Subscriber-level user — should fail with 403 |

---

## Sources

- [WordPress Plugin Handbook — Common Issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/) — Official WordPress.org rejection criteria
- [WordPress Plugin Review Checklist](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/) — What reviewers look for
- [WordPress Nonces — Plugin Handbook](https://developer.wordpress.org/plugins/security/nonces/) — Nonce implementation reference
- [WordPress Plugin Activation/Deactivation Hooks](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/) — Lifecycle documentation
- [WordPress Uninstall Methods](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/) — Correct uninstall approach
- [DOMPDF RCE via Remote Font Loading — Snyk](https://snyk.io/blog/security-alert-php-pdf-library-dompdf-rce/) — CVE-2022-28368 detail
- [DOMPDF RCE — Positive Security](https://positive.security/blog/dompdf-rce) — Exploit mechanics for `$isRemoteEnabled`
- [DOMPDF Memory Issues — GitHub Issue #2557](https://github.com/dompdf/dompdf/issues/2557) — Real-world memory exhaustion reports
- [TCPDF Performance Tuning — PDF Ink](https://pdfink.com/blog/2016/04/tcpdf-performance-tuning/) — Font subsetting memory impact
- [WordPress Emails Going to Spam — WP Mail SMTP](https://wpmailsmtp.com/wordpress-emails-going-to-spam/) — wp_mail() deliverability issues
- [Email Attachment Deliverability — EmailConsul](https://emailconsul.com/blog/%F0%9F%93%8E-email-attachments-deliverability-what-you-need-to-know/) — PDF attachment spam scoring
- [Email File Size Limits — GlockApps](https://glockapps.com/blog/email-file-size-limits-and-attachment-restrictions/) — Size limits and MIME overhead
- [WordPress wp_options Autoload Performance — 10up](https://10up.com/blog/2017/wp-options-table/) — Options API scaling pitfalls
- [WordPress VIP Autoloaded Options](https://docs.wpvip.com/wordpress-on-vip/autoloaded-options/) — Autoload performance guidance
- [WordPress Internationalization — Plugin Handbook](https://developer.wordpress.org/plugins/internationalization/) — i18n requirements
- [WordPress Securely Developing Plugins — Learn WordPress](https://learn.wordpress.org/tutorial/introduction-to-securely-developing-plugins/) — Official security tutorial

---
*Pitfalls research for: WordPress form + PDF generation + email plugin*
*Researched: 2026-03-20*
