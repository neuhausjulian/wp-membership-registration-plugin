# Architecture Research

**Domain:** WordPress plugin — form, PDF generation, email/webhook notification
**Researched:** 2026-03-20
**Confidence:** HIGH (WordPress official docs + multiple current sources)

## Standard Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress Request                         │
├──────────────────────────┬──────────────────────────────────────┤
│       Admin Layer        │          Frontend Layer              │
│  ┌──────────────────┐   │   ┌────────────────────────────┐     │
│  │  Settings Page   │   │   │  Shortcode / Form Renderer │     │
│  │  (WP Settings    │   │   │  [FormRenderer]            │     │
│  │   API / menus)   │   │   └────────────┬───────────────┘     │
│  └────────┬─────────┘   │                │ POST (AJAX/sync)    │
│           │ options_     │                ▼                     │
│           │ table        │   ┌────────────────────────────┐     │
│           ▼             │   │    Form Handler            │     │
│  ┌──────────────────┐   │   │    [FormHandler]           │     │
│  │  SettingsManager │   │   │    · nonce verify          │     │
│  │  get/save config │   │   │    · sanitize/validate     │     │
│  └──────────────────┘   │   │    · fire action hook      │     │
│                          │   └────────────┬───────────────┘     │
├──────────────────────────┘                │                     │
│                         Core Pipeline     │                     │
│   ┌────────────────────────────────────── ▼ ──────────────────┐ │
│   │              SubmissionPipeline                           │ │
│   │  (fired via do_action / custom hook chain)                │ │
│   │                                                           │ │
│   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │ │
│   │  │ PDFGenerator│  │   Mailer    │  │ WebhookDispatch │  │ │
│   │  │ (HTML→PDF)  │  │ (wp_mail)   │  │ (wp_remote_post)│  │ │
│   │  └─────────────┘  └─────────────┘  └─────────────────┘  │ │
│   └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Communicates With |
|-----------|----------------|-------------------|
| `Plugin` (bootstrap) | Loads all modules, registers all hooks | All components |
| `SettingsManager` | CRUD for plugin options (fields config, PDF template, email addresses) via WP Options API | Admin settings page, FormRenderer, PDFGenerator |
| `AdminPage` | Renders the settings UI in WP admin using Settings API | SettingsManager |
| `FormRenderer` | Outputs shortcode HTML with nonce-protected form, enqueues frontend assets | SettingsManager (reads field config) |
| `FormHandler` | Receives POST/AJAX submission, verifies nonce, sanitizes/validates, fires pipeline hook | SubmissionPipeline via `do_action` |
| `PDFGenerator` | Takes sanitized submission data + template config, produces PDF bytes (via dompdf/mPDF) | FormHandler (via hook), Mailer |
| `Mailer` | Attaches PDF, sends email to member + admin via `wp_mail` | PDFGenerator, SettingsManager |
| `WebhookDispatcher` | Sends HTTP POST JSON payload via `wp_remote_post` | FormHandler (via hook), SettingsManager |
| `Plugin` uninstall | Cleans up options on plugin deletion | WP Options API |

## Recommended Project Structure

```
wp-membership-registration/
├── wp-membership-registration.php   # Main entry point: bootstrap, plugin headers
├── uninstall.php                    # Cleanup on uninstall (delete options)
├── composer.json                    # PSR-4 autoload map, dev deps (phpunit, brain/monkey)
├── composer.lock
├── vendor/                          # Composer deps (dompdf, etc.) — gitignored in dev
├── src/
│   ├── Plugin.php                   # Bootstrap: instantiates all components, registers hooks
│   ├── Admin/
│   │   ├── AdminPage.php            # Settings menu + page render (Settings API)
│   │   └── SettingsManager.php      # get/update options (field schema, PDF template, email cfg)
│   ├── Frontend/
│   │   ├── FormRenderer.php         # [shortcode] callback, renders form HTML
│   │   └── FormHandler.php          # Processes submission: nonce, sanitize, validate, emit hook
│   ├── PDF/
│   │   └── PDFGenerator.php         # Renders HTML template + dompdf → PDF bytes/temp file
│   ├── Notification/
│   │   ├── Mailer.php               # Composes and sends wp_mail with PDF attachment
│   │   └── WebhookDispatcher.php    # Sends JSON payload via wp_remote_post
│   └── Support/
│       └── TemplateRenderer.php     # Renders PHP/HTML templates with data interpolation
├── templates/
│   └── pdf/
│       └── membership-form.php      # Default HTML/CSS PDF template (configurable)
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       └── frontend.js              # Progressive form UX (validation, loading state)
├── languages/                       # .pot / .po / .mo for i18n
├── tests/
│   ├── unit/
│   │   ├── bootstrap.php            # Composer autoload only — no WordPress
│   │   ├── Admin/
│   │   ├── Frontend/
│   │   ├── PDF/
│   │   └── Notification/
│   ├── integration/
│   │   ├── bootstrap.php            # Bootstraps full WordPress via wp-env
│   │   └── FormSubmissionTest.php   # End-to-end: submit → PDF → email
│   └── phpunit.unit.xml
├── phpunit.xml.dist                 # Integration test config (default `phpunit` run)
├── docker-compose.yml               # WordPress + MySQL for local dev (alternative to wp-env)
├── .wp-env.json                     # wp-env config (optional, for npm-based env)
└── bin/
    └── install-wp-tests.sh          # WP test suite installer (standard scaffold output)
```

### Structure Rationale

- **`src/` with PSR-4 autoloading:** No manual `require` chains; Composer resolves classes by namespace (`WpMembershipRegistration\`). Each directory maps to a bounded concern.
- **`Admin/` vs `Frontend/`:** Mirrors WordPress's own `is_admin()` boundary. Admin code never loads on frontend requests; frontend code never loads in admin.
- **`PDF/` and `Notification/` as separate namespaces:** Keeps the pipeline stages independently testable. PDF generation is pure data-in / bytes-out with no WordPress dependency; Mailer has a thin WP dependency (`wp_mail`).
- **`templates/`:** Separates HTML/CSS from PHP logic. The template is the user-configurable artifact — it stays outside `src/` because it is data, not code.
- **`tests/unit/` vs `tests/integration/`:** Two separate PHPUnit bootstrap files enforce the unit/integration boundary. Unit tests load only Composer autoloader; integration tests load the full WordPress test suite.

## Architectural Patterns

### Pattern 1: Hook-Based Pipeline (Core Pattern)

**What:** Form submission triggers a custom action (`do_action('wmr_form_submitted', $data)`) that each downstream component (PDF, Mailer, Webhook) listens to independently. Each component adds itself via `add_action` in the bootstrap class.

**When to use:** Anytime a single event must trigger multiple independent side effects. This is the idiomatic WordPress way and makes each handler independently testable and opt-outable.

**Trade-offs:** Components are decoupled (good), but execution order depends on hook priority (requires documentation). Error in one handler does not automatically halt others (may need explicit error propagation).

**Example:**
```php
// Plugin.php bootstrap
add_action('wmr_form_submitted', [$this->pdfGenerator,     'handleSubmission'], 10, 1);
add_action('wmr_form_submitted', [$this->mailer,           'handleSubmission'], 20, 1);
add_action('wmr_form_submitted', [$this->webhookDispatcher,'handleSubmission'], 30, 1);

// FormHandler.php — after validation passes
do_action('wmr_form_submitted', $sanitized_data);
```

### Pattern 2: Bootstrap Singleton with Dependency Injection

**What:** A single `Plugin` class is instantiated once (via plugin main file). It constructs all components with their dependencies injected, then wires hooks. No component instantiates another; the bootstrap class owns composition.

**When to use:** All plugins beyond trivial single-file scripts. Prevents scattered globals and makes it possible to swap implementations in tests.

**Trade-offs:** Slightly more boilerplate upfront; pays off immediately for testability.

**Example:**
```php
// wp-membership-registration.php
function wmr_init(): void {
    static $instance = null;
    if ($instance === null) {
        $settings  = new SettingsManager();
        $pdf       = new PDFGenerator($settings);
        $mailer    = new Mailer($settings);
        $webhook   = new WebhookDispatcher($settings);
        $handler   = new FormHandler($settings);
        $renderer  = new FormRenderer($settings);
        $instance  = new Plugin($settings, $pdf, $mailer, $webhook, $handler, $renderer);
        $instance->register();
    }
}
add_action('plugins_loaded', 'wmr_init');
```

### Pattern 3: Constructor-Free Hook Registration

**What:** Hooks are NOT registered inside class constructors. The bootstrap class calls an explicit `register()` method on each component after construction is complete.

**When to use:** Always in testable plugins. Constructors with `add_action()` inside them make unit testing nearly impossible because instantiating the class immediately side-effects WordPress.

**Trade-offs:** Requires discipline; WP does not enforce this. Without it, test isolation is very painful.

**Example:**
```php
// Bad — hooks in constructor
class FormRenderer {
    public function __construct() {
        add_shortcode('wmr_form', [$this, 'render']); // side-effect!
    }
}

// Good — hooks in explicit register() method
class FormRenderer {
    public function __construct(SettingsManager $settings) {
        $this->settings = $settings; // pure construction
    }
    public function register(): void {
        add_shortcode('wmr_form', [$this, 'render']);
    }
}
```

### Pattern 4: Settings API for Admin Configuration

**What:** Use WordPress's built-in Settings API (`register_setting`, `add_settings_section`, `add_settings_field`) rather than rolling a custom `$_POST` handler for admin settings. WordPress handles nonce, options saving, and capability checks.

**When to use:** All admin configuration in this plugin. Covers: field schema editor, PDF template config, email address configuration.

**Trade-offs:** Settings API is verbose for complex UIs (repeater fields for dynamic form field lists require custom handling beyond what the API natively supports), but it handles security correctly out of the box.

### Pattern 5: AJAX-First Form Submission

**What:** The frontend form submits via `fetch()` to `wp-admin/admin-ajax.php` (or a REST endpoint), receives JSON, and updates the UI without a page reload. The server-side handler is `wp_ajax_nopriv_wmr_submit` (public-facing).

**When to use:** For the registration form. Enables progressive enhancement, better error display, and immediate feedback ("Your PDF is on its way").

**Trade-offs:** Slightly more JS complexity than a plain form POST, but significantly better UX for a one-shot registration flow.

## Data Flow

### Form Submission Flow

```
[Visitor] fills form in browser
    │
    ▼ fetch() POST to admin-ajax.php?action=wmr_submit
[FormHandler::handleAjax()]
    │
    ├── check_ajax_referer('wmr_submit_form')        ← nonce check
    ├── current_user_can() [n/a, public action]
    ├── sanitize_text_field() / sanitize_email()     ← sanitize all fields
    ├── validate_required_fields()                   ← validate
    │
    ├── do_action('wmr_form_submitted', $data)       ← pipeline trigger
    │       │
    │       ├── [priority 10] PDFGenerator::handleSubmission($data)
    │       │       ├── Render HTML template with $data
    │       │       ├── dompdf->loadHtml($html)
    │       │       ├── dompdf->render()
    │       │       └── Returns temp file path (stored in $data['pdf_path'])
    │       │
    │       ├── [priority 20] Mailer::handleSubmission($data)
    │       │       ├── wp_mail(member_email, subject, body, [], [$pdf_path])
    │       │       └── wp_mail(admin_email,  subject, body, [], [$pdf_path])
    │       │
    │       └── [priority 30] WebhookDispatcher::handleSubmission($data) [optional]
    │               └── wp_remote_post(webhook_url, ['body' => json_encode($data)])
    │
    └── wp_send_json_success(['message' => 'Registration complete'])

[Browser] receives JSON, shows success message
```

### Settings Read Flow

```
[Admin] saves settings in WP admin
    │
    ▼ POST to options.php (Settings API)
[WordPress core] validates capability + nonce, calls sanitize callback
    │
    ▼ update_option('wmr_settings', $sanitized)
[wp_options table]
    │
[Plugin request] SettingsManager::get('field_schema')
    └── get_option('wmr_settings')['field_schema']   ← single options read per request
```

### PDF Temp File Lifecycle

```
Form submitted
    │
PDFGenerator writes → sys_get_temp_dir() / wmr_[hash].pdf
    │
Mailer reads + attaches → wp_mail()
    │
WebhookDispatcher fires (no file needed)
    │
Cleanup: register_shutdown_function() or after-send hook → unlink($pdf_path)
```

## Component Build Order

Build dependencies flow bottom-up. Each layer depends only on layers below it.

```
Layer 0: SettingsManager
    (no plugin dependencies; reads/writes wp_options)

Layer 1: PDFGenerator, TemplateRenderer
    (depends on: SettingsManager for template config)

Layer 2: Mailer, WebhookDispatcher
    (depends on: SettingsManager for email/webhook config; PDFGenerator for attachment)

Layer 3: FormHandler
    (depends on: SettingsManager for field schema; fires hook consumed by layers 1-2)

Layer 4: FormRenderer
    (depends on: SettingsManager for field schema)

Layer 5: AdminPage
    (depends on: SettingsManager)

Layer 6: Plugin bootstrap
    (depends on: all above; wires hooks)
```

**Suggested build sequence for phases:**
1. `SettingsManager` + `AdminPage` (get/set settings working end-to-end in admin)
2. `PDFGenerator` + `TemplateRenderer` (pure PHP unit; testable without WordPress)
3. `Mailer` (thin WP wrapper; testable with Brain Monkey mocking `wp_mail`)
4. `FormRenderer` + `FormHandler` (shortcode + AJAX endpoint)
5. `WebhookDispatcher` (optional feature; last because it is purely additive)
6. `Plugin` bootstrap (wires everything together — written incrementally as layers build)

## Testing Architecture

### Two-Tier Strategy

| Tier | Tool | Bootstrap | Speed | Tests |
|------|------|-----------|-------|-------|
| Unit | PHPUnit + Brain Monkey | Composer autoloader only | ~1-2s total | Isolated class logic (sanitize, validate, template render) |
| Integration | PHPUnit + WP test suite | Full WordPress (Docker/wp-env) | ~10-30s total | Hook wiring, options read/write, wp_mail call |

### Unit Test Approach

PHPUnit with **Brain Monkey** (preferred over WP_Mock). Brain Monkey stubs WordPress global functions (`add_action`, `wp_mail`, `sanitize_text_field`, etc.) without loading WordPress. Each test class mocks only what the SUT calls.

Ideal unit test targets:
- `SettingsManager`: sanitize callbacks
- `PDFGenerator`: template rendering logic (no WP dependency in PDF generation itself)
- `FormHandler`: validation rules, sanitization
- `WebhookDispatcher`: payload structure

### Integration Test Approach

Full WordPress bootstrap (via `wp-env` Docker or `docker-compose` in the dev environment). Use `WP_UnitTestCase` for tests that verify:
- Hook registration (`has_action`, `has_shortcode`)
- Options round-trip (`update_option` → `get_option`)
- `wp_mail` actually fires with expected args (use `phpmailer_init` filter to capture)
- Shortcode renders correct HTML

### Directory Mapping

```
tests/
├── unit/
│   ├── bootstrap.php              # require __DIR__.'/../vendor/autoload.php'
│   ├── Admin/SettingsManagerTest.php
│   ├── Frontend/FormHandlerTest.php
│   ├── PDF/PDFGeneratorTest.php
│   └── Notification/MailerTest.php
├── integration/
│   ├── bootstrap.php              # WP test suite bootstrap
│   └── FormSubmissionTest.php
├── phpunit.unit.xml               # testSuites: unit/, no WP bootstrap
└── phpunit.xml.dist               # testSuites: integration/, WP bootstrap
```

Running tests:
```bash
# Unit tests (fast, no Docker needed)
vendor/bin/phpunit -c phpunit.unit.xml

# Integration tests (Docker required)
vendor/bin/phpunit -c phpunit.xml.dist
```

## Anti-Patterns

### Anti-Pattern 1: Hooks in Constructors

**What people do:** Call `add_action('init', [$this, 'run'])` inside `__construct()`.
**Why it's wrong:** Instantiating the class immediately registers WordPress hooks, making unit testing impossible without loading WordPress. You cannot construct the object for testing without triggering side effects.
**Do this instead:** Use an explicit `register()` method called by the bootstrap class after construction.

### Anti-Pattern 2: Storing PDFs in the Database or Media Library

**What people do:** Save generated PDF as a WordPress attachment (`wp_insert_attachment`).
**Why it's wrong:** v1 has no member database. Storing PDFs creates a GDPR surface, fills the media library, and requires a cleanup strategy. The PDF is a transient artifact: generate → email → discard.
**Do this instead:** Write to `sys_get_temp_dir()`, attach to email, delete via `register_shutdown_function` or a post-send action.

### Anti-Pattern 3: One Giant Settings Array

**What people do:** Store all settings as `update_option('wmr_settings', $huge_array)` and load the whole thing on every page.
**Why it's wrong:** The field schema for the form renderer and the PDF template config are only needed in specific contexts. Loading everything on every admin and frontend page is wasteful.
**Do this instead:** One options key (`wmr_settings`) is still fine for this scale, but the `SettingsManager` should provide typed getter methods (`getFieldSchema()`, `getPdfTemplate()`, `getAdminEmail()`) rather than consumers reaching into the raw array. Lazy-load within the request.

### Anti-Pattern 4: Rolling a Custom Nonce for Admin Settings

**What people do:** Skip the Settings API and write a custom `admin_post_*` handler with manual `$_POST` parsing.
**Why it's wrong:** Settings API handles nonce, options.php routing, capability check (`manage_options`), and sanitize callback registration — all for free. Custom handlers must re-implement all of this and are prone to subtle security gaps.
**Do this instead:** Use Settings API for all admin configuration. Only use `admin_post` or `wp_ajax` for frontend/AJAX endpoints (like form submission).

### Anti-Pattern 5: Bundling Composer Dependencies Without Prefixing

**What people do:** Ship `vendor/` with standard Composer namespaces (e.g., `Dompdf\Dompdf`).
**Why it's wrong:** Another plugin could ship a different version of dompdf under the same namespace, causing class collision and fatal errors.
**Do this instead:** Use PHP-Scoper (or Strauss) to prefix all vendor namespaces — e.g., `WmrVendor\Dompdf\Dompdf`. This is the standard approach for distributing WP plugins with Composer dependencies.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Webhook receiver (user-configured URL) | `wp_remote_post()` with JSON body and configurable headers | Fire-and-forget; log response code but don't block form success on failure |
| SMTP / wp_mail | WordPress `wp_mail()` function | WordPress handles SMTP transport; SMTP config is server-level, not plugin-level |
| dompdf (bundled) | Instantiate `Dompdf\Dompdf` in `PDFGenerator` | Must be namespace-prefixed before distribution |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| FormHandler → Pipeline | `do_action('wmr_form_submitted', $data)` | `$data` is an associative array of sanitized field values |
| PDFGenerator → Mailer | `$data['pdf_tmp_path']` set on `$data` array passed through hook | PDFGenerator writes temp file and annotates `$data` before Mailer priority fires |
| SettingsManager → All | Direct method calls via injected dependency | All components receive `SettingsManager` at construction |
| Plugin → WP | WP hooks (`add_action`, `add_shortcode`, Settings API) | Plugin never calls WP functions in constructors — always in `register()` |

## Scaling Considerations

This is a self-hosted WordPress plugin for small clubs. Scaling is not a primary concern, but known limits:

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 1-100 submissions/day | Current synchronous pipeline is fine. No queue needed. |
| 100-1000 submissions/day | Consider `wp_schedule_single_event()` to push email + webhook off the HTTP response cycle. |
| 1000+ submissions/day | At this scale the club is not small anymore. Async queue (Action Scheduler, bundled by WooCommerce team) would be the right tool. Out of v1 scope. |

**First bottleneck:** PDF generation (dompdf) is CPU-heavy. At low volumes (~10-50 concurrent) on shared hosting it will time out. Mitigation: set `max_execution_time` higher in `PDFGenerator`, or defer via `wp_schedule_single_event`.

## Sources

- [WordPress Plugin Best Practices Handbook](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) — official directory structure and OOP guidance
- [WordPress Settings API Handbook](https://developer.wordpress.org/plugins/settings/settings-api/) — admin settings architecture
- [WordPress Nonces Documentation](https://developer.wordpress.org/apis/security/nonces/) — nonce patterns for form security
- [Unit Testing WordPress Plugins in 2025 (Nate Weller)](https://blog.nateweller.com/2025/05/09/unit-testing-wordpress-plugins-in-2025-with-wordpress-env-and-phpunit/) — wp-env + PHPUnit testing architecture
- [Setting up Unit and Integration Testing (juanma.codes, 2025)](https://juanma.codes/2025/08/12/setting-up-unit-and-integration-testing-for-wordpress-plugins/) — two-tier testing strategy
- [Building Advanced WordPress Plugins: OOP, Namespaces, Autoloading (BuddyX, 2025)](https://buddyxtheme.com/building-advanced-wordpress-plugins-oop-namespaces-autoloading-and-modern-architecture/) — PSR-4 structure
- [The Pitfalls of Adding WordPress Hooks to a Class Constructor (Gary Jones)](https://garyjones.io/constructor-hooks) — anti-pattern: hooks in constructors
- [PHP PDF Generation Libraries 2025 (Piotr Horzycki)](https://peterdev.pl/picking-a-php-tool-to-generate-pdfs/) — dompdf vs TCPDF comparison
- [Yoast/wp-test-utils](https://github.com/Yoast/wp-test-utils) — PHPUnit cross-version compatibility for WP plugins

---
*Architecture research for: WordPress membership registration plugin*
*Researched: 2026-03-20*
