# Phase 1: Plugin Foundation and Settings - Research

**Researched:** 2026-03-20
**Domain:** WordPress plugin architecture, Settings API, Docker local dev, PHPCS/WPCS
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- Settings page lives under Settings › Membership Registration (WP standard convention)
- Three tabs: Form Fields | PDF Branding | Email Settings
- Tab switching is JavaScript-driven (single page load, JS shows/hides sections) — not separate WP settings pages per tab
- All settings saved via a single save button per tab (standard WP settings pattern — no auto-save)
- Drag-and-drop row reordering uses jQuery UI Sortable (already bundled with WordPress)
- Drag handle on the left of each row; rows show: label input, field type dropdown, required toggle, delete button
- New rows default to field type "text"
- Field types supported: text, email, date, tel, textarea, select
- Field schema stored as serialized JSON in a single WP option
- Club logo uses WP Media Library picker (standard `wp.media` frame) — stores the attachment URL in options
- Accent color uses WP iris color picker (`wp-color-picker`) — ships with WordPress, no extra dependency
- Club name: plain text input
- One or more recipient email addresses (format at Claude's discretion)
- "Send test email" button lives on the Email Settings tab, triggers an AJAX call
- Official `wordpress` image (PHP + Apache) as the Docker base
- Services: WordPress + MySQL + Mailpit + Adminer
- Plugin source mounted as a volume into the WordPress plugins directory
- `docker compose up` is the single launch command
- WordPress Coding Standards (WPCS) enforced via PHPCS with a `.phpcs.xml` config
- CI runs PHPCS on plugin source files; zero violations required to pass

### Claude's Discretion

- Exact column widths and visual spacing in the field schema editor table
- Whether recipient emails are stored comma-separated or one-per-line (textarea)
- Port assignments for Mailpit (UI) and Adminer in docker-compose.yml
- WordPress version pinned in docker-compose.yml (latest or specific)
- Composer or manual PHPCS setup (whichever fits contributor workflow better)

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| CONF-01 | Admin can define registration form fields via settings page (label, field type, required toggle) | Settings API + custom JS repeater + serialized JSON option pattern documented below |
| CONF-02 | Admin can add, remove, and reorder form fields in the settings UI | jQuery UI Sortable (bundled with WP) for drag reorder; JS for add/delete rows; pattern documented below |
| CONF-03 | Admin can configure PDF branding: club name, logo upload, accent color | wp.media frame for logo; wp-color-picker for accent; plain text input for name; all via Settings API |
| CONF-04 | Admin can configure one or more recipient email addresses for PDF notification emails | Textarea (one per line) stored as single WP option; sanitize_textarea_field + explode on save |
| CONF-05 | Admin can send a test email from the settings page to verify SMTP delivery | wp_ajax_ handler + check_ajax_referer + wp_mail(); wp_send_json_success/error pattern |
| DEV-01 | Plugin ships a Docker-based local dev environment using docker-compose.yml with the official WordPress image | docker-compose.yml with wordpress + mysql + mailpit + adminer; volume mount pattern documented below |
| DEV-03 | WordPress coding standards enforced via PHPCS/WPCS with a .phpcs.xml config that runs in CI | WPCS 3.x via Composer; .phpcs.xml config; `vendor/bin/phpcs` command |
</phase_requirements>

---

## Summary

This phase establishes the entire plugin skeleton and its settings infrastructure. WordPress provides most of what is needed out of the box: the Settings API for option storage and form rendering, jQuery UI (bundled) for drag-and-drop, `wp-color-picker` (bundled) for the accent color, and `wp.media` (bundled) for the logo upload. No external JS libraries need to be downloaded for the settings UI.

The only non-WordPress dependencies are Composer dev tools: `phpcs` and `wp-coding-standards/wpcs` for code quality. The Docker environment combines three official or well-maintained images (`wordpress`, `mysql`, `axllent/mailpit`) and the lightweight `adminer` image.

The repeater field editor (Form Fields tab) is the most complex piece: the Settings API does not natively support array-of-objects options, so the schema is stored as a JSON-encoded string in a single `wp_options` row. The PHP side encodes/decodes; the JS side builds, serializes, and submits the table rows on save.

**Primary recommendation:** Use the WordPress Settings API with a single option per tab, custom JS for the repeater/sortable, and all bundled WP dependencies. Set up WPCS via Composer for consistent tooling. Use `docker compose up` (V2 syntax, no hyphen) throughout.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| WordPress Settings API | Built into WP 6.0+ | Option registration, nonce handling, form submission to options.php | Official WP mechanism; handles sanitization callbacks, capability checks, nonces automatically |
| jQuery UI Sortable | Bundled with WP (jQuery UI 1.13.x) | Drag-and-drop row reordering in the field editor | Already available in wp-admin; no extra download or enqueue dependency |
| wp-color-picker (Iris) | Bundled with WP 3.5+ | Accent color picker | Ships with WordPress; no CDN or Composer dependency |
| wp.media JS API | Bundled with WP | Media Library file picker for logo upload | Official WP media frame; handles upload, library browse, URL retrieval |
| wp_mail() | Built into WP | Send test email via AJAX handler | Native WP function; respects any SMTP plugin or `WORDPRESS_CONFIG_EXTRA` override |
| wp_ajax_ hooks | Built into WP | AJAX endpoint for "Send test email" button | Official secure admin AJAX mechanism with nonce verification |

### Supporting (Dev / Tooling)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| squizlabs/php_codesniffer | ^3.8 | Runs the sniffs | Required by WPCS 3.x |
| wp-coding-standards/wpcs | ^3.0 | WPCS sniff rules | Enforces WP PHP coding standards |
| dealerdirect/phpcodesniffer-composer-installer | ^1.0 | Auto-registers WPCS with PHPCS after `composer install` | Removes manual `--config-set installed_paths` step |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| jQuery UI Sortable (bundled) | SortableJS, dragula | Extra enqueue, extra Composer/npm dependency for something WP already ships |
| wp-color-picker (bundled) | Spectrum, vanilla color input | Extra dependency; iris is already on every WP admin page |
| WPCS via Composer | Global PHPCS install | Composer is reproducible; global install breaks on contributor machines with different PHPCS versions |

**Installation (dev dependencies only):**
```bash
composer require --dev \
  squizlabs/php_codesniffer:"^3.8" \
  wp-coding-standards/wpcs:"^3.0" \
  dealerdirect/phpcodesniffer-composer-installer:"^1.0"
```

Also add to `composer.json`:
```json
{
  "config": {
    "allow-plugins": {
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  }
}
```

---

## Architecture Patterns

### Recommended Project Structure

```
wp-membership-registration-plugin/
├── wp-membership-registration.php   # Plugin header, bootstrap (require autoload, instantiate Plugin)
├── composer.json                    # Dev dependencies: phpcs, wpcs
├── composer.lock
├── .phpcs.xml                       # PHPCS configuration
├── docker-compose.yml               # Local dev: WP + MySQL + Mailpit + Adminer
├── vendor/                          # Composer-managed (gitignored except bin)
├── src/
│   ├── Plugin.php                   # Main class: registers all hooks
│   ├── Admin/
│   │   ├── SettingsPage.php         # admin_menu + page render
│   │   ├── SettingsRegistrar.php    # admin_init: register_setting, sections, fields
│   │   └── AjaxHandlers.php         # wp_ajax_ handlers (test email)
│   └── Util/
│       └── FieldSchema.php          # encode/decode field schema JSON
├── assets/
│   ├── js/
│   │   └── admin-settings.js        # Tab switching, sortable, repeater, media picker, color picker init
│   └── css/
│       └── admin-settings.css       # Settings page styles
└── templates/
    └── admin-settings-page.php      # HTML template for the settings page
```

**Namespace:** `WpMembershipRegistration` (PSR-4, mapped to `src/` in composer.json)

**Note on file naming:** WordPress Coding Standards require class files named `class-{slug}.php`. With Composer PSR-4 autoloading the class file names follow PSR-4 (`SettingsPage.php`). WPCS 3.x provides a sniff (`WordPress.Files.FileName`) that can be configured to allow PSR-4 filenames — add `<property name="strict_class_file_names" value="false"/>` to `.phpcs.xml` to avoid false positives when using a `src/` layout.

### Pattern 1: Plugin Bootstrap (main file)

**What:** Main plugin file contains only the WP header comment, version constants, and a single instantiation call after the autoloader is loaded.
**When to use:** Every WordPress plugin — keeps the root file minimal and testable.

```php
<?php
/**
 * Plugin Name: WP Membership Registration
 * Description: Digital membership registration with PDF generation.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-membership-registration
 */

// Source: WordPress Plugin Handbook — plugin header spec
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

( new \WpMembershipRegistration\Plugin() )->register();
```

### Pattern 2: Settings API with Tabs (JS-driven)

**What:** Register all three tabs under one `options_group` (or one option name per tab). Render a single settings page; JavaScript shows/hides tab panels on click.
**When to use:** Multi-tab settings pages where you don't want a page reload per tab.

```php
// Source: developer.wordpress.org/plugins/settings/settings-api/
// In SettingsRegistrar::register() — called on admin_init

register_setting(
    'wmr_form_fields_group',       // option_group
    'wmr_field_schema',             // option_name
    [
        'type'              => 'string',
        'sanitize_callback' => [ $this, 'sanitize_field_schema' ],
        'default'           => '[]',
    ]
);

register_setting(
    'wmr_pdf_branding_group',
    'wmr_pdf_branding',
    [
        'type'              => 'array',
        'sanitize_callback' => [ $this, 'sanitize_pdf_branding' ],
        'default'           => [],
    ]
);

register_setting(
    'wmr_email_settings_group',
    'wmr_email_settings',
    [
        'type'              => 'array',
        'sanitize_callback' => [ $this, 'sanitize_email_settings' ],
        'default'           => [],
    ]
);
```

Tab switching HTML pattern (rendered in `templates/admin-settings-page.php`):
```html
<!-- Each tab form posts to options.php with its own hidden_fields() -->
<div class="wrap">
  <h1>Membership Registration Settings</h1>
  <nav class="nav-tab-wrapper wmr-tab-nav">
    <a href="#wmr-tab-fields"    class="nav-tab nav-tab-active">Form Fields</a>
    <a href="#wmr-tab-branding"  class="nav-tab">PDF Branding</a>
    <a href="#wmr-tab-email"     class="nav-tab">Email Settings</a>
  </nav>

  <div id="wmr-tab-fields"   class="wmr-tab-panel">
    <form method="post" action="options.php">
      <?php settings_fields( 'wmr_form_fields_group' ); ?>
      <!-- repeater table rendered here -->
      <?php submit_button(); ?>
    </form>
  </div>

  <div id="wmr-tab-branding" class="wmr-tab-panel" style="display:none">
    <form method="post" action="options.php">
      <?php settings_fields( 'wmr_pdf_branding_group' ); ?>
      <?php do_settings_sections( 'wmr-pdf-branding' ); ?>
      <?php submit_button(); ?>
    </form>
  </div>

  <div id="wmr-tab-email"    class="wmr-tab-panel" style="display:none">
    <form method="post" action="options.php">
      <?php settings_fields( 'wmr_email_settings_group' ); ?>
      <?php do_settings_sections( 'wmr-email-settings' ); ?>
      <button id="wmr-send-test-email" class="button">Send test email</button>
      <?php submit_button(); ?>
    </form>
  </div>
</div>
```

### Pattern 3: Field Schema Repeater (JS + JSON)

**What:** A `<tbody>` of rows built by JavaScript. On save the JS serializes rows to a hidden JSON input; PHP decodes and re-encodes on save.
**When to use:** Any time the Settings API needs to store an ordered list of objects.

```javascript
// Source: jQuery UI Sortable API docs + WordPress bundled jQuery UI
// admin-settings.js — init after DOM ready

jQuery(function($) {
    // Sortable rows
    $('#wmr-fields-tbody').sortable({
        handle: '.wmr-drag-handle',
        axis: 'y',
        update: serializeSchema,
    });

    // Add row
    $('#wmr-add-field').on('click', function() {
        addRow({ label: '', type: 'text', required: false });
        serializeSchema();
    });

    // Delete row (delegated)
    $('#wmr-fields-tbody').on('click', '.wmr-delete-row', function() {
        $(this).closest('tr').remove();
        serializeSchema();
    });

    // Serialize current rows → hidden input before form submit
    $('form').on('submit', serializeSchema);

    function serializeSchema() {
        var rows = [];
        $('#wmr-fields-tbody tr').each(function() {
            rows.push({
                label:    $(this).find('.wmr-label').val(),
                type:     $(this).find('.wmr-type').val(),
                required: $(this).find('.wmr-required').is(':checked'),
            });
        });
        $('#wmr-field-schema-json').val(JSON.stringify(rows));
    }
});
```

PHP sanitize callback:
```php
public function sanitize_field_schema( string $raw ): string {
    $decoded = json_decode( wp_unslash( $raw ), true );
    if ( ! is_array( $decoded ) ) {
        return '[]';
    }
    $allowed_types = [ 'text', 'email', 'date', 'tel', 'textarea', 'select' ];
    $clean = [];
    foreach ( $decoded as $row ) {
        if ( ! is_array( $row ) ) continue;
        $clean[] = [
            'label'    => sanitize_text_field( $row['label'] ?? '' ),
            'type'     => in_array( $row['type'] ?? '', $allowed_types, true )
                            ? $row['type']
                            : 'text',
            'required' => ! empty( $row['required'] ),
        ];
    }
    return wp_json_encode( $clean );
}
```

### Pattern 4: wp.media Logo Picker

**What:** A "Select Logo" button opens the WP Media Library frame. On select, stores the attachment URL in a hidden text input and previews the image.
**When to use:** Any settings field that should reference a media library file.

```javascript
// Source: codex.wordpress.org/Javascript_Reference/wp.media
var mediaFrame;
$('#wmr-select-logo').on('click', function(e) {
    e.preventDefault();
    if ( mediaFrame ) { mediaFrame.open(); return; }
    mediaFrame = wp.media({
        title:    'Select Club Logo',
        button:   { text: 'Use this image' },
        multiple: false,
        library:  { type: 'image' },
    });
    mediaFrame.on('select', function() {
        var attachment = mediaFrame.state().get('selection').first().toJSON();
        $('#wmr-logo-url').val( attachment.url );
        $('#wmr-logo-preview').attr('src', attachment.url).show();
    });
    mediaFrame.open();
});
```

PHP dependency for enqueue:
```php
// wp_enqueue_media() must be called on admin_enqueue_scripts for the page
wp_enqueue_media();
```

### Pattern 5: wp-color-picker (Iris)

```php
// In SettingsPage::enqueue_scripts()
wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script(
    'wmr-admin-settings',
    plugins_url( 'assets/js/admin-settings.js', WMR_PLUGIN_FILE ),
    [ 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-mediaelement' ],
    WMR_VERSION,
    true
);
```

```javascript
// Initialize color picker in admin-settings.js
$('.wmr-color-picker').wpColorPicker();
```

HTML input:
```html
<input type="text" name="wmr_pdf_branding[accent_color]"
       class="wmr-color-picker"
       value="<?php echo esc_attr( $accent_color ); ?>">
```

### Pattern 6: AJAX — Send Test Email

```php
// Source: developer.wordpress.org/plugins/javascript/ajax/
// In AjaxHandlers — hooked on wp_ajax_wmr_send_test_email

add_action( 'wp_ajax_wmr_send_test_email', [ $this, 'handle_send_test_email' ] );

public function handle_send_test_email(): void {
    check_ajax_referer( 'wmr_send_test_email', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'wp-membership-registration' ) ], 403 );
    }

    $settings  = get_option( 'wmr_email_settings', [] );
    $recipients = array_filter( array_map( 'sanitize_email',
        explode( "\n", $settings['recipients'] ?? '' )
    ) );

    if ( empty( $recipients ) ) {
        wp_send_json_error( [ 'message' => __( 'No recipient configured.', 'wp-membership-registration' ) ] );
    }

    $sent = wp_mail(
        $recipients,
        __( '[Test] Membership Registration email', 'wp-membership-registration' ),
        __( 'This is a test email from the WP Membership Registration plugin.', 'wp-membership-registration' )
    );

    if ( $sent ) {
        wp_send_json_success( [ 'message' => __( 'Test email sent.', 'wp-membership-registration' ) ] );
    } else {
        wp_send_json_error( [ 'message' => __( 'wp_mail() returned false — check SMTP settings.', 'wp-membership-registration' ) ] );
    }
}
```

Nonce localization:
```php
wp_localize_script( 'wmr-admin-settings', 'wmrSettings', [
    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
    'testEmailNonce' => wp_create_nonce( 'wmr_send_test_email' ),
] );
```

### Anti-Patterns to Avoid

- **Separate WP settings page per tab:** The user explicitly chose JS-driven tabs on a single page load — do NOT use `?page=wmr&tab=branding` as a WP sub-menu page; use one `add_options_page()` call.
- **Auto-save / live updates:** No AJAX auto-save; each tab has an explicit submit button posting to `options.php`.
- **Storing field schema as PHP-serialized data:** WordPress recommends JSON for structured data in options — PHP serialization is fragile across server migrations and character encoding issues.
- **Calling `wp_enqueue_media()` globally on all admin pages:** Hook `admin_enqueue_scripts` and check `$hook_suffix` to ensure media scripts only load on the plugin's settings page.
- **Outputting raw option values without escaping:** Always `esc_attr()` for attribute contexts, `esc_html()` for text nodes, `esc_url()` for URLs.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Nonce generation and verification | Custom token logic | `wp_create_nonce()` + `check_ajax_referer()` | WP nonces handle timing, user session, salting |
| Options storage with sanitization | Direct `$wpdb` queries | `register_setting()` with sanitize_callback + `get_option()` / `update_option()` | Settings API handles capability checks, option validation, and update hooks |
| Media upload dialog | Custom file input + upload handler | `wp.media` JS frame + `wp_enqueue_media()` | Handles chunked upload, library reuse, image preview, attachment metadata |
| Color picker widget | `<input type="color">` or custom widget | `wp-color-picker` (Iris) | Consistent with WP admin UI; handles hex validation, swatches, alpha |
| Drag-and-drop ordering | Custom mouse event handling | `jQuery UI Sortable` (bundled) | Battle-tested; handles touch, keyboard, scroll-container edge cases |
| SMTP email capture in dev | Configuring real SMTP | Mailpit Docker service | Zero-config local mail catcher; web UI at port 8025 |
| PHP dependency management for sniffers | Manual PHPCS install instructions | Composer `require --dev` | Reproducible across all contributor machines |

**Key insight:** WordPress admin already ships every interactive widget needed for this settings page. Zero additional JS downloads are required.

---

## Common Pitfalls

### Pitfall 1: `settings_fields()` group mismatch
**What goes wrong:** Saving one tab's form updates a different option, or nonce verification fails with "are you sure?" error.
**Why it happens:** `settings_fields('my_group')` must match the `$option_group` parameter in `register_setting('my_group', ...)` exactly.
**How to avoid:** Use one named group constant per tab (e.g., `WMR_FORM_FIELDS_GROUP = 'wmr_form_fields_group'`) and reference the constant in both registration and the template.
**Warning signs:** WP admin shows "Settings saved." but option value in database hasn't changed; or redirect to `options.php?error` instead of options-general.php.

### Pitfall 2: `wp_unslash()` missing before JSON decode
**What goes wrong:** JSON decode fails because WordPress double-slashes quotes in POST data (`\"`).
**Why it happens:** WordPress applies `wp_magic_quotes()` on all `$_POST` data before it reaches plugin code.
**How to avoid:** Always call `wp_unslash( $_POST['my_field'] )` or `wp_unslash( $raw )` before `json_decode()`.
**Warning signs:** `json_decode()` returns `null`; `json_last_error()` returns `JSON_ERROR_SYNTAX`.

### Pitfall 3: `wp_enqueue_media()` on all admin pages
**What goes wrong:** Media scripts conflict with other plugins' admin pages; slower admin loads.
**Why it happens:** Registering `wp_enqueue_media()` on `admin_enqueue_scripts` without a page check loads it everywhere.
**How to avoid:** Check `$hook_suffix` matches the plugin settings page: `if ( $hook_suffix === $this->settings_page_hook ) { wp_enqueue_media(); }`.
**Warning signs:** JS errors on other admin pages; tinymce conflicts; slower page loads across all admin.

### Pitfall 4: jQuery UI Sortable not found
**What goes wrong:** `$.fn.sortable is not a function` in browser console.
**Why it happens:** `jquery-ui-sortable` must be listed as a dependency in `wp_enqueue_script()` — it is NOT loaded by default even though it ships with WordPress.
**How to avoid:** Pass `'jquery-ui-sortable'` in the `$deps` array of `wp_enqueue_script()`.
**Warning signs:** JS console error; rows draggable but snap back immediately.

### Pitfall 5: PHPCS filename sniff false positives with PSR-4
**What goes wrong:** PHPCS reports `WordPress.Files.FileName.NotHyphenatedLowercase` for all class files named with PascalCase.
**Why it happens:** WPCS default enforces WP's `class-my-class.php` filename convention; PSR-4 uses `MyClass.php`.
**How to avoid:** In `.phpcs.xml`, either exclude the sniff or set `<property name="strict_class_file_names" value="false"/>`.
**Warning signs:** PHPCS reports dozens of filename violations on a clean codebase.

### Pitfall 6: Docker WordPress image doesn't send mail natively
**What goes wrong:** `wp_mail()` returns `false` in the dev environment; no error shown.
**Why it happens:** The official `wordpress` Docker image does not include a local MTA (sendmail/postfix). PHP's `mail()` fails silently.
**How to avoid:** Use Mailpit as the SMTP relay. Configure WordPress to use Mailpit via `WORDPRESS_CONFIG_EXTRA` environment variable with `define('SMTP_HOST', 'mailpit')` — or install WP Mail SMTP plugin in the local environment. The recommended approach is the `WORDPRESS_CONFIG_EXTRA` env var.
**Warning signs:** `wp_mail()` returns `false`; no email appears in Mailpit UI after "Send test email".

### Pitfall 7: Color picker value not persisted
**What goes wrong:** Accent color reverts to default after save.
**Why it happens:** `wp-color-picker` uses a hidden text input alongside the visible color widget; the hidden input carries the form value. If the HTML `name` attribute is wrong, the value is not included in the POST.
**How to avoid:** The `<input type="text" name="wmr_pdf_branding[accent_color]">` must have its `name` attribute set, and the sanitize callback must read `$input['accent_color']` (not just `$_POST`).
**Warning signs:** `print_r( get_option('wmr_pdf_branding') )` shows empty string for `accent_color`.

---

## Code Examples

### .phpcs.xml — Minimal Correct Config

```xml
<?xml version="1.0"?>
<!-- Source: github.com/WordPress/WordPress-Coding-Standards -->
<ruleset name="WP-Membership-Registration">
    <description>PHPCS rules for WP Membership Registration plugin.</description>

    <!-- Scan src/ and the main plugin file only -->
    <file>src/</file>
    <file>wp-membership-registration.php</file>

    <!-- Exclude generated files -->
    <exclude-pattern>vendor/</exclude-pattern>
    <exclude-pattern>node_modules/</exclude-pattern>

    <!-- Use the full WordPress standard -->
    <rule ref="WordPress">
        <!-- Allow PSR-4 PascalCase filenames (class-*.php not required) -->
        <exclude name="WordPress.Files.FileName.NotHyphenatedLowercase"/>
        <exclude name="WordPress.Files.FileName.InvalidClassFileName"/>
    </rule>

    <!-- Set text domain for i18n sniff -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="wp-membership-registration"/>
            </property>
        </properties>
    </rule>

    <!-- PHP version minimum -->
    <config name="minimum_supported_wp_version" value="6.0"/>
    <config name="testVersion" value="8.0-"/>
</ruleset>
```

### docker-compose.yml

```yaml
# Source: hub.docker.com/_/wordpress + mailpit.axllent.org/docs/install/docker/
services:
  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
    volumes:
      - db_data:/var/lib/mysql

  wordpress:
    image: wordpress:latest
    restart: unless-stopped
    depends_on:
      - db
      - mailpit
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DEBUG: "1"
      # Route wp_mail() through Mailpit SMTP
      WORDPRESS_CONFIG_EXTRA: |
        define( 'SMTP_HOST', 'mailpit' );
        define( 'SMTP_PORT', 1025 );
    volumes:
      - .:/var/www/html/wp-content/plugins/wp-membership-registration
      - wp_data:/var/www/html

  mailpit:
    image: axllent/mailpit
    restart: unless-stopped
    ports:
      - "8025:8025"   # Web UI — Claude's discretion: 8025
      - "1025:1025"   # SMTP (exposed for debugging; internal traffic uses service name)

  adminer:
    image: adminer
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "8081:8080"   # Adminer UI — Claude's discretion: 8081

volumes:
  db_data:
  wp_data:
```

**Important note:** `WORDPRESS_CONFIG_EXTRA` injects PHP into `wp-config.php` but does NOT configure `wp_mail()` to use a custom SMTP host by itself — WordPress's `wp_mail()` uses PHP's `mail()` unless overridden by a hook. The `WORDPRESS_CONFIG_EXTRA` trick works for constants that a mail plugin reads (e.g., WP Mail SMTP). A zero-plugin alternative is a lightweight `mu-plugin` that hooks `phpmailer_init` to set SMTP host/port.

**Recommended approach for dev SMTP:** Add a `mu-plugins/mailpit-smtp.php` file (mounted via volume) that hooks `phpmailer_init`:

```php
<?php
// mu-plugins/mailpit-smtp.php — loaded automatically by WordPress
add_action( 'phpmailer_init', function( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mailpit';
    $phpmailer->Port       = 1025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = '';
} );
```

Mount it by adding to the wordpress service volumes:
```yaml
- ./dev/mu-plugins:/var/www/html/wp-content/mu-plugins
```

### composer.json skeleton

```json
{
  "name": "bitstrange/wp-membership-registration-plugin",
  "description": "WordPress membership registration plugin with PDF generation",
  "type": "wordpress-plugin",
  "license": "GPL-2.0-or-later",
  "require": {},
  "require-dev": {
    "squizlabs/php_codesniffer": "^3.8",
    "wp-coding-standards/wpcs": "^3.0",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "WpMembershipRegistration\\": "src/"
    }
  },
  "config": {
    "allow-plugins": {
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  }
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Global PHPCS install + manual `--config-set installed_paths` | Composer `dealerdirect/phpcodesniffer-composer-installer` auto-registers rulesets | WPCS 3.0 (2023) | Installation is one `composer install` command; no manual path setup |
| WPCS 2.x (PHP 5.6 compatible) | WPCS 3.x (PHP 7.2+) | WPCS 3.0.0 (Oct 2023) | New sniffs; stricter rules; old `WordPress-VIP` standard removed |
| MailHog for local mail capture | Mailpit (`axllent/mailpit`) | ~2022 | MailHog is unmaintained; Mailpit is the community replacement |
| `wp_localize_script()` for any JS data | `wp_add_inline_script()` for structured data; `wp_localize_script()` still valid for simple objects | WP 4.5+ | Either approach works; `wp_localize_script()` is still perfectly fine for nonces/config objects |
| PHP-serialized option values for arrays | JSON (`wp_json_encode`/`json_decode`) for structured option data | Best practice codified ~2018 | JSON is portable, human-readable, and doesn't corrupt on charset migration |

**Deprecated/outdated:**
- `MailHog`: Unmaintained since 2022 — use Mailpit instead.
- `WPCS 2.x`: No longer maintained for new PHP versions — use 3.x.
- Manual `phpcs --config-set installed_paths /path/to/wpcs`: Replaced by Composer installer plugin.

---

## Open Questions

1. **SMTP configuration strategy in Docker**
   - What we know: Official WP Docker image does not configure SMTP; `WORDPRESS_CONFIG_EXTRA` defines constants but doesn't directly hook `phpmailer_init`.
   - What's unclear: Whether to use a `mu-plugin` file (cleanest, zero WP-admin config) or require contributors to install WP Mail SMTP plugin after first boot.
   - Recommendation: Ship a `dev/mu-plugins/mailpit-smtp.php` file mounted as a volume. This is zero-click — no contributor action needed, no plugin installed in WP admin. Document in CONTRIBUTING.md.

2. **WordPress version pin in docker-compose.yml**
   - What we know: Claude's discretion. `wordpress:latest` always gives the newest version.
   - What's unclear: Whether to pin to `wordpress:6.7` (most recent stable as of research date) for reproducibility.
   - Recommendation: Use `wordpress:latest` in Phase 1 to avoid manual version bumps. Pin to a specific version before v1 release.

3. **Recipient email storage format (Claude's discretion)**
   - What we know: Options are comma-separated string or one-per-line textarea.
   - Recommendation: One-per-line textarea. Stored as a single option string; split on `\n` and sanitize each line with `sanitize_email()` on read. This is more readable in the UI, avoids comma-in-address ambiguity, and is trivially exploded in PHP.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPCS (code quality, not unit tests) |
| Config file | `.phpcs.xml` (Wave 0 creates this) |
| Quick run command | `vendor/bin/phpcs --standard=.phpcs.xml src/ wp-membership-registration.php` |
| Full suite command | `vendor/bin/phpcs --standard=.phpcs.xml src/ wp-membership-registration.php` |

**Note:** DEV-02 (PHPUnit) is out of scope for Phase 1 — it is mapped to Phase 4. Phase 1 validation is exclusively PHPCS linting. No automated browser or integration tests are required for this phase.

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CONF-01 | Field schema option saved and retrieved as valid JSON | Manual (UI smoke test) | — | N/A |
| CONF-02 | Add/remove/reorder rows persist across page reload | Manual (UI smoke test) | — | N/A |
| CONF-03 | PDF branding option saved with logo URL, color hex, club name | Manual (UI smoke test) | — | N/A |
| CONF-04 | Recipient emails textarea saved and read back correctly | Manual (UI smoke test) | — | N/A |
| CONF-05 | "Send test email" button triggers AJAX; email appears in Mailpit UI | Manual (Docker + browser) | — | N/A |
| DEV-01 | `docker compose up` launches WP, Mailpit, Adminer without errors | Manual (smoke test) | `docker compose up --wait` | ❌ Wave 0 |
| DEV-03 | PHPCS reports zero violations on plugin source | Automated lint | `vendor/bin/phpcs --standard=.phpcs.xml src/ wp-membership-registration.php` | ❌ Wave 0 |

### Sampling Rate

- **Per task commit:** `vendor/bin/phpcs --standard=.phpcs.xml src/ wp-membership-registration.php`
- **Per wave merge:** same command (only automated check available in Phase 1)
- **Phase gate:** PHPCS zero violations + manual Docker smoke test before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `composer.json` — with PHPCS/WPCS dev dependencies
- [ ] `.phpcs.xml` — PHPCS configuration with WordPress standard
- [ ] `docker-compose.yml` — full service definition
- [ ] `dev/mu-plugins/mailpit-smtp.php` — SMTP relay for dev environment
- [ ] `vendor/` — populated by `composer install` (gitignored)

---

## Sources

### Primary (HIGH confidence)

- [developer.wordpress.org/plugins/settings/settings-api/](https://developer.wordpress.org/plugins/settings/settings-api/) — Settings API functions, nonces, capability model
- [developer.wordpress.org/plugins/javascript/ajax/](https://developer.wordpress.org/plugins/javascript/ajax/) — wp_ajax_ pattern, nonce localization, wp_send_json_success
- [codex.wordpress.org/Javascript_Reference/wp.media](https://codex.wordpress.org/Javascript_Reference/wp.media) — wp.media frame API
- [api.jqueryui.com/sortable/](https://api.jqueryui.com/sortable/) — jQuery UI Sortable options, update callback, serialize
- [mailpit.axllent.org/docs/install/docker/](https://mailpit.axllent.org/docs/install/docker/) — Mailpit Docker image, ports (1025 SMTP, 8025 UI)
- [github.com/WordPress/WordPress-Coding-Standards](https://github.com/WordPress/WordPress-Coding-Standards) — WPCS 3.x, Composer install, available standards
- [packagist.org/packages/wp-coding-standards/wpcs](https://packagist.org/packages/wp-coding-standards/wpcs) — WPCS 3.x package, install command

### Secondary (MEDIUM confidence)

- [rudrastyh.com/wordpress/create-tabs-in-settings-pages.html](https://rudrastyh.com/wordpress/create-tabs-in-settings-pages.html) — Tabbed settings page patterns
- [dlxplugins.com/tutorials/creating-a-psr-4-autoloading-wordpress-plugin/](https://dlxplugins.com/tutorials/creating-a-psr-4-autoloading-wordpress-plugin/) — PSR-4 plugin structure with Composer
- [wpdiaries.com/mail-functionality-for-official-docker-wordpress-image/](https://www.wpdiaries.com/mail-functionality-for-official-docker-wordpress-image/) — SMTP workaround for official WP Docker image

### Tertiary (LOW confidence — needs validation)

- Docker `WORDPRESS_CONFIG_EXTRA` SMTP injection approach — partial verification; mu-plugin approach confirmed as more reliable

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all dependencies are WordPress-bundled or official Composer packages with verified docs
- Architecture: HIGH — Settings API, PSR-4, and WPCS patterns verified against official WordPress developer docs
- Docker setup: MEDIUM — docker-compose structure verified; SMTP relay via mu-plugin is a practical pattern but not officially documented by Docker WordPress team
- Pitfalls: HIGH — each pitfall traced to a specific WordPress API behavior or WPCS sniff behavior

**Research date:** 2026-03-20
**Valid until:** 2026-06-20 (stable domain — WP Settings API, WPCS 3.x, Mailpit all stable)
