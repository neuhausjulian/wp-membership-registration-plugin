# Roadmap: WP Membership Registration Plugin

## Milestones

- ✅ **v1.0 MVP** — Phases 1–4 (shipped 2026-03-24)
- 🚧 **v1.1 Localization** — Phases 5–6 (in progress)

## Phases

<details>
<summary>✅ v1.0 MVP (Phases 1–4) — SHIPPED 2026-03-24</summary>

- [x] Phase 1: Plugin Foundation and Settings (3/3 plans) — completed 2026-03-21
- [x] Phase 2: PDF Engine (4/4 plans) — completed 2026-03-21
- [x] Phase 3: Email Notifications (4/4 plans) — completed 2026-03-22
- [x] Phase 4: Frontend Form and UX Polish (10/10 plans) — completed 2026-03-24

</details>

### 🚧 v1.1 Localization (In Progress)

**Milestone Goal:** Make all plugin UI strings properly translatable and ship de/en translations so the admin settings and frontend form reflect the site's configured language.

- [ ] **Phase 5: PHP i18n Foundation** — Register text domain, wrap all PHP strings in admin and frontend, add fallback language setting
- [ ] **Phase 6: Translation Files and JS i18n** — Generate .pot source file, compile de_DE .po/.mo, deliver JS translations via wp_set_script_translations()

## Phase Details

### Phase 5: PHP i18n Foundation
**Goal**: All PHP-layer strings in admin settings and frontend form are translatable via WordPress i18n functions, the text domain is registered on init, and admins can configure a fallback language.
**Depends on**: Phase 4
**Requirements**: I18N-01, I18N-02, I18N-03, I18N-07
**Success Criteria** (what must be TRUE):
  1. Admin settings page renders all labels, tab names, button text, and help strings through `__()` or `esc_html_e()` — no bare English strings remain in SettingsManager.php output
  2. Frontend form renders all field labels, placeholder text, validation messages, GDPR checkbox label, and submit button through i18n functions — no bare strings in FormRenderer.php or AjaxHandlers.php output
  3. A "Fallback Language" option (de / en) appears in plugin settings; selecting "de" and switching the site to German causes admin and frontend strings to render in German
  4. `load_plugin_textdomain()` is called on the `init` hook and correctly points at the plugin's `languages/` directory
**Plans:** 1/3 plans executed

Plans:
- [ ] 05-01-PLAN.md — Register text domain on init and create languages/ directory
- [ ] 05-02-PLAN.md — Wire admin JS i18n strings through wp_localize_script
- [ ] 05-03-PLAN.md — Fix frontend consent string, wire frontend JS i18n, add fallback language setting

### Phase 6: Translation Files and JS i18n
**Goal**: A .pot source file is generated from the wrapped strings, German .po/.mo translation files are compiled and ship with the plugin, and JavaScript client-side strings are delivered as translatable JSON via WordPress script translations API.
**Depends on**: Phase 5
**Requirements**: I18N-04, I18N-05, I18N-06
**Success Criteria** (what must be TRUE):
  1. A `languages/wp-membership-registration.pot` file exists and contains all translatable strings from PHP source files (no missing strings relative to the wrapped codebase)
  2. `languages/wp-membership-registration-de_DE.po` and `languages/wp-membership-registration-de_DE.mo` exist; installing the plugin on a site with locale `de_DE` causes admin settings and frontend form to render in German without any manual configuration
  3. Client-side validation messages, AJAX success/error feedback, and any other JS-originated strings render in German on a `de_DE` site — delivered via `wp_set_script_translations()` reading a `languages/wp-membership-registration-de_DE-{script-handle}.json` file
**Plans**: TBD

Plans:
- [ ] 06-01: Generate .pot file and compile de_DE .po/.mo translations
- [ ] 06-02: Wire up JS translations via wp_set_script_translations() with German JSON locale file

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Plugin Foundation and Settings | v1.0 | 3/3 | Complete | 2026-03-21 |
| 2. PDF Engine | v1.0 | 4/4 | Complete | 2026-03-21 |
| 3. Email Notifications | v1.0 | 4/4 | Complete | 2026-03-22 |
| 4. Frontend Form and UX Polish | v1.0 | 10/10 | Complete | 2026-03-24 |
| 5. PHP i18n Foundation | 1/3 | In Progress|  | - |
| 6. Translation Files and JS i18n | v1.1 | 0/2 | Not started | - |

---
*Full milestone archive: `.planning/milestones/v1.0-ROADMAP.md`*
