# Phase 6: Translation Files and JS i18n - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Generate a `.pot` source file from all wrapped PHP strings, compile German (`de_DE`) `.po` and `.mo` translation files that ship with the plugin, and deliver JavaScript client-side strings as translatable JSON via `wp_set_script_translations()`. After this phase, installing the plugin on a `de_DE` site renders all admin and frontend strings in German out-of-the-box.

PDF structural labels remain out of scope (decided in Phase 5).

</domain>

<decisions>
## Implementation Decisions

### POT/PO/MO tooling
- Use **WP-CLI `i18n make-pot`** inside the existing Docker Compose stack to generate the `.pot` file — no local PHP needed
- Use **`msgfmt`** (inside Docker) to compile `.po` → `.mo`
- **One-time generation** during this phase — no Makefile/script automation needed for now
- Commit **all** generated files (`.pot`, `.po`, `.mo`) to the repo — plugin works out-of-the-box on install with no build step

### Languages directory
- Create `languages/` at plugin root — standard WordPress convention, matches `load_plugin_textdomain()` domain path already in `Plugin.php`

### German translations
- **Claude writes all German translations** during plan execution; user reviews in the `.po` file before committing
- **Informal "du" tone everywhere** — both admin settings strings and member-facing form strings use consistent informal register (e.g., "Bitte füll das Formular aus", not "Bitte füllen Sie...")
- **Reuse original German phrasings** where they existed before Phase 5 converted them to English (consent text, validation messages, form labels) — maintains continuity with what users have already seen
- ~37 PHP strings + ~14 JS strings to translate

### JS translation delivery
- **Fully replace** `wp_localize_script` i18n sub-objects with `wp_set_script_translations()` + JSON locale files
- Remove `wmrForm.i18n` and `wmrSettings.i18n` sub-objects from `wp_localize_script` calls (keep non-i18n data like `ajaxUrl`, `submitNonce`)
- JS files (`form.js`, `admin-settings.js`) switch from `wmrForm.i18n.key` to `wp.i18n.__('string', 'wp-membership-registration')`
- Add `wp-i18n` as a script dependency for both JS files
- Use **WP-CLI `i18n make-json`** inside Docker to generate script-handle-keyed JSON locale files from the `.po` file

### Claude's Discretion
- Exact WP-CLI commands and Docker exec invocations
- `.po` file header metadata (translator, charset, etc.)
- JSON locale file naming (WordPress generates these based on script handle + md5)
- Order of operations for generation steps
- How to verify translations load correctly in the Docker dev environment

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 5 context (predecessor)
- `.planning/phases/05-php-i18n-foundation/05-CONTEXT.md` — Source language policy, consent text handling, fallback language setting, JS string handling decisions that Phase 6 builds on

### Plugin i18n infrastructure
- `src/Plugin.php` — `load_plugin_textdomain()` call on `init` (lines 33-37), `wp_localize_script` for `wmrForm` (line 102+) — must be modified for wp_set_script_translations
- `src/Admin/SettingsPage.php` — `wp_localize_script` for `wmrSettings` (line 84+) — must be modified for wp_set_script_translations
- `wp-membership-registration.php` — Plugin header with Text Domain and Domain Path declarations

### JavaScript files to modify
- `assets/js/form.js` — Frontend form JS; replace `wmrForm.i18n.key` references with `wp.i18n.__()`
- `assets/js/admin-settings.js` — Admin settings JS; replace `wmrSettings.i18n.key` references with `wp.i18n.__()`

### Requirements
- `.planning/REQUIREMENTS.md` — I18N-04 (.pot), I18N-05 (de_DE .po/.mo), I18N-06 (JS translations via wp_set_script_translations)

### Test infrastructure
- `tests/Unit/PluginTest.php` — Existing test stubs for `wp_localize_script` and `load_plugin_textdomain`; will need updates for `wp_set_script_translations`

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `wp_localize_script` calls in `Plugin.php:102` and `SettingsPage.php:84` already structure data objects — the non-i18n keys (`ajaxUrl`, `submitNonce`, `successMessage`, nonces) stay, only the `i18n` sub-objects are removed
- Docker Compose stack already running WordPress — WP-CLI can be added/exec'd in the same container
- Brain Monkey test stubs in `tests/bootstrap.php` already stub `__()` and related functions

### Established Patterns
- Text domain `wp-membership-registration` used consistently in all 37 `__()` / `esc_html_e()` calls across 7 PHP source files
- `wmrForm.i18n` pattern (6 keys: consentRequired, fieldRequired, invalidEmail, submitting, networkError, unexpectedError) and `wmrSettings.i18n` pattern (8 keys) established in Phase 5

### Integration Points
- `Plugin::register()` init callback — `load_plugin_textdomain()` already here; `wp_set_script_translations()` calls go near the `wp_localize_script` calls
- `SettingsPage::enqueue_admin_scripts()` — admin-side `wp_set_script_translations()` call goes here
- `languages/` directory — new directory to create, referenced by domain path in plugin header

</code_context>

<specifics>
## Specific Ideas

- Reuse the original German strings from pre-Phase-5 code where they existed (consent checkbox text, validation messages from `form.js`, etc.) — these were production strings users have already seen
- Informal "du" register was an explicit choice — applies consistently to all ~50 translated strings

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 06-translation-files-and-js-i18n*
*Context gathered: 2026-03-24*
