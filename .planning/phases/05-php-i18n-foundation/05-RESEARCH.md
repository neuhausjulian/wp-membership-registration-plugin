# Phase 5: PHP i18n Foundation - Research

**Researched:** 2026-03-24
**Domain:** WordPress internationalization (PHP layer + JS localization wiring)
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Source language policy**
- ALL msgids (source strings) must be English — no German strings in PHP or JS source code
- German text is provided exclusively via `.po/.mo` translation files (Phase 6 generates these)
- The consent checkbox default in `FormRenderer.php:92` must be changed from German (`'Ich stimme der Verarbeitung...'`) to English (`'I consent to the processing of my personal data.'`)
- No migration needed for existing installs — de_DE locale sites will get German text from the translation file

**Consent text handling**
- If the admin has set a custom `consent_text` in plugin settings, use it verbatim (no translation applied)
- The i18n-wrapped English default only applies when the setting is empty/unset
- This preserves existing behavior — custom text wins over translation

**Fallback language setting**
- Plugin-level locale override: a dropdown in admin settings with values `auto` / `de` / `en`
- Default value: `auto` — auto-detects from WP site language (de_DE → German, otherwise English)
- When set to `de` or `en`, the plugin forces its own locale regardless of WP's site language setting
- Affects BOTH admin settings page AND frontend form (all plugin strings)
- Placed in the General tab of plugin settings (alongside other plugin-wide options)

**String audit scope**
- Quick grep audit of `templates/admin-settings-page.php` (~308 lines) for any bare strings missed by existing wrapping
- Include email strings in `Mailer.php` — email subjects and body text are user-facing and should be verified/wrapped
- PDF generator (`PdfGenerator.php`) is excluded — PDF structural labels are out of scope per requirements
- Audit covers: admin settings template, FormRenderer.php, AjaxHandlers.php, Mailer.php, SettingsPage.php, Plugin.php

**JS string handling (Phase 5 scope)**
- Convert all 6 hardcoded German strings in `assets/js/form.js` to English, passed via `wp_localize_script` i18n sub-object
- Convert all 8 hardcoded English strings in `assets/js/admin-settings.js` to translatable strings via `wp_localize_script` i18n sub-object
- Pattern: extend existing localized data objects — `wmrForm.i18n = { consentError: __('...'), ... }` and `wmrSettings.i18n = { dragToReorder: __('...'), ... }`
- Phase 6 will later upgrade this to `wp_set_script_translations()` with JSON locale files

### Claude's Discretion
- Exact implementation of the locale override mechanism (e.g., `switch_to_locale()` vs custom `load_textdomain()` call)
- Option key naming for the fallback language setting
- Ordering of the language dropdown relative to other General tab fields
- Grep patterns used for the quick audit

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.

Also explicitly out of scope: `.pot` file generation, `.po/.mo` compilation, `wp_set_script_translations()` delivery (Phase 6). PDF structural labels remain out of scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| I18N-01 | All admin settings page strings wrapped in WordPress i18n functions | Admin template already uses `esc_html_e()` throughout; audit confirms no bare strings remain (template passes inspection). JS strings in `admin-settings.js` need `wp_localize_script` delivery. |
| I18N-02 | All frontend form strings wrapped in WordPress i18n functions | `FormRenderer.php` nearly complete except German msgid at line 92. JS strings in `form.js` (6 German hardcoded) need `wp_localize_script` delivery. |
| I18N-03 | Plugin registers text domain via `load_plugin_textdomain()` on init | Text domain header already declared in main plugin file; `load_plugin_textdomain()` call and `languages/` directory creation are the two concrete tasks. |
| I18N-07 | Admin can configure fallback language (de/en); defaults to site language | `switch_to_locale()` / `restore_current_locale()` pattern provides the mechanism; setting registered in `SettingsRegistrar` and stored in `wmr_form_settings` or a dedicated option. |
</phase_requirements>

---

## Summary

Phase 5 wraps the PHP string audit, text domain registration, fallback language setting, and JS string wiring. The codebase is already in good shape — the admin template and AjaxHandlers.php use `esc_html_e()` / `__()` consistently. The concrete work is:

1. Register the text domain on `init` and create the `languages/` directory.
2. Fix the one known German msgid in `FormRenderer.php:92`.
3. Add a "Fallback Language" dropdown to the Form Settings tab (extending `SettingsRegistrar`), and implement the locale override mechanism using `switch_to_locale()` / `restore_current_locale()`.
4. Wire JS i18n strings via `wp_localize_script` i18n sub-objects on both admin and frontend scripts.
5. Update the one existing test that asserts the German default consent string.

**Primary recommendation:** Use `switch_to_locale()` / `restore_current_locale()` on the `init` hook to apply the fallback language override; this is the WordPress-idiomatic mechanism for per-plugin locale forcing, introduced in WP 4.7, well within the plugin's `Requires at least: 6.0` floor.

---

## Standard Stack

### Core
| Function | Version | Purpose | Why Standard |
|----------|---------|---------|--------------|
| `load_plugin_textdomain()` | WP core | Registers the plugin's `.mo` file with WordPress translation system | The canonical entry point for plugin translation loading |
| `__()` | WP core | Returns translated string | Used throughout; the basic translation getter |
| `esc_html_e()` | WP core | Echoes HTML-escaped translated string | Used for all template output; combines escaping + translation |
| `esc_attr_e()` | WP core | Echoes attribute-escaped translated string | For HTML attribute contexts (already used in template) |
| `esc_html__()` | WP core | Returns HTML-escaped translated string | For inline expressions where echo is separate |
| `switch_to_locale()` | WP 4.7+ | Temporarily forces a specific locale | Standard WP mechanism for per-context locale overrides |
| `restore_current_locale()` | WP 4.7+ | Reverts to the locale before `switch_to_locale()` | Paired with `switch_to_locale()`; must always be called after |
| `wp_localize_script()` | WP core | Passes PHP data (including i18n strings) to JS | Existing pattern already used for `wmrForm.successMessage` and `wmrSettings` |

### Supporting
| Function | Version | Purpose | When to Use |
|----------|---------|---------|-------------|
| `get_locale()` | WP core | Returns current WP site locale (e.g., `de_DE`) | Used to implement `auto` detection for fallback language setting |
| `register_setting()` | WP core | Registers a new option with sanitize callback | Already used in `SettingsRegistrar`; extend for fallback language setting |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `switch_to_locale()` on `init` | Custom `load_textdomain()` with a locale-specific `.mo` file path | `switch_to_locale()` is cleaner: it works with already-loaded textdomains, handles the stack, and integrates with WP's locale system. Custom loading requires managing file paths and doesn't properly unwind nested switches. |
| `wp_localize_script()` for JS i18n | `wp_set_script_translations()` | Phase 6 will upgrade to `wp_set_script_translations()` + JSON locale files; Phase 5 uses `wp_localize_script()` because no `.pot`/`.po` exists yet. |

**Installation:** No new packages required. All functions are WP core.

---

## Architecture Patterns

### Recommended Project Structure Changes
```
wp-membership-registration-plugin/
├── languages/                  # NEW — must be created (currently absent)
│   └── .gitkeep               # Placeholder; .mo files go here in Phase 6
├── src/
│   ├── Plugin.php             # ADD: load_plugin_textdomain() on init + switch_to_locale() logic
│   ├── Admin/
│   │   ├── SettingsRegistrar.php  # ADD: fallback_language field in wmr_form_settings
│   │   └── SettingsPage.php       # ADD: i18n sub-object to wmrSettings wp_localize_script call
│   └── Frontend/
│       └── FormRenderer.php   # FIX: German msgid at line 92 → English
└── templates/
    └── admin-settings-page.php  # ADD: fallback language dropdown in Form Settings tab
                                  # (admin template already fully wrapped)
```

### Pattern 1: Text Domain Registration on init
**What:** Call `load_plugin_textdomain()` early so all subsequent `__()` calls resolve against the loaded `.mo`.
**When to use:** Once, in `Plugin::register()` on the `init` hook.

```php
// In Plugin::register()
add_action(
    'init',
    static function () {
        load_plugin_textdomain(
            'wp-membership-registration',
            false,
            dirname( plugin_basename( WMR_PLUGIN_FILE ) ) . '/languages'
        );
    }
);
```

Note: WordPress 6.7+ performs just-in-time loading for plugins hosted on WordPress.org, but an explicit `load_plugin_textdomain()` call is still required for self-hosted / locally-developed plugins and is the correct practice for this codebase.

### Pattern 2: Locale Override with switch_to_locale()
**What:** Read the plugin's fallback_language setting on `init`. If `de`, switch to `de_DE`; if `en`, switch to `en_US`; if `auto`, inspect `get_locale()` — de_DE stays, anything else becomes English (no switch needed since WP English is the default).
**When to use:** Immediately after `load_plugin_textdomain()` in the same `init` hook closure, so the locale is set before any rendering happens.

```php
// In Plugin::register(), same init closure after load_plugin_textdomain()
$settings           = get_option( 'wmr_form_settings', array() );
$fallback_language  = $settings['fallback_language'] ?? 'auto';

if ( 'de' === $fallback_language ) {
    switch_to_locale( 'de_DE' );
} elseif ( 'en' === $fallback_language ) {
    switch_to_locale( 'en_US' );
}
// 'auto': no override — WP's site language applies naturally
```

`restore_current_locale()` is NOT needed here because the locale override is meant to persist for the entire request. `switch_to_locale()` pushed onto the stack is fine; WP cleans up at request end. However, if defensive cleanup is desired, it can be hooked on `shutdown`.

### Pattern 3: wp_localize_script i18n Sub-object
**What:** Extend existing `wp_localize_script` calls with an `i18n` key containing all JS-visible strings.
**When to use:** In `SettingsPage::enqueue_scripts()` for admin JS, and in `Plugin.php`'s `wp_enqueue_scripts` closure for frontend JS.

```php
// In SettingsPage::enqueue_scripts() — extend existing wmrSettings array:
wp_localize_script(
    'wmr-admin-settings',
    'wmrSettings',
    array(
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'testEmailNonce' => wp_create_nonce( 'wmr_send_test_email' ),
        'i18n'           => array(
            'dragToReorder'       => __( 'Drag to reorder', 'wp-membership-registration' ),
            'deleteField'         => __( 'Delete field', 'wp-membership-registration' ),
            'noFieldsDefined'     => __( 'No fields defined yet.', 'wp-membership-registration' ),
            'addFirstField'       => __( 'Click Add Field to define the first registration form field.', 'wp-membership-registration' ),
            'selectLogoTitle'     => __( 'Select Club Logo', 'wp-membership-registration' ),
            'selectLogoButton'    => __( 'Use this image', 'wp-membership-registration' ),
            'testEmailSent'       => __( 'Test email sent.', 'wp-membership-registration' ),
            'unknownError'        => __( 'An unknown error occurred.', 'wp-membership-registration' ),
            'sending'             => __( 'Sending\u2026', 'wp-membership-registration' ),
        ),
    )
);
```

```php
// In Plugin.php wp_enqueue_scripts closure — extend existing wmrForm array:
wp_localize_script(
    'wmr-form',
    'wmrForm',
    array(
        'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
        'submitNonce'    => wp_create_nonce( 'wmr_submit_form' ),
        'successMessage' => $success_message,
        'i18n'           => array(
            'consentRequired'   => __( 'Please confirm your consent.', 'wp-membership-registration' ),
            'fieldRequired'     => __( 'This field is required.', 'wp-membership-registration' ),
            'invalidEmail'      => __( 'Please enter a valid email address.', 'wp-membership-registration' ),
            'emailCopySent'     => __( 'A copy has been sent to your email address.', 'wp-membership-registration' ),
            'downloadLink'      => __( 'Download your completed form now', 'wp-membership-registration' ),
            'genericError'      => __( 'An error occurred. Please try again.', 'wp-membership-registration' ),
        ),
    )
);
```

Then in JS, replace hardcoded strings with `wmrForm.i18n.fieldRequired` etc.

### Pattern 4: Fallback Language Setting Registration
**What:** Add `fallback_language` key to the existing `wmr_form_settings` option.
**When to use:** In `SettingsRegistrar::sanitize_form_settings()`.

```php
// In SettingsRegistrar::sanitize_form_settings()
$allowed_languages = array( 'auto', 'de', 'en' );
$fallback          = $input['fallback_language'] ?? 'auto';
return array(
    'consent_text'          => sanitize_text_field( $input['consent_text'] ?? '' ),
    'success_message'       => sanitize_text_field( $input['success_message'] ?? '' ),
    'offer_direct_download' => ! empty( $input['offer_direct_download'] ),
    'fallback_language'     => in_array( $fallback, $allowed_languages, true ) ? $fallback : 'auto',
);
```

And in the admin template (Form Settings tab):
```php
<tr>
    <th scope="row">
        <label for="wmr-fallback-language"><?php esc_html_e( 'Fallback Language', 'wp-membership-registration' ); ?></label>
    </th>
    <td>
        <select id="wmr-fallback-language" name="wmr_form_settings[fallback_language]">
            <option value="auto" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'auto' ); ?>>
                <?php esc_html_e( 'Auto (follow site language)', 'wp-membership-registration' ); ?>
            </option>
            <option value="de" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'de' ); ?>>
                <?php esc_html_e( 'German (de)', 'wp-membership-registration' ); ?>
            </option>
            <option value="en" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'en' ); ?>>
                <?php esc_html_e( 'English (en)', 'wp-membership-registration' ); ?>
            </option>
        </select>
        <p class="description"><?php esc_html_e( 'Override the language used for plugin strings. Defaults to the WordPress site language.', 'wp-membership-registration' ); ?></p>
    </td>
</tr>
```

### Anti-Patterns to Avoid
- **German source strings in PHP or JS:** All msgids must be English. The German consent default at `FormRenderer.php:92` is the only known violation and must be fixed.
- **Calling load_plugin_textdomain() before init:** Text domain loading before the `init` hook can cause issues in some WordPress configurations. Always use `init`.
- **Missing restore after switch_to_locale():** If `switch_to_locale()` is called inside a function that exits early on error, the locale stack leaks. For request-wide overrides, skip `restore_current_locale()` since WP tears down at shutdown; for scoped overrides (e.g., per-email), always use try/finally.
- **Hardcoded strings in JS:** All user-visible JS strings must come from the `i18n` sub-object, not literals.
- **Extending wmr_form_settings default without migration:** The `register_setting` default array must include `fallback_language => 'auto'` so new installs get the correct default and `get_option()` fallbacks work.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Locale switching | Custom `load_textdomain()` with hardcoded `.mo` path | `switch_to_locale()` / `restore_current_locale()` | Handles `.mo` reloading, locale stack unwinding, and integration with WP's locale system automatically |
| Escaping + translation | Separate `esc_html( __('...') )` calls | `esc_html__()` / `esc_html_e()` / `esc_attr_e()` | Reduces typos, the combined functions are the WP standard |
| JavaScript string delivery | Inline `<script>` blocks with string literals | `wp_localize_script()` with i18n sub-object | Ties to the script handle's enqueue lifecycle; survives script dependency resolution |

**Key insight:** WP's translation system is complete and handles all edge cases (locale stack, `.mo` file discovery, fallback chains). The only work needed is registering the text domain and calling the right i18n functions at the right callsite.

---

## Common Pitfalls

### Pitfall 1: languages/ directory absent at load time
**What goes wrong:** `load_plugin_textdomain()` silently does nothing if the `languages/` directory does not exist. Translation files will never load even after Phase 6 generates them.
**Why it happens:** The directory is not created by WordPress — it must exist in the plugin repo.
**How to avoid:** Create the `languages/` directory (with a `.gitkeep` or `.gitignore`) as the first task in Phase 5.
**Warning signs:** `load_plugin_textdomain()` returns `false` — but it's silent. Check with `WP_DEBUG`.

### Pitfall 2: German msgid in FormRenderer.php:92 breaks Phase 6
**What goes wrong:** The `.pot` file (generated in Phase 6 by xgettext/wp i18n make-pot) will extract the German string as a msgid. Translators would then need to "translate German to German" — the de_DE `.po` file would need an identical source and target, which is wrong and confusing.
**Why it happens:** The string was hardcoded in German before i18n was scoped.
**How to avoid:** Change to English: `__( 'I consent to the processing of my personal data.', 'wp-membership-registration' )` in Phase 5. The de_DE translation file in Phase 6 provides the German rendering.
**Warning signs:** The test `test_render_includes_consent_checkbox_when_consent_text_empty()` in `FormRendererTest.php` currently asserts the German string — this test must be updated alongside the source change.

### Pitfall 3: Forgetting to update wmr_form_settings register_setting default
**What goes wrong:** `get_option( 'wmr_form_settings', array() )` on a fresh install returns an array without `fallback_language`. The `switch_to_locale()` code reads `$settings['fallback_language'] ?? 'auto'` — the null-coalescing handles it, but `register_setting()` default does not, meaning the Settings API won't return a default on a totally fresh DB.
**Why it happens:** The default array in `register_setting()` for `wmr_form_settings` doesn't include `fallback_language`.
**How to avoid:** Add `'fallback_language' => 'auto'` to the `default` array in `SettingsRegistrar::register()` for `wmr_form_settings_group`.

### Pitfall 4: switch_to_locale() called before load_plugin_textdomain()
**What goes wrong:** If locale switching happens before the text domain is loaded, the `.mo` file for the new locale is not loaded, and strings fall back to English regardless of setting.
**Why it happens:** Wrong ordering within the `init` callback.
**How to avoid:** In the `init` hook closure, always call `load_plugin_textdomain()` first, then `switch_to_locale()`.

### Pitfall 5: JS strings not using wmrForm.i18n / wmrSettings.i18n
**What goes wrong:** If JS files reference `wmrForm.i18n.fieldRequired` but PHP code doesn't add `i18n` to the localized object, JS will throw `TypeError: Cannot read properties of undefined`.
**Why it happens:** PHP and JS get out of sync during development.
**How to avoid:** Add `i18n` sub-object to `wp_localize_script` in the same commit that updates the JS references.

---

## Code Examples

Verified patterns from official WordPress documentation:

### load_plugin_textdomain() canonical usage
```php
// Source: https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
add_action( 'init', function () {
    load_plugin_textdomain(
        'wp-membership-registration',
        false,
        dirname( plugin_basename( WMR_PLUGIN_FILE ) ) . '/languages'
    );
} );
```

### switch_to_locale() / restore_current_locale() pattern
```php
// Source: https://developer.wordpress.org/reference/functions/switch_to_locale/
// For request-wide override (no restore needed — WP tears down at shutdown):
switch_to_locale( 'de_DE' );

// For scoped override (e.g., sending a single email in a specific locale):
switch_to_locale( 'de_DE' );
try {
    // ... locale-specific operation ...
} finally {
    restore_current_locale();
}
```

### Consent default — corrected English msgid
```php
// FormRenderer.php line 90-93 — BEFORE (broken):
$consent_label = $consent_text
    ? $consent_text
    : __( 'Ich stimme der Verarbeitung meiner personenbezogenen Daten zu.', 'wp-membership-registration' );

// AFTER (correct):
$consent_label = $consent_text
    ? $consent_text
    : __( 'I consent to the processing of my personal data.', 'wp-membership-registration' );
```

### JS i18n consumption pattern
```javascript
// In form.js — replace hardcoded German string:
// BEFORE:
setError( field, 'Dieses Feld ist erforderlich.' );

// AFTER:
setError( field, wmrForm.i18n.fieldRequired );
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `load_plugin_textdomain()` on `plugins_loaded` | `init` hook (or just-in-time in WP 6.7+) | WP 6.7 | Explicit `init` call still required for self-hosted plugins; WP 6.7 JIT only applies to WordPress.org hosted plugins |
| Separate `esc_html( __('...', 'domain') )` | Combined `esc_html_e( '...', 'domain' )` | Long-standing WP best practice | Simpler, less error-prone; already the pattern used throughout this codebase |
| `wp_localize_script()` for JS i18n | `wp_set_script_translations()` with JSON locale files | WP 5.0+ | Phase 6 will upgrade; Phase 5 uses `wp_localize_script()` as bridge since no `.pot`/`.po` exists yet |

**Deprecated/outdated:**
- Passing an `$abs_rel_path` as second parameter to `load_plugin_textdomain()`: Use `false` for the deprecated second param, and the relative path as the third param.

---

## Open Questions

1. **Should switch_to_locale() be called on every request, or only on admin/frontend separately?**
   - What we know: The decision says "Affects BOTH admin settings page AND frontend form (all plugin strings)".
   - What's unclear: Whether it's cleaner to apply in `init` unconditionally or to gate on `is_admin()` vs frontend context.
   - Recommendation: Apply unconditionally in `init` — it's simpler and the fallback_language setting is plugin-wide per the spec.

2. **Does switch_to_locale() on init affect other plugins or WP core strings?**
   - What we know: `switch_to_locale()` changes the global locale, not just the plugin's text domain. This could theoretically affect other plugins' `__()` calls that run after `init`.
   - What's unclear: Whether this is a problem in practice for this single-plugin deployment scenario.
   - Recommendation: For now, accept this behavior — the plugin is a self-contained deployment and WP core strings on the admin side will match the forced locale, which is consistent. If needed in a future multi-plugin environment, scope the switch to only wrap the plugin's output hooks.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (version from composer.json) |
| Config file | `phpunit.xml` |
| Quick run command | `./vendor/bin/phpunit --filter FormRenderer` |
| Full suite command | `./vendor/bin/phpunit` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| I18N-01 | Admin template strings output translated text; no bare English | unit | `./vendor/bin/phpunit --filter SettingsPageTest` | ❌ Wave 0 |
| I18N-02 | FormRenderer outputs English default consent string (not German) | unit | `./vendor/bin/phpunit --filter FormRendererTest::test_render_includes_consent_checkbox_when_consent_text_empty` | ✅ (test must be updated) |
| I18N-02 | FormRenderer consent label when custom text is set | unit | `./vendor/bin/phpunit --filter FormRendererTest::test_render_includes_consent_checkbox_when_consent_text_set` | ✅ |
| I18N-03 | load_plugin_textdomain() is hooked on init | unit | `./vendor/bin/phpunit --filter PluginTest` | ❌ Wave 0 |
| I18N-07 | Fallback language setting persists via SettingsRegistrar sanitize | unit | `./vendor/bin/phpunit --filter SettingsRegistrarTest` | ❌ Wave 0 (existing file, but new test needed) |
| I18N-07 | switch_to_locale() called for 'de' setting on init | unit | `./vendor/bin/phpunit --filter PluginTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `./vendor/bin/phpunit --filter {RelevantTestClass}`
- **Per wave merge:** `./vendor/bin/phpunit`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/PluginTest.php` — covers I18N-03 (load_plugin_textdomain on init) and I18N-07 (switch_to_locale for 'de'/'en' settings)
- [ ] `tests/Unit/Admin/SettingsRegistrarTest.php` — extend existing (file may already exist) to cover fallback_language sanitize and default value
- [ ] Update `tests/Unit/Frontend/FormRendererTest.php::test_render_includes_consent_checkbox_when_consent_text_empty` — assert English default, not German

*(Existing test files: `tests/Unit/Admin/AjaxHandlersTest.php`, `tests/Unit/Frontend/FormRendererTest.php`, `tests/Unit/Email/MailerTest.php` — no new framework setup needed)*

---

## Sources

### Primary (HIGH confidence)
- `https://developer.wordpress.org/reference/functions/load_plugin_textdomain/` — exact parameters and hook timing
- `https://developer.wordpress.org/reference/functions/switch_to_locale/` — parameters, return value, use cases
- `https://developer.wordpress.org/reference/functions/restore_current_locale/` — pairing with switch_to_locale
- `https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/` — i18n function usage, text domain header format

### Secondary (MEDIUM confidence)
- Direct code inspection of `src/Plugin.php`, `src/Admin/SettingsPage.php`, `src/Admin/SettingsRegistrar.php`, `src/Frontend/FormRenderer.php`, `src/Admin/AjaxHandlers.php`, `src/Email/Mailer.php`, `templates/admin-settings-page.php`, `assets/js/form.js`, `assets/js/admin-settings.js`, `tests/bootstrap.php` — HIGH confidence (first-hand source reading)

### Tertiary (LOW confidence)
- None.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all functions are WP core, confirmed against official docs
- Architecture: HIGH — based on direct code inspection of all audit targets
- Pitfalls: HIGH for pitfalls 1-4 (known code issues); MEDIUM for pitfall 5 (confirmed by pattern analysis)

**Research date:** 2026-03-24
**Valid until:** 2026-04-24 (stable WordPress i18n APIs, low churn)
