# Phase 5: PHP i18n Foundation - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Register text domain via `load_plugin_textdomain()`, audit and complete PHP string wrapping across admin settings and frontend form, standardize all source strings to English msgids, pass JS strings through `wp_localize_script` i18n objects, and add a fallback language admin setting that overrides WP's locale for this plugin.

PDF structural labels are explicitly out of scope. The `.pot` file generation, `.po/.mo` compilation, and `wp_set_script_translations()` delivery are Phase 6.

</domain>

<decisions>
## Implementation Decisions

### Source language policy
- ALL msgids (source strings) must be English — no German strings in PHP or JS source code
- German text is provided exclusively via `.po/.mo` translation files (Phase 6 generates these)
- The consent checkbox default in `FormRenderer.php:92` must be changed from German (`'Ich stimme der Verarbeitung...'`) to English (`'I consent to the processing of my personal data.'`)
- No migration needed for existing installs — de_DE locale sites will get German text from the translation file

### Consent text handling
- If the admin has set a custom `consent_text` in plugin settings, use it verbatim (no translation applied)
- The i18n-wrapped English default only applies when the setting is empty/unset
- This preserves existing behavior — custom text wins over translation

### Fallback language setting
- Plugin-level locale override: a dropdown in admin settings with values `auto` / `de` / `en`
- Default value: `auto` — auto-detects from WP site language (de_DE → German, otherwise English)
- When set to `de` or `en`, the plugin forces its own locale regardless of WP's site language setting
- Affects BOTH admin settings page AND frontend form (all plugin strings)
- Placed in the General tab of plugin settings (alongside other plugin-wide options)

### String audit scope
- Quick grep audit of `templates/admin-settings-page.php` (~308 lines) for any bare strings missed by existing wrapping
- Include email strings in `Mailer.php` — email subjects and body text are user-facing and should be verified/wrapped
- PDF generator (`PdfGenerator.php`) is excluded — PDF structural labels are out of scope per requirements
- Audit covers: admin settings template, FormRenderer.php, AjaxHandlers.php, Mailer.php, SettingsPage.php, Plugin.php

### JS string handling (Phase 5 scope)
- Convert all 6 hardcoded German strings in `assets/js/form.js` to English, passed via `wp_localize_script` i18n sub-object
- Convert all 8 hardcoded English strings in `assets/js/admin-settings.js` to translatable strings via `wp_localize_script` i18n sub-object
- Pattern: extend existing localized data objects — `wmrForm.i18n = { consentError: __('...'), ... }` and `wmrSettings.i18n = { dragToReorder: __('...'), ... }`
- Phase 6 will later upgrade this to `wp_set_script_translations()` with JSON locale files

### Claude's Discretion
- Exact implementation of the locale override mechanism (e.g., `switch_to_locale()` vs custom `load_textdomain()` call)
- Option key naming for the fallback language setting
- Ordering of the language dropdown relative to other General tab fields
- Grep patterns used for the quick audit

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Plugin structure
- `wp-membership-registration.php` — Main plugin file; text domain and domain path declared in header (lines 10-11)
- `src/Plugin.php` — Plugin bootstrap; `load_plugin_textdomain()` should be added here on `init` hook

### Admin settings
- `src/Admin/SettingsPage.php` — Admin menu registration and script enqueuing
- `src/Admin/SettingsRegistrar.php` — Settings registration (4 option groups); fallback language setting goes here
- `templates/admin-settings-page.php` — Admin settings template (~308 lines); primary audit target

### Frontend form
- `src/Frontend/FormRenderer.php` — Form rendering; consent default text at line 92 needs English msgid
- `src/Admin/AjaxHandlers.php` — AJAX handlers with wrapped response strings

### Email
- `src/Email/Mailer.php` — Email sending; verify all subject/body strings are wrapped

### JavaScript
- `assets/js/form.js` — Frontend form JS; 6 German hardcoded strings to convert
- `assets/js/admin-settings.js` — Admin settings JS; 8 English hardcoded strings to convert

### Requirements
- `.planning/REQUIREMENTS.md` — I18N-01, I18N-02, I18N-03, I18N-07 mapped to this phase

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `wp_localize_script` already used for `wmrForm.successMessage` — extend this pattern for i18n sub-objects
- All admin template strings already use `esc_html_e()` / `esc_attr_e()` — consistent pattern to follow
- `AjaxHandlers.php` already uses `__()` for all response strings — good reference pattern
- Brain Monkey test stubs for `__()` etc. already in `tests/bootstrap.php` — no test setup changes needed

### Established Patterns
- Text domain: `wp-membership-registration` used consistently in all existing `__()` calls
- Settings registration: `SettingsRegistrar.php` uses `register_setting()` + `add_settings_section()` + `add_settings_field()`
- Settings storage: array options (`wmr_form_settings`, `wmr_pdf_branding`, etc.) with sanitize callbacks

### Integration Points
- `Plugin::register()` — where `load_plugin_textdomain()` should be hooked (on `init`)
- `SettingsRegistrar::register_settings()` — where fallback language setting should be registered
- `SettingsPage::enqueue_admin_scripts()` — where admin JS i18n object gets localized
- `FormRenderer` or shortcode enqueue — where frontend JS i18n object gets localized

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard WordPress i18n approaches.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 05-php-i18n-foundation*
*Context gathered: 2026-03-24*
