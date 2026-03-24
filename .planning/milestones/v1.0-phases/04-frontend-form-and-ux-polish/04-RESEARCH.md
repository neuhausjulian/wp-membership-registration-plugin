# Phase 4: Frontend Form and UX Polish - Research

**Researched:** 2026-03-22
**Domain:** WordPress shortcode form rendering, AJAX submission, TCPDF AcroForm, wp_editor TinyMCE, PHPUnit Brain Monkey
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **PDF library**: Replace DOMPDF with TCPDF entirely (one library, not two). TCPDF namespace-prefixed via Strauss exactly as DOMPDF was.
- **TCPDF writeHTML()** renders `form_notes` and `page2_content` HTML in the PDF (bold, italic, paragraphs, lists, links).
- **Blank PDF**: TCPDF AcroForm API for real interactive fillable fields (text inputs, date fields) instead of static underlines.
- **Filled PDF (submitted data)**: Pre-fills values as static text — no AcroForm needed.
- **Option rename**: `wmr_branding_settings['gdpr_text']` → `wmr_branding_settings['form_notes']` (key AND UI label). UI label: "Form information".
- **form_notes rendered in**: (1) PDF page 1 via `TCPDF::writeHTML()`, (2) frontend form via `wp_kses_post()` echo.
- **Rich text editors**: Replace both `form_notes` and `page2_content` plain textareas with `wp_editor()` (TinyMCE) in the PDF Branding settings tab.
- **New Form Settings tab**: Fourth tab — "Form Fields | PDF Branding | Email Settings | Form Settings". Option key: `wmr_form_settings`. Contains: `consent_text` and `success_message`.
- **Frontend form rendering**: PHP server-side, `MembershipFormShortcode::render()` loops over `FieldSchema::decode()`. Field output order: dynamic fields → `form_notes` block → consent checkbox → submit button.
- **Honeypot**: Hidden input `name="website"`, silently discard non-empty (return success to avoid bot retry loops).
- **Nonce**: `wp_nonce_field()` in form; AJAX handler verifies with `check_ajax_referer()`.
- **Client-side validation**: Custom JS (not native HTML5). Required fields, email regex, consent checkbox. Errors as `<span class="wmr-field-error">`. AJAX only fires if all pass.
- **AJAX handler**: `wp_ajax_nopriv_wmr_submit_form` + `wp_ajax_wmr_submit_form`. In `AjaxHandlers.php`. nonce → honeypot → sanitize → `do_action('wmr_form_submitted', ...)` → JSON.
- **Success behavior**: In-place swap (form container replaced by success message, no reload). Text from `wmr_form_settings['success_message']`.
- **Form styling**: Minimal, `wp_enqueue_style()` only when shortcode present. BEM-style `wmr-` prefix.
- **PDF layout fixes**: Two-column table layout in TCPDF for label alignment (~50mm label column). Increased margin between `form_notes` and signature section (~8mm).

### Claude's Discretion

- Exact TCPDF AcroForm field types per schema field type
- `wmr_form_settings` sanitize callbacks (standard: `sanitize_text_field` for both)
- Class names and file locations for new PHP classes (e.g. `src/Frontend/FormRenderer.php`)
- PHPUnit test coverage scope for FormHandler (AJAX handler unit tests + integration smoke test)
- CONTRIBUTING.md and README.md content structure
- Whether `wmr_generate_pdf_stub` AJAX stub is removed or left as dev-only in Phase 4

### Deferred Ideas (OUT OF SCOPE)

- `add single and multi-select field types` todo — `select` (single) already supported; `multiselect` is v2 scope
- Interactive fillable fields on the filled PDF (submitted data)
- Scheduled/automated email retries on delivery failure
- Gutenberg block for the registration form
- Rich text editor for email body/subject lines
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| FORM-01 | Visitor can access the registration form on any page via `[membership_form]` shortcode | FormRenderer class renders HTML from FieldSchema::decode(); MembershipFormShortcode::render() non-download path filled in |
| FORM-02 | Form includes a GDPR/privacy consent checkbox with admin-configurable text (required to submit) | `wmr_form_settings['consent_text']` option; JS validation blocks submit if unchecked |
| FORM-03 | Form uses honeypot spam protection to block automated bot submissions | Hidden `name="website"` field; AJAX handler silently discards non-empty |
| FORM-04 | Form validates required fields and email format client-side before submission | Custom JS validation; `<span class="wmr-field-error">` pattern; AJAX fires only on pass |
| FORM-05 | Form displays a configurable success message after successful submission | `wmr_form_settings['success_message']` option; JS in-place swap |
| DEV-02 | PHPUnit test suite: unit tests (Brain Monkey) and integration tests | Existing test infrastructure extended; new tests for FormRenderer, AjaxHandlers submit handler |
| DEV-04 | CONTRIBUTING.md developer guide | New file; covers Docker setup, PHPUnit, PHPCS, PR workflow |
| DEV-05 | User-facing README.md | New file; covers installation, settings configuration, shortcode usage |
</phase_requirements>

---

## Summary

Phase 4 is the final v1 phase and closes eight requirements across two domains: frontend form UX (FORM-01 to FORM-05) and developer documentation (DEV-02, DEV-04, DEV-05). It also completes a full PDF engine swap (DOMPDF → TCPDF) and adds TinyMCE rich text editing to two admin fields.

The work divides into five coherent implementation areas: (1) TCPDF engine replacement with two-column PDF layout and AcroForm blank PDF, (2) admin settings additions — Form Settings tab + wp_editor() replacements + form_notes key rename, (3) frontend form rendering via shortcode + AJAX submission pipeline, (4) PHPUnit test coverage for the new form submission handler, and (5) developer/user documentation.

All architectural patterns are locked in CONTEXT.md. Research confirms TCPDF 6.11.2 is available via `tecnickcom/tcpdf`, supports writeHTML() with full TinyMCE-compatible tag set, provides TextField()/CheckBox() AcroForm methods, and installs via Strauss following the established DOMPDF prefix pattern. WordPress's `wp_editor()` works in settings page forms with the `textarea_name` setting for proper POST data binding.

**Primary recommendation:** Follow CONTEXT.md decisions exactly. No architectural decisions remain open — implement in dependency order: TCPDF engine first (other code depends on it), then admin settings, then frontend form, then tests and docs.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| tecnickcom/tcpdf | ^6.11 | PDF generation (replaces DOMPDF) | Supports AcroForm API, writeHTML(), two-column cell layout; locked decision in CONTEXT.md |
| PHPUnit | ^9.6 (already installed) | Unit + integration tests | Already in composer.json |
| Brain Monkey | ^2.6 (already installed) | WordPress function stubs for unit tests | Already in use in MailerTest.php |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| BrianHenryIE/strauss | latest phar | Namespace-prefix TCPDF vendor files | Required to avoid conflicts with themes/plugins that also load TCPDF |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| tecnickcom/tcpdf | DOMPDF | DOMPDF cannot produce AcroForm fields; already being replaced per locked decision |
| Custom JS validation | jQuery Validate | Custom is 40 lines, no extra dependency; jQuery Validate is overkill for 5-field forms |
| wp_editor() | Custom textarea | wp_editor() is the WordPress-standard way; custom TinyMCE init is fragile |

**Installation:**
```bash
composer require tecnickcom/tcpdf
# Then run the existing Strauss prefix step:
composer run prefix-namespaces
```

---

## Architecture Patterns

### Recommended Project Structure
```
src/
├── Frontend/
│   └── FormRenderer.php     # New: renders HTML form from FieldSchema
├── Pdf/
│   └── PdfGenerator.php     # Rewrite: DOMPDF → TCPDF
├── Admin/
│   ├── AjaxHandlers.php     # Add: handle_submit_form() method
│   ├── SettingsRegistrar.php # Add: wmr_form_settings; rename gdpr_text→form_notes
│   └── SettingsPage.php     # Add: Form Settings tab; wp_editor() replacements
└── Shortcodes/
    └── MembershipFormShortcode.php  # Fill in non-download render() path

assets/
├── css/
│   ├── admin-settings.css   # Existing
│   └── form.css             # New: frontend form styles (wmr- prefix)
└── js/
    ├── admin-settings.js    # Existing
    └── form.js              # New: client-side validation + AJAX submit

templates/
├── admin-settings-page.php  # Modify: add Form Settings tab, wp_editor(), rename field
└── pdf/
    └── membership-form.php  # Remove (TCPDF uses PHP API, not HTML template)

tests/
└── Unit/
    ├── Email/MailerTest.php       # Existing
    ├── Pdf/PdfGeneratorTest.php   # Rewrite stubs: gdpr_text→form_notes
    ├── Frontend/
    │   └── FormRendererTest.php   # New
    └── Admin/
        └── AjaxHandlersTest.php   # New: wmr_submit_form handler
```

### Pattern 1: TCPDF Engine Initialization (Strauss-prefixed)
**What:** TCPDF instantiated using the Strauss-prefixed namespace, same as DOMPDF was.
**When to use:** Everywhere PdfGenerator constructs the PDF object.
**Example:**
```php
// Source: Strauss prefix pattern from Phase 2 (02-CONTEXT.md)
use WpMembershipRegistration\Vendor\Tecnickcom\TCPDF\TCPDF;

$pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );
$pdf->SetCreator( 'WP Membership Registration' );
$pdf->SetTitle( $document_title );
$pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );
$pdf->AddPage();
```

**Strauss config addition to composer.json** (`extra.strauss.packages`):
```json
"tecnickcom/tcpdf"
```
Remove `dompdf/dompdf`, `dompdf/php-font-lib`, `dompdf/php-svg-lib` from the list.

### Pattern 2: TCPDF writeHTML() for Rich Text Blocks
**What:** Render HTML (from TinyMCE/wp_kses_post) into the PDF stream.
**When to use:** `form_notes` block on PDF page 1; `page2_content` on page 2.
**Example:**
```php
// Source: github.com/tecnickcom/TCPDF/blob/main/examples/example_006.php
$pdf->writeHTML( $form_notes_html, true, false, true, false, '' );
```
Supported tags include: `<b>`, `<i>`, `<u>`, `<p>`, `<ul>`, `<ol>`, `<li>`, `<a>`, `<h1>`–`<h6>`, `<br>`, inline CSS. This covers everything TinyMCE produces.

### Pattern 3: TCPDF Two-Column Cell Layout for Field Rows
**What:** Fixed-width label column (~50mm, right-aligned) + value/underline column filling remaining width.
**When to use:** Each field row in the filled and blank PDFs.
**Example:**
```php
// Source: TCPDF MultiCell/Cell API
$pdf->SetFont( 'dejavusans', '', 10 );
$pdf->Cell( 50, 6, $field['label'] . ':', 0, 0, 'R' ); // label column
if ( $is_blank ) {
    $pdf->Cell( 0, 6, '', 'B', 1, 'L' ); // underline via bottom border
} else {
    $pdf->Cell( 0, 6, $field_values[ $field['label'] ] ?? '', 0, 1, 'L' );
}
```

### Pattern 4: TCPDF AcroForm TextField for Blank PDF
**What:** Interactive fillable text fields in the blank PDF instead of static underlines.
**When to use:** `generate_blank()` path only.
**Example:**
```php
// Source: github.com/tecnickcom/TCPDF/blob/main/examples/example_014.php
// Place label cell, then a TextField at current X/Y position
$pdf->Cell( 50, 6, $field['label'] . ':', 0, 0, 'R' );
$pdf->TextField(
    $field['label'],  // field name (unique per field)
    100,              // width mm
    6,                // height mm
    array( 'lineWidth' => 0.5, 'borderStyle' => 'solid' ),
    array()           // options (value, default value)
);
$pdf->Ln( 8 );
```

For date fields use the same `TextField()` with a placeholder option: `array('v' => 'TT.MM.JJJJ', 'dv' => 'TT.MM.JJJJ')`.

### Pattern 5: wp_editor() Replacing a Textarea in a Settings Form
**What:** TinyMCE rich-text editor for `form_notes` and `page2_content` in the PDF Branding tab.
**When to use:** Any admin form textarea that stores HTML content.
**Example:**
```php
// Source: developer.wordpress.org/reference/functions/wp_editor/
wp_editor(
    wp_kses_post( $branding['form_notes'] ?? '' ),  // initial content
    'wmr-form-notes',                               // HTML element ID (no brackets)
    array(
        'textarea_name' => 'wmr_pdf_branding[form_notes]', // POST key
        'textarea_rows' => 5,
        'media_buttons' => false,
        'teeny'         => true,   // compact toolbar
    )
);
```
**Critical:** `textarea_name` must be set to the correct POST key (`wmr_pdf_branding[form_notes]`). The `$editor_id` must not contain square brackets — use hyphenated IDs.

### Pattern 6: Frontend AJAX Form Submission
**What:** wp_nonce_field in form, fetch() AJAX call in JS, JSON response handler.
**When to use:** `wmr_submit_form` action.
**PHP (handler in AjaxHandlers.php):**
```php
// Source: existing AjaxHandlers.php pattern
public function handle_submit_form(): void {
    check_ajax_referer( 'wmr_submit_form', 'nonce' );
    // Honeypot check
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_success(); // Silent discard
        return;
    }
    // Sanitize, fire action, respond
    $field_values = /* sanitized from $_POST['wmr_fields'] */;
    do_action( 'wmr_form_submitted', $field_values, $member_email );
    wp_send_json_success( array( 'message' => $success_message ) );
}
```
**JS (form.js):**
```javascript
// Source: CONTEXT.md decisions
const formData = new FormData(form);
fetch(wmrForm.ajaxUrl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = wmrForm.successMessage;
        }
    });
```

### Pattern 7: Conditional wp_enqueue_scripts (shortcode only)
**What:** Enqueue frontend CSS/JS only on pages that use the shortcode.
**When to use:** Plugin.php `wp_enqueue_scripts` hook.
**Example:**
```php
// Source: WordPress coding standards — use has_shortcode() for conditional enqueue
add_action( 'wp_enqueue_scripts', function() use ( $shortcode ) {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'membership_form' ) ) {
        wp_enqueue_style( 'wmr-form', WMR_PLUGIN_URL . 'assets/css/form.css', array(), WMR_VERSION );
        wp_enqueue_script( 'wmr-form', WMR_PLUGIN_URL . 'assets/js/form.js', array(), WMR_VERSION, true );
        wp_localize_script( 'wmr-form', 'wmrForm', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'submitNonce'    => wp_create_nonce( 'wmr_submit_form' ),
            'successMessage' => wp_kses_post( $success_msg ),
        ) );
    }
} );
```

### Anti-Patterns to Avoid

- **Using HTML template for TCPDF**: DOMPDF used `templates/pdf/membership-form.php`; TCPDF uses a PHP API, not HTML. Do not pass HTML to TCPDF when the layout requires precise column alignment — use `Cell()`/`MultiCell()` directly.
- **writeHTML() for the whole PDF**: Use `writeHTML()` only for the rich-text blocks (`form_notes`, `page2_content`). Use `Cell()` for structured field rows where alignment is required.
- **Using `$editor_id` with brackets**: `wp_editor( ..., 'wmr_pdf_branding[form_notes]', ... )` will break TinyMCE initialization. Use a hyphenated ID and set `textarea_name` separately.
- **Registering nopriv AJAX without nonce**: The `wmr_submit_form` handler is accessible to unauthenticated users — `check_ajax_referer()` is the only gate. Never skip it.
- **Moving TinyMCE in the DOM with JS**: `wp_editor()` editors cannot be safely moved after init. Do not render them inside a hidden tab and then show/hide with `display:none` toggle if the init fires before they are visible. Use the `style="display:none"` approach already established for the existing tabs (PHP sets initial visibility; the form_notes editor renders inside its tab).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Rich text in admin | Custom TinyMCE init | `wp_editor()` | WordPress-bundled, handles WP version differences, correct nonce/autosave handling |
| PDF font embedding | Custom font loader | TCPDF built-in DejaVu fonts | TCPDF ships DejaVu Sans (full Unicode); no font files to manage |
| CSRF protection on AJAX | Custom token system | `wp_nonce_field()` + `check_ajax_referer()` | WordPress-standard; already used throughout the plugin |
| Honeypot spam filter | External CAPTCHA | `name="website"` hidden input | No third-party dependency; no accessibility issues; locked decision |
| PDF temp file naming | UUID generator | `wp_generate_uuid4()` | Already used in PdfGenerator; consistent pattern |
| Client-side email regex | Third-party validator | Simple `/^[^\s@]+@[^\s@]+\.[^\s@]+$/` | Sufficient for UX pre-validation; server side re-validates |

**Key insight:** The WordPress plugin API provides nearly everything needed. The only external library addition is TCPDF itself.

---

## Common Pitfalls

### Pitfall 1: TCPDF Strauss namespace — package name mismatch
**What goes wrong:** Strauss silently skips scoping if the package name in `extra.strauss.packages` does not match the installed Composer package name.
**Why it happens:** DOMPDF had this exact issue with `phenx/` vs `dompdf/php-font-lib` (logged in STATE.md).
**How to avoid:** Use `tecnickcom/tcpdf` (the Packagist package name, not a path or class name).
**Warning signs:** `vendor-prefixed/` does not contain any TCPDF files after running `composer run prefix-namespaces`.

### Pitfall 2: TCPDF cURL extension requirement
**What goes wrong:** TCPDF 6.x requires the PHP cURL extension. If it is absent, Composer install succeeds but TCPDF throws at runtime.
**Why it happens:** TCPDF 6.11.2 declares `ext-curl` as a dependency.
**How to avoid:** Verify `php -m | grep curl` inside the Docker container. The official WordPress Docker image includes cURL — this is LOW risk but worth confirming.
**Warning signs:** `Class 'CURLFile' not found` or similar fatal on first PDF generation.

### Pitfall 3: wp_editor() inside a hidden tab panel
**What goes wrong:** TinyMCE fails to initialize if the `<textarea>` it replaces is inside a `display:none` container when the page loads.
**Why it happens:** TinyMCE measures the DOM element on init; a hidden element has zero dimensions.
**How to avoid:** The existing tab panels use `style="display:none"` toggled by JS — this is safe because the WP admin's TinyMCE init fires after all `wp_editor()` outputs are rendered in the HTML source. The TinyMCE `init_instance_callback` triggers on focus/first-activation, not on page load. However, to be safe, consider the `wp_editor()` in the *second* tab (PDF Branding) which is hidden by default. The existing test pattern shows this works (Phase 1 used the same tab-hidden approach for other fields).
**Warning signs:** WYSIWYG editor shows as a plain textarea; no TinyMCE toolbar visible after clicking the PDF Branding tab.

### Pitfall 4: gdpr_text → form_notes key rename — stale data
**What goes wrong:** Existing WP installs have `wmr_pdf_branding['gdpr_text']` in the database. After renaming the key, those installs silently lose the content.
**Why it happens:** The sanitize callback produces a new array with `form_notes` key; the old `gdpr_text` key is never migrated.
**How to avoid:** In `SettingsRegistrar::sanitize_pdf_branding()`, the new array must include a one-time migration: check if `form_notes` is absent but `gdpr_text` is present (from the stored option), and use `gdpr_text` value as fallback. Also update `PdfGeneratorTest.php` stubs from `gdpr_text` to `form_notes`.
**Warning signs:** PDF Branding tab shows empty "Form information" field after saving settings once; PdfGeneratorTest.php stubs reference the old key.

### Pitfall 5: Brain Monkey stubs missing for new WP functions
**What goes wrong:** New code in `FormRenderer` or `AjaxHandlers::handle_submit_form()` calls WP functions not yet stubbed in `tests/bootstrap.php` or test `setUp()`.
**Why it happens:** Brain Monkey does not auto-stub every WP function — only those explicitly registered. Prior phases added `__()`, `esc_attr`, `esc_html`, `wp_kses_post` manually.
**How to avoid:** For every new WP function call in tested classes, add a `Functions\stubs([...])` entry. Common additions needed: `wp_verify_nonce`, `sanitize_text_field`, `wp_unslash`, `wp_send_json_success`, `wp_send_json_error`, `wp_nonce_field`, `esc_url`, `selected`, `checked`.
**Warning signs:** `Brain\Monkey\Expectation\Exception\MissedExpectation` or `Function ... has not been defined yet` during test run.

### Pitfall 6: AJAX nopriv nonce not localized to frontend JS
**What goes wrong:** `wmr_submit_form` nonce created on PHP side but not passed to `form.js`, causing `check_ajax_referer()` to fail every submission.
**Why it happens:** Frontend JS has no access to PHP-generated nonces unless they are localized via `wp_localize_script()`.
**How to avoid:** `wp_localize_script( 'wmr-form', 'wmrForm', [ 'submitNonce' => wp_create_nonce('wmr_submit_form'), ... ] )`.
**Warning signs:** Every AJAX submission returns `-1` (nonce failure) even with valid data.

### Pitfall 7: has_shortcode() only works on single posts
**What goes wrong:** `has_shortcode( $post->post_content, 'membership_form' )` returns false on non-singular pages (archives, home page).
**Why it happens:** `$post` is the current post object; on non-singular contexts it may be null or the wrong post.
**How to avoid:** Guard with `is_singular()` before checking `$post->post_content`. Alternatively, use the late `wp_footer` hook to enqueue after shortcode has rendered — but `wp_localize_script` needs to run before the script outputs. The `has_shortcode` + `is_singular()` guard is the standard WordPress approach and correct for this plugin's use case.
**Warning signs:** JS/CSS not loading on the registration page; or loading on every page.

---

## Code Examples

### TCPDF basic setup (Strauss-prefixed)
```php
// Source: CONTEXT.md + Strauss pattern from Phase 2
use WpMembershipRegistration\Vendor\Tecnickcom\TCPDF\TCPDF as PdfLib;

$pdf = new PdfLib( 'P', 'mm', 'A4', true, 'UTF-8', false );
$pdf->setPrintHeader( false );
$pdf->setPrintFooter( false );
$pdf->SetMargins( 20, 20, 20 );
$pdf->SetAutoPageBreak( true, 20 );
$pdf->AddPage();
$pdf->SetFont( 'dejavusans', '', 10 );
```

### TCPDF two-column field rows (filled PDF)
```php
// Source: TCPDF Cell() API + CONTEXT.md layout decision
$label_width = 50;
$pdf->SetFont( 'dejavusans', 'B', 9 );
$pdf->Cell( $label_width, 7, esc_html( $field['label'] ) . ':', 0, 0, 'R' );
$pdf->SetFont( 'dejavusans', '', 10 );
$pdf->Cell( 0, 7, esc_html( $field_values[ $field['label'] ] ?? '' ), 'B', 1, 'L' );
```

### TCPDF AcroForm TextField (blank PDF)
```php
// Source: github.com/tecnickcom/TCPDF/blob/main/examples/example_014.php
$pdf->Cell( $label_width, 7, esc_html( $field['label'] ) . ':', 0, 0, 'R' );
$pdf->TextField(
    str_replace( ' ', '_', $field['label'] ), // unique name, no spaces
    100, 6,
    array( 'lineWidth' => 0.3, 'borderStyle' => 'solid', 'fillColor' => array( 255, 255, 255 ) ),
    array()
);
$pdf->Ln( 8 );
```

### wp_editor() in settings form (form_notes)
```php
// Source: developer.wordpress.org/reference/functions/wp_editor/
$branding = get_option( 'wmr_pdf_branding', array() );
wp_editor(
    wp_kses_post( $branding['form_notes'] ?? '' ),
    'wmr-form-notes',
    array(
        'textarea_name' => 'wmr_pdf_branding[form_notes]',
        'textarea_rows' => 5,
        'media_buttons' => false,
        'teeny'         => true,
    )
);
```

### New Form Settings option registration
```php
// Source: existing SettingsRegistrar.php pattern
register_setting(
    'wmr_form_settings_group',
    'wmr_form_settings',
    array(
        'type'              => 'array',
        'sanitize_callback' => array( $this, 'sanitize_form_settings' ),
        'default'           => array(
            'consent_text'    => '',
            'success_message' => '',
        ),
    )
);

public function sanitize_form_settings( mixed $input ): array {
    if ( ! is_array( $input ) ) {
        $input = array();
    }
    return array(
        'consent_text'    => sanitize_text_field( $input['consent_text'] ?? '' ),
        'success_message' => sanitize_text_field( $input['success_message'] ?? '' ),
    );
}
```

### FormRenderer::render() output order
```php
// Source: CONTEXT.md "Frontend form rendering" decision
$form_settings = get_option( 'wmr_form_settings', array() );
$branding      = get_option( 'wmr_pdf_branding', array() );
$fields        = FieldSchema::decode( get_option( 'wmr_field_schema', '[]' ) );

// 1. Dynamic fields
foreach ( $fields as $index => $field ) { /* render <input> etc */ }

// 2. form_notes block (if set)
if ( ! empty( $branding['form_notes'] ) ) {
    echo '<div class="wmr-form-notes">' . wp_kses_post( $branding['form_notes'] ) . '</div>';
}

// 3. Consent checkbox
echo '<label class="wmr-consent">';
echo '<input type="checkbox" name="wmr_consent" value="1"> ';
echo esc_html( $form_settings['consent_text'] ?? '' );
echo '</label>';

// 4. Honeypot (hidden)
echo '<input type="text" name="website" value="" style="display:none" tabindex="-1" autocomplete="off">';

// 5. Submit
echo '<button type="submit" class="wmr-submit">' . esc_html__( 'Submit', 'wp-membership-registration' ) . '</button>';
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| DOMPDF HTML template | TCPDF PHP API + writeHTML() for rich text | Phase 4 (this phase) | Enables AcroForm fields, aligned columns, correct HTML rendering |
| Static underlines in blank PDF | TCPDF AcroForm TextField() | Phase 4 (this phase) | Members can fill blank PDF in Acrobat/Preview |
| Plain textarea for form_notes | wp_editor() TinyMCE | Phase 4 (this phase) | Club admins get WYSIWYG for legal/descriptive text |
| gdpr_text option key | form_notes option key | Phase 4 (this phase) | More accurate naming; same wp_kses_post sanitization |
| MembershipFormShortcode returns '' | Full form rendering | Phase 4 (this phase) | FORM-01 requirement fulfilled |

**Deprecated/outdated in this codebase:**
- `dompdf/dompdf`, `dompdf/php-font-lib`, `dompdf/php-svg-lib` in composer.json require and strauss.packages — remove entirely
- `templates/pdf/membership-form.php` — remove (TCPDF uses PHP API)
- `wmr_pdf_branding['gdpr_text']` — rename to `form_notes` everywhere (SettingsRegistrar, SettingsPage template, PdfGenerator, PdfGeneratorTest stubs)

---

## Open Questions

1. **TCPDF cURL dependency in Docker container**
   - What we know: TCPDF 6.11.2 requires `ext-curl`. The official `wordpress:latest` Docker image is based on PHP 8.x official image which typically includes cURL.
   - What's unclear: Whether it's compiled in the specific Docker image version used.
   - Recommendation: Add a `php -m | grep curl` check to the Wave 0 task; if absent, add `RUN docker-php-ext-install curl` to the Dockerfile (or the equivalent in the docker-compose.yml setup).

2. **PHPCS compliance for TCPDF Cell() calls**
   - What we know: WPCS enforces `esc_html()` around output. TCPDF `Cell()` takes plain strings; output goes to PDF, not HTML.
   - What's unclear: Whether PHPCS flags unescaped strings passed to TCPDF methods.
   - Recommendation: Apply `esc_html()` to all human-readable strings passed to TCPDF (both for correctness and to satisfy PHPCS), except for AcroForm field names where spaces would break things — use `sanitize_key()` or `str_replace()` for those.

3. **TinyMCE initialization on hidden tab (PDF Branding tab)**
   - What we know: The PDF Branding tab is hidden by default (`display:none`). wp_editor() docs warn about hidden container issues.
   - What's unclear: Whether the existing tab switching JS triggers TinyMCE init correctly on first reveal.
   - Recommendation: Test manually after implementation. If TinyMCE shows as plain textarea, add `tinymce.execCommand('mceAddEditor', true, 'wmr-form-notes')` on the tab-click handler. The existing `admin-settings.js` tab switching code is the right place to add this fallback.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 9.6 + Brain Monkey 2.6 |
| Config file | `phpunit.xml` (root) |
| Quick run command | `docker-compose exec wordpress phpunit --testsuite unit` |
| Full suite command | `docker-compose exec wordpress phpunit` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| FORM-01 | FormRenderer::render() returns non-empty HTML with correct field elements | unit | `phpunit tests/Unit/Frontend/FormRendererTest.php -x` | Wave 0 |
| FORM-02 | Consent checkbox HTML present in render() output; AJAX handler rejects submission without consent | unit | `phpunit tests/Unit/Frontend/FormRendererTest.php -x` | Wave 0 |
| FORM-03 | Honeypot non-empty input causes handle_submit_form() to return success without firing wmr_form_submitted | unit | `phpunit tests/Unit/Admin/AjaxHandlersTest.php -x` | Wave 0 |
| FORM-04 | Client-side validation — manual-only (JS behavior, not PHP testable in unit suite) | manual-only | N/A | N/A |
| FORM-05 | Success message from wmr_form_settings returned in JSON response | unit | `phpunit tests/Unit/Admin/AjaxHandlersTest.php -x` | Wave 0 |
| DEV-02 | All PHPUnit tests pass | unit | `phpunit` | Partial (MailerTest, PdfGeneratorTest exist) |
| DEV-04 | CONTRIBUTING.md exists and covers required topics | manual-only | N/A | Wave 0 (new file) |
| DEV-05 | README.md exists and covers required topics | manual-only | N/A | Wave 0 (new file) |

*FORM-04 (client-side JS validation) is manual-only: PHPUnit does not execute JavaScript. Server-side validation (sanitize + type check) is covered by AjaxHandlersTest.*

### Sampling Rate
- **Per task commit:** `docker-compose exec wordpress vendor/bin/phpunit tests/Unit/ --stop-on-failure`
- **Per wave merge:** `docker-compose exec wordpress vendor/bin/phpunit`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/Frontend/FormRendererTest.php` — covers FORM-01, FORM-02
- [ ] `tests/Unit/Admin/AjaxHandlersTest.php` — covers FORM-03, FORM-05
- [ ] Update `tests/Unit/Pdf/PdfGeneratorTest.php` — change `gdpr_text` stubs to `form_notes`; adapt for TCPDF (remove DOMPDF Options class usage)
- [ ] CONTRIBUTING.md at repo root — covers DEV-04
- [ ] README.md at repo root — covers DEV-05

---

## Sources

### Primary (HIGH confidence)
- [TCPDF example_014.php](https://github.com/tecnickcom/TCPDF/blob/main/examples/example_014.php) — AcroForm TextField, CheckBox, RadioButton, ComboBox method signatures
- [TCPDF example_006.php](https://github.com/tecnickcom/TCPDF/blob/main/examples/example_006.php) — writeHTML() supported tag set
- [tecnickcom/tcpdf Packagist](https://packagist.org/packages/tecnickcom/tcpdf) — version 6.11.2, PHP 7.1+ requirement
- [wp_editor() WordPress docs](https://developer.wordpress.org/reference/functions/wp_editor/) — textarea_name parameter, hidden DOM restriction
- Existing codebase: `src/Admin/AjaxHandlers.php`, `tests/Unit/Email/MailerTest.php`, `tests/bootstrap.php` — established patterns read directly

### Secondary (MEDIUM confidence)
- [TCPDF Creating Interactive Forms](https://www.squarepoint.net/creating-interactive-fillable-pdf-forms-using-tcpdf/) — confirms TextField() / RadioButton() / CheckBox() method signatures, cross-referenced against example_014.php

### Tertiary (LOW confidence)
- None — all claims verified against official TCPDF repo or WordPress docs

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — TCPDF Packagist page confirms 6.11.2 current, PHP 8.0+ compatible
- Architecture: HIGH — patterns derived from existing codebase + TCPDF official examples + WordPress docs
- Pitfalls: HIGH — most are directly drawn from STATE.md accumulated lessons (Strauss package names, Brain Monkey stub gaps, AJAX nonce localization) or confirmed in official docs (wp_editor hidden DOM)

**Research date:** 2026-03-22
**Valid until:** 2026-06-22 (TCPDF is in support-only mode; breaking changes unlikely; WordPress API stable)
