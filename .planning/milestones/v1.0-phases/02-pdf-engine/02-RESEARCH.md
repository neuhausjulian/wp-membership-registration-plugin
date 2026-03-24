# Phase 2: PDF Engine - Research

**Researched:** 2026-03-21
**Domain:** DOMPDF HTML-to-PDF generation, vendor namespace scoping (Strauss), WordPress AJAX file streaming
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **PDF header layout:** Logo top-left, club name + document title on the right, accent color separator line below. If no logo: club name + document title span full width.
- **Admin-configurable static text sections:** Four new fields added to PDF Branding settings tab: `wmr_document_title`, `wmr_gdpr_text`, `wmr_footer_text`, `wmr_page2_content`.
- **Field rendering style:** Each field as `Label:  Value` on a single line. Blank PDF uses underlines for empty values: `Name:  _______________`.
- **Document structure (page 1):** Header block → dynamic fields → GDPR paragraph → Place+date line → Signature line → Footer.
- **Page 2:** If `wmr_page2_content` is set, appended as second page. If empty, single-page PDF.
- **Blank PDF download trigger:** Shortcode parameter `[membership_form download="blank"]` renders a download link.
- **Paper format:** A4 portrait, hardcoded — no admin override for v1.
- **Temp file handling:** PDF written to `sys_get_temp_dir()` with unique filename. Deleted after use. Never written to `wp-content/uploads/`.
- **DOMPDF bundling:** Vendored via Composer and namespace-prefixed. Prefix: `WpMembershipRegistration\Vendor\`. Claude's discretion on exact scoping toolchain.

### Claude's Discretion

- Exact scoping toolchain (Strauss vs manual) for DOMPDF vendor namespacing.

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PDF-01 | Plugin generates a pre-filled PDF on every form submission, containing all submitted field values | DOMPDF `loadHtml()` + `output()` with dynamic HTML template; `PdfGenerator::generate(array $field_values): string` contract |
| PDF-02 | Generated PDF incorporates admin-configured branding (club name, logo, accent color) | Logo converted to base64 data-URI for reliable DOMPDF rendering; `get_option('wmr_pdf_branding')` for branding values; 4 new sub-keys added to same option |
| PDF-03 | A blank (empty-fields) version of the PDF template is downloadable via shortcode parameter | `wp_ajax_nopriv_wmr_download_blank_pdf` AJAX action; `readfile()` + `unlink()` pattern; shortcode parameter `download="blank"` |
</phase_requirements>

---

## Summary

Phase 2 adds the PDF engine to the plugin. Every form submission (Phase 4) and every blank-download request produces a branded, pre-filled A4 PDF using DOMPDF. The PDF is written to `sys_get_temp_dir()`, streamed to the browser or returned as a file path to Phase 3 (email), and then deleted. DOMPDF is vendored via Composer and namespace-scoped with Strauss to prevent conflicts with other WordPress plugins that may also bundle DOMPDF.

The key technical work is threefold: (1) install and scope DOMPDF under `WpMembershipRegistration\Vendor\` using Strauss, (2) build a `PdfGenerator` class that hydrates an HTML template and converts it to PDF, and (3) extend the settings to add four new branding text fields and register the blank-download AJAX handler. The HTML template approach (rather than AcroForm filling) is confirmed as the correct strategy and is already in REQUIREMENTS.md out-of-scope notes.

One critical discrepancy discovered in research: the CONTEXT.md canonical refs cite `wmr_branding_settings` as the option key but the actual code in `SettingsRegistrar.php` uses `wmr_pdf_branding`. All downstream agents must use `wmr_pdf_branding`.

**Primary recommendation:** Use DOMPDF 3.x with Strauss for namespace prefixing; use inline base64 data-URIs for the logo image; stream the PDF via `readfile()` + `unlink()` in a `wp_ajax_nopriv` handler.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| dompdf/dompdf | ^3.1 | HTML-to-PDF conversion | Mature, pure-PHP, no external binary required, GPL-compatible, PHP 8 supported |
| brianhenryie/strauss | 0.26.x (latest phar) | Namespace-prefix vendor deps | WordPress-standard solution; zero-config; actively maintained fork of Mozart |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHP ext-mbstring | bundled | DOMPDF character encoding | Required by DOMPDF — verify present in Docker image |
| PHP ext-gd | bundled | DOMPDF image rendering | Required for logo rendering — verify present in Docker image |
| PHP ext-dom | bundled | DOMPDF HTML parsing | Required by DOMPDF |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Strauss | PHP-Scoper | PHP-Scoper requires more config and a build step; Strauss infers defaults from composer.json — simpler for this use case |
| Strauss | Manual scoping (copy+rename) | Error-prone, no automation for future updates |
| DOMPDF | mPDF | mPDF is heavier and less common in the WP plugin ecosystem; DOMPDF is the established choice |
| base64 data-URI logo | `isRemoteEnabled = true` | Remote loading is a known RCE attack vector; data-URI is the safe approach |

**Installation:**
```bash
# Step 1: Add DOMPDF to composer.json require section
composer require dompdf/dompdf

# Step 2: Configure Strauss in composer.json extra section (see Architecture Patterns)
# Step 3: Download and run Strauss phar
mkdir -p bin
curl -o bin/strauss.phar -L https://github.com/BrianHenryIE/strauss/releases/latest/download/strauss.phar
php bin/strauss.phar
composer dump-autoload
```

---

## Architecture Patterns

### Recommended Project Structure
```
src/
├── Admin/
│   ├── AjaxHandlers.php       # Extend: add handle_download_blank_pdf()
│   ├── SettingsPage.php       # Extend: render 4 new fields in PDF Branding tab
│   └── SettingsRegistrar.php  # Extend: register 4 new wmr_pdf_branding sub-keys
├── Pdf/
│   └── PdfGenerator.php       # New: generates filled and blank PDFs
├── Plugin.php                 # Extend: register wp_ajax_nopriv_wmr_download_blank_pdf hook
└── Util/
    └── FieldSchema.php        # Unchanged
vendor-prefixed/               # Created by Strauss — prefixed DOMPDF files
templates/
└── pdf/
    └── membership-form.php    # New: HTML template for PDF rendering
bin/
└── strauss.phar               # Downloaded, gitignored
```

### Pattern 1: Strauss composer.json Configuration
**What:** Configure Strauss to prefix DOMPDF namespaces before distribution.
**When to use:** One-time setup; re-run after any `composer update` to DOMPDF.

```json
{
  "require": {
    "dompdf/dompdf": "^3.1"
  },
  "extra": {
    "strauss": {
      "target_directory": "vendor-prefixed",
      "namespace_prefix": "WpMembershipRegistration\\Vendor\\",
      "classmap_prefix": "WpMembershipRegistration_Vendor_",
      "packages": [
        "dompdf/dompdf",
        "phenx/php-font-lib",
        "phenx/php-svg-lib"
      ]
    }
  },
  "scripts": {
    "prefix-namespaces": [
      "sh -c 'test -f ./bin/strauss.phar || curl -o bin/strauss.phar -L -C - https://github.com/BrianHenryIE/strauss/releases/latest/download/strauss.phar'",
      "@php bin/strauss.phar",
      "@composer dump-autoload"
    ],
    "post-install-cmd": ["@prefix-namespaces"],
    "post-update-cmd": ["@prefix-namespaces"]
  }
}
```

The main plugin file must load `vendor-prefixed/autoload.php` in addition to (or instead of) the standard `vendor/autoload.php` for the scoped classes:
```php
// In wp-membership-registration.php, before Plugin::register()
require_once WMR_PLUGIN_DIR . 'vendor-prefixed/autoload.php';
```

### Pattern 2: PdfGenerator Class
**What:** Service class with two public methods: `generate()` for filled PDFs and `generateBlank()` for empty-field PDFs.
**When to use:** Called by Phase 3 Mailer (filled) and AJAX blank-download handler.

```php
// Source: DOMPDF official usage pattern
namespace WpMembershipRegistration\Pdf;

use WpMembershipRegistration\Vendor\Dompdf\Dompdf;
use WpMembershipRegistration\Vendor\Dompdf\Options;

class PdfGenerator {

    /**
     * Generate a filled PDF from submitted field values.
     * Returns the temp file path. Caller is responsible for unlink().
     *
     * @param array<string, string> $field_values Submitted field label => value pairs.
     * @return string Absolute path to the generated temp file.
     */
    public function generate( array $field_values ): string {
        $html     = $this->render_template( $field_values, false );
        $pdf_data = $this->render_pdf( $html );
        return $this->write_temp_file( $pdf_data );
    }

    /**
     * Generate a blank PDF (empty field underlines, full branding).
     * Returns the temp file path. Caller is responsible for unlink().
     *
     * @return string Absolute path to the generated temp file.
     */
    public function generate_blank(): string {
        $html     = $this->render_template( array(), true );
        $pdf_data = $this->render_pdf( $html );
        return $this->write_temp_file( $pdf_data );
    }

    private function render_pdf( string $html ): string {
        $options = new Options();
        $options->set( 'isRemoteEnabled', false );     // NEVER enable — RCE risk
        $options->set( 'isPhpEnabled', false );        // NEVER enable
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'defaultPaperSize', 'a4' );
        $options->set( 'defaultPaperOrientation', 'portrait' );

        $dompdf = new Dompdf( $options );
        $dompdf->loadHtml( $html, 'UTF-8' );
        $dompdf->setPaper( 'a4', 'portrait' );
        $dompdf->render();
        return $dompdf->output();
    }

    private function write_temp_file( string $pdf_data ): string {
        $path = sys_get_temp_dir() . '/wmr-' . wp_generate_uuid4() . '.pdf';
        file_put_contents( $path, $pdf_data );
        return $path;
    }
}
```

### Pattern 3: Logo as Base64 Data-URI
**What:** Convert the logo image to a base64-encoded data-URI so DOMPDF can render it without remote access.
**When to use:** Always — `isRemoteEnabled` must stay false.

```php
private function get_logo_data_uri(): string {
    $branding  = get_option( 'wmr_pdf_branding', array() );
    $logo_url  = $branding['logo_url'] ?? '';
    if ( empty( $logo_url ) ) {
        return '';
    }
    // Convert URL to local filesystem path.
    $upload_dir = wp_upload_dir();
    $logo_path  = str_replace(
        $upload_dir['baseurl'],
        $upload_dir['basedir'],
        $logo_url
    );
    if ( ! file_exists( $logo_path ) ) {
        return '';
    }
    $mime = mime_content_type( $logo_path );
    $data = base64_encode( file_get_contents( $logo_path ) );
    return 'data:' . $mime . ';base64,' . $data;
}
```

### Pattern 4: Blank PDF AJAX Download Handler
**What:** Respond to an unauthenticated AJAX request with a streamed PDF file, then delete the temp file.
**When to use:** `[membership_form download="blank"]` shortcode triggers a link to `admin-ajax.php?action=wmr_download_blank_pdf`.

```php
// In AjaxHandlers.php
public function handle_download_blank_pdf(): void {
    $generator = new \WpMembershipRegistration\Pdf\PdfGenerator();
    $path      = $generator->generate_blank();

    // Nonce not required for public download, but rate-limit is advisable (Phase 4+).
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="membership-form.pdf"' );
    header( 'Content-Length: ' . filesize( $path ) );
    header( 'Cache-Control: private, max-age=0, must-revalidate' );

    ob_clean();
    flush();
    readfile( $path );
    unlink( $path );
    exit;
}
```

Register in `Plugin.php`:
```php
add_action( 'wp_ajax_wmr_download_blank_pdf',        array( $ajax_handlers, 'handle_download_blank_pdf' ) );
add_action( 'wp_ajax_nopriv_wmr_download_blank_pdf', array( $ajax_handlers, 'handle_download_blank_pdf' ) );
```

### Pattern 5: Extending wmr_pdf_branding Settings
**What:** Add 4 new sub-keys to the existing `wmr_pdf_branding` option. **Important:** The existing option key in code is `wmr_pdf_branding` (not `wmr_branding_settings` as mentioned in some docs).

```php
// In SettingsRegistrar::sanitize_pdf_branding() — extend the return array:
return array(
    'club_name'      => sanitize_text_field( $input['club_name'] ?? '' ),
    'logo_url'       => esc_url_raw( $input['logo_url'] ?? '' ),
    'accent_color'   => $accent_color ? $accent_color : '#2271b1',
    'document_title' => sanitize_text_field( $input['document_title'] ?? '' ),
    'gdpr_text'      => wp_kses_post( $input['gdpr_text'] ?? '' ),
    'footer_text'    => wp_kses_post( $input['footer_text'] ?? '' ),
    'page2_content'  => wp_kses_post( $input['page2_content'] ?? '' ),
);
```

Use `wp_kses_post()` for textarea fields that may contain HTML links (GDPR text often includes links to privacy policy pages).

### Anti-Patterns to Avoid
- **`isRemoteEnabled = true`:** DOMPDF had an RCE vulnerability exploited through remote font loading. Never enable remote resources.
- **`isPhpEnabled = true`:** Allows arbitrary PHP execution in HTML templates. Never enable.
- **Writing PDFs to `wp-content/uploads/`:** Exposes member PII; violates design decision.
- **Storing the logo URL directly in the `<img src>` tag:** DOMPDF with `isRemoteEnabled = false` cannot fetch URLs — must use base64 data-URI.
- **Calling `file_get_contents()` on the logo URL:** Fetches via HTTP; use filesystem path instead.
- **Using `Dompdf\Dompdf` (unscoped) after Strauss:** Must use `WpMembershipRegistration\Vendor\Dompdf\Dompdf`.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| HTML → PDF conversion | Custom PDF byte generation | DOMPDF | PDF binary format is extraordinarily complex; font embedding, page layout, unicode, compression — all handled |
| Namespace conflict prevention | Copy-paste and rename vendor files | Strauss | Strauss tracks original names, handles classmap, generates autoloader, handles license compliance |
| Unique temp filenames | Custom random-string generator | `wp_generate_uuid4()` | WordPress built-in, cryptographically random, no collisions |
| Image MIME type detection | Extension-based lookup | `mime_content_type()` | PHP built-in, handles edge cases |
| Output buffering before file stream | Manual `ob_*` calls | `ob_clean(); flush();` standard pattern | Prevents partial output corruption before `readfile()` |

**Key insight:** PDF generation involves hundreds of edge cases (Unicode text flow, font subsetting, page breaks, image encoding). DOMPDF handles all of them — the plugin's job is only to produce correct HTML for DOMPDF to consume.

---

## Common Pitfalls

### Pitfall 1: Logo Image Not Rendering
**What goes wrong:** Logo appears broken or missing in the generated PDF.
**Why it happens:** DOMPDF with `isRemoteEnabled = false` cannot fetch URLs. Using the logo URL directly in `<img src>` fails silently.
**How to avoid:** Convert logo to base64 data-URI using the filesystem path (Pattern 3 above). Always resolve URL to path via `wp_upload_dir()`.
**Warning signs:** PDF generates without error but logo area is blank.

### Pitfall 2: Scoped Namespace Not Used in PdfGenerator
**What goes wrong:** Fatal error: `Class 'Dompdf\Dompdf' not found` or wrong version loaded from another plugin.
**Why it happens:** After Strauss runs, the classes live under `WpMembershipRegistration\Vendor\Dompdf\` not `Dompdf\`. Using the original namespace loads from `vendor/` (unscoped) or fails entirely.
**How to avoid:** Always import `use WpMembershipRegistration\Vendor\Dompdf\Dompdf;` in `PdfGenerator.php`. Do not `use Dompdf\Dompdf;`.
**Warning signs:** Works locally but breaks on sites with another plugin that also bundles DOMPDF.

### Pitfall 3: vendor-prefixed/ Not Loaded
**What goes wrong:** Strauss creates `vendor-prefixed/` but the plugin never loads its autoloader.
**Why it happens:** The standard `vendor/autoload.php` does not include Strauss-prefixed classes.
**How to avoid:** Add `require_once WMR_PLUGIN_DIR . 'vendor-prefixed/autoload.php';` in the main plugin file before `Plugin::register()`.
**Warning signs:** Fatal error on any DOMPDF operation even after Strauss ran.

### Pitfall 4: PDF Written After Headers Sent
**What goes wrong:** Partial output followed by the PDF binary, producing a corrupt download.
**Why it happens:** WordPress or another plugin outputs HTML before the AJAX handler runs. `ob_clean()` is not called before `readfile()`.
**How to avoid:** Call `ob_clean(); flush();` immediately before `readfile()`. Register AJAX handlers early so they fire before theme output.
**Warning signs:** Downloaded PDF file is larger than expected and unreadable by PDF viewers.

### Pitfall 5: Option Key Mismatch
**What goes wrong:** New branding fields (`document_title`, `gdpr_text`, etc.) are never saved or retrieved.
**Why it happens:** CONTEXT.md canonical refs mention `wmr_branding_settings` but the actual code uses `wmr_pdf_branding`. If a developer reads the context doc and uses the wrong key, settings are silently lost.
**How to avoid:** Use `get_option('wmr_pdf_branding')` everywhere. The canonical option key is `wmr_pdf_branding` (confirmed in `src/Admin/SettingsRegistrar.php`).
**Warning signs:** Settings page saves without error but PDF shows no branding.

### Pitfall 6: Strauss Not Re-Run After DOMPDF Update
**What goes wrong:** `composer update dompdf/dompdf` regenerates `vendor/` but `vendor-prefixed/` still has the old version. Two versions of DOMPDF coexist.
**Why it happens:** Strauss only runs when explicitly invoked (or via Composer scripts).
**How to avoid:** Configure `post-update-cmd` in `composer.json` to always re-run Strauss. Document in DEVELOPMENT.md.
**Warning signs:** `vendor/dompdf/` shows new version; `vendor-prefixed/` shows old.

### Pitfall 7: Temp File Left on Disk After Exception
**What goes wrong:** If PDF generation throws an exception, the temp file may not be deleted.
**Why it happens:** `unlink()` is called after `readfile()` — if an exception occurs earlier, cleanup is skipped.
**How to avoid:** Use try/finally to guarantee `unlink()` is called:
```php
try {
    readfile( $path );
} finally {
    @unlink( $path );
}
```
**Warning signs:** Temp directory accumulates `.wmr-*.pdf` files over time.

---

## Code Examples

### HTML Template Structure (membership-form.php)
```php
// Source: DOMPDF documentation + CONTEXT.md layout specification
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 20mm 20mm 25mm 20mm; size: A4 portrait; }
  body   { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; }
  .header      { width: 100%; margin-bottom: 4mm; }
  .header-logo { float: left; max-height: 18mm; max-width: 40mm; }
  .header-text { margin-left: 45mm; }
  .separator   { height: 1mm; background-color: <?php echo esc_attr( $accent_color ); ?>; clear: both; margin: 2mm 0 4mm; }
  .field-row   { margin: 2mm 0; }
  .field-label { font-weight: normal; }
  .footer      { position: fixed; bottom: 0; width: 100%; font-size: 8pt; border-top: 0.5pt solid #ccc; }
</style>
</head>
<body>
  <!-- Header -->
  <div class="header">
    <?php if ( $logo_data_uri ) : ?>
      <img class="header-logo" src="<?php echo esc_attr( $logo_data_uri ); ?>" alt="">
      <div class="header-text">
    <?php else : ?>
      <div class="header-text" style="margin-left:0">
    <?php endif; ?>
        <strong><?php echo esc_html( $club_name ); ?></strong><br>
        <?php echo esc_html( $document_title ); ?>
      </div>
  </div>
  <div class="separator"></div>

  <!-- Dynamic Fields -->
  <?php foreach ( $fields as $field ) : ?>
    <div class="field-row">
      <span class="field-label"><?php echo esc_html( $field['label'] ); ?>:</span>
      <?php if ( $is_blank ) : ?>
        &nbsp;&nbsp;___________________________
      <?php else : ?>
        &nbsp;&nbsp;<?php echo esc_html( $field_values[ $field['label'] ] ?? '' ); ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <!-- GDPR / Consent -->
  <?php if ( $gdpr_text ) : ?>
    <div class="gdpr"><?php echo wp_kses_post( $gdpr_text ); ?></div>
  <?php endif; ?>

  <!-- Place + Date -->
  <div class="field-row">Ort, Datum: &nbsp;&nbsp;___________________________</div>

  <!-- Signature -->
  <div class="field-row">Unterschrift: &nbsp;&nbsp;___________________________</div>

  <!-- Footer (fixed at bottom) -->
  <?php if ( $footer_text ) : ?>
    <div class="footer"><?php echo wp_kses_post( $footer_text ); ?></div>
  <?php endif; ?>
</body>
</html>
```

### DOMPDF Instantiation with Secure Options
```php
// Source: DOMPDF Options.php (https://github.com/dompdf/dompdf/blob/master/src/Options.php)
use WpMembershipRegistration\Vendor\Dompdf\Dompdf;
use WpMembershipRegistration\Vendor\Dompdf\Options;

$options = new Options();
$options->set( 'isRemoteEnabled', false );      // SECURITY: must stay false
$options->set( 'isPhpEnabled', false );         // SECURITY: must stay false
$options->set( 'isHtml5ParserEnabled', true );  // Handles modern HTML gracefully
$options->set( 'defaultPaperSize', 'a4' );
$options->set( 'defaultPaperOrientation', 'portrait' );
$options->set( 'chroot', sys_get_temp_dir() );  // Further restrict filesystem access

$dompdf = new Dompdf( $options );
$dompdf->loadHtml( $html, 'UTF-8' );
$dompdf->setPaper( 'a4', 'portrait' );
$dompdf->render();
$pdf_bytes = $dompdf->output();
```

### PHPCS Compliant File Writing
```php
// WordPress coding standards require using WP filesystem for writes in some contexts.
// For temp files (not in WP directories), native PHP file_put_contents is acceptable.
$path = sys_get_temp_dir() . '/wmr-' . wp_generate_uuid4() . '.pdf';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
file_put_contents( $path, $pdf_bytes );
```

Add the inline PHPCS ignore comment because WPCS flags `file_put_contents` as preferring WP_Filesystem — but WP_Filesystem is designed for files within WP directories, not system temp.

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Mozart for namespace scoping | Strauss (Mozart fork) | 2021+ | Strauss is actively maintained; Mozart is abandoned |
| DOMPDF 1.x / 2.x | DOMPDF 3.x (3.1.5 as of 2026-03-03) | Jan 2025 (3.0) | CSS custom properties (calc, etc.), better font matching, security defaults |
| `isRemoteEnabled = true` as default | `false` as default in 3.x | DOMPDF 3.0 | Must not re-enable; was a historical RCE vector |
| Writing PDF to `uploads/` | `sys_get_temp_dir()` + `unlink()` | Best practice | No persistent PII storage |

**Deprecated/outdated:**
- `dompdf/dompdf` < 2.0.0: Multiple CVEs fixed in 2.0.0 (GHSA-5QJ8-6XXJ-HP9H and related). Use 3.x.
- Mozart: Unmaintained. Strauss is its active successor.
- `$options->set('isPhpEnabled', true)`: Was sometimes used for dynamic content in old tutorials. Never use.

---

## Open Questions

1. **DOMPDF dependency chain scope**
   - What we know: DOMPDF 3.x depends on `phenx/php-font-lib` and `phenx/php-svg-lib`. Strauss must prefix these too.
   - What's unclear: Whether any of these transitively depend on other packages that need scoping.
   - Recommendation: Run Strauss with `"packages": ["dompdf/dompdf", "phenx/php-font-lib", "phenx/php-svg-lib"]` explicitly listed. Verify `vendor-prefixed/` output after first run.

2. **WPCS compliance for `file_put_contents` and `readfile`**
   - What we know: WPCS flags `file_put_contents` (prefers WP_Filesystem) and may flag `readfile` similarly.
   - What's unclear: Exact sniff names that fire for system temp operations.
   - Recommendation: Use targeted `// phpcs:ignore` comments with sniff name (e.g., `WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents`). Run PHPCS during Wave 1 verify step and add ignores as needed.

3. **PHP extension availability in Docker image**
   - What we know: DOMPDF requires `ext-mbstring`, `ext-gd`, `ext-dom`. The project uses `wordpress:latest` Docker image.
   - What's unclear: Whether `ext-gd` is enabled by default in the official WordPress image.
   - Recommendation: Add a Wave 0 task to verify `phpinfo()` or `php -m` confirms all three extensions are loaded. If not, add a Dockerfile that extends `wordpress:latest` with `docker-php-ext-install gd`.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (to be installed — DEV-02 is Phase 4, but unit tests for PdfGenerator can start now) |
| Config file | None — Wave 0 gap |
| Quick run command | `./vendor/bin/phpunit tests/Unit/ --no-coverage` |
| Full suite command | `./vendor/bin/phpunit --no-coverage` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PDF-01 | `PdfGenerator::generate()` returns a path to a file ending in `.pdf` | unit (Brain Monkey) | `./vendor/bin/phpunit tests/Unit/Pdf/PdfGeneratorTest.php -x` | Wave 0 |
| PDF-01 | Generated file contains UTF-8 PDF header `%PDF` | unit | `./vendor/bin/phpunit tests/Unit/Pdf/PdfGeneratorTest.php::test_generate_produces_valid_pdf_file -x` | Wave 0 |
| PDF-02 | HTML template includes club name and accent color from options | unit (Brain Monkey) | `./vendor/bin/phpunit tests/Unit/Pdf/PdfGeneratorTest.php::test_branding_fields_appear_in_template -x` | Wave 0 |
| PDF-03 | `generate_blank()` returns path to file; field values are empty underlines | unit | `./vendor/bin/phpunit tests/Unit/Pdf/PdfGeneratorTest.php::test_blank_pdf_contains_underlines -x` | Wave 0 |
| PDF-03 | AJAX handler sets `Content-Type: application/pdf` and streams file | manual | Manual browser test via `[membership_form download="blank"]` | manual-only |

Note: Full PDF rendering tests that invoke DOMPDF itself are integration-level (require the Docker WordPress environment) and are in scope for Phase 4's DEV-02 requirement, not Phase 2. Phase 2 unit tests should mock `PdfGenerator` internals where possible and test the HTML template output directly.

### Sampling Rate
- **Per task commit:** `composer phpcs` (PHPCS — already configured)
- **Per wave merge:** `./vendor/bin/phpunit tests/Unit/ --no-coverage`
- **Phase gate:** PHPUnit unit suite green + PHPCS clean before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/Pdf/PdfGeneratorTest.php` — covers PDF-01, PDF-02, PDF-03
- [ ] `tests/bootstrap.php` — Brain Monkey setup
- [ ] `phpunit.xml` — test suite configuration
- [ ] PHPUnit + Brain Monkey install: `composer require --dev phpunit/phpunit brain/monkey`

---

## Sources

### Primary (HIGH confidence)
- [dompdf/dompdf Packagist](https://packagist.org/packages/dompdf/dompdf) — version 3.1.5 confirmed, PHP requirements
- [dompdf/dompdf GitHub releases](https://github.com/dompdf/dompdf/releases) — 3.1.5 latest, 3.0 security changes
- [BrianHenryIE/strauss GitHub](https://github.com/BrianHenryIE/strauss) — installation pattern, composer.json config
- `src/Admin/SettingsRegistrar.php` — confirmed option key is `wmr_pdf_branding` (not `wmr_branding_settings`)
- `wp-membership-registration.php` — confirmed PSR-4 loader pattern, PHP 8.0 minimum

### Secondary (MEDIUM confidence)
- [WordPress developer docs: get_temp_dir()](https://developer.wordpress.org/reference/functions/get_temp_dir/) — `sys_get_temp_dir()` is preferred
- [WordPress developer docs: wp_ajax_nopriv hook](https://developer.wordpress.org/reference/hooks/wp_ajax_nopriv_action/) — public AJAX pattern
- [Positive Security: dompdf RCE writeup](https://positive.security/blog/dompdf-rce) — confirms isRemoteEnabled=false is mandatory
- [DEV Community: base64 images in Laravel DomPDF](https://dev.to/bhaidar/efficiently-rendering-base64-images-in-laravel-pdfs-with-dompdf-16pk) — base64 data-URI approach for images

### Tertiary (LOW confidence)
- [wpovernight/strauss GitHub](https://github.com/wpovernight/strauss) — alternative fork, less actively maintained than BrianHenryIE's

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — DOMPDF version confirmed from Packagist; Strauss version/config confirmed from GitHub README
- Architecture: HIGH — confirmed against existing codebase; option keys verified in source
- Pitfalls: HIGH — isRemoteEnabled RCE confirmed via CVE documentation; option key mismatch discovered via direct code inspection
- Test strategy: MEDIUM — PHPUnit/Brain Monkey is standard but not yet installed; wave 0 gaps listed

**Research date:** 2026-03-21
**Valid until:** 2026-04-20 (DOMPDF 3.x is stable; Strauss phar URL is rolling-latest)
