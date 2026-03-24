# Requirements: WP Membership Registration Plugin

**Defined:** 2026-03-24
**Core Value:** A member can register digitally and receive a legally valid, pre-filled membership form (PDF) without the club needing to pay for any software or manage a spreadsheet.

## v1.1 Requirements

Requirements for the Localization milestone. Each maps to roadmap phases.

### Internationalization

- [ ] **I18N-01**: All admin settings page strings are wrapped in WordPress i18n functions and translatable
- [ ] **I18N-02**: All frontend form strings (labels, buttons, validation messages, success/error feedback) are wrapped in WordPress i18n functions and translatable
- [ ] **I18N-03**: Plugin registers its text domain via `load_plugin_textdomain()` on init
- [ ] **I18N-04**: A `.pot` source file is generated for the plugin (enables future community translations)
- [ ] **I18N-05**: German (`de_DE`) `.po` and `.mo` translation files are provided covering all plugin strings
- [ ] **I18N-06**: JavaScript strings (client-side validation, AJAX error/success messages) are translatable and delivered via `wp_set_script_translations()` with a German JSON locale file
- [ ] **I18N-07**: Admin can configure a fallback language (de or en) in plugin settings; defaults to the site language (German if site is German, English otherwise)

## Future Requirements

### Broader Language Support

- **I18N-F01**: Support additional locales beyond de/en
- **I18N-F02**: Per-visitor browser language detection for the frontend form

## Out of Scope

| Feature | Reason |
|---------|--------|
| PDF structural labels (column headers, field labels in generated PDF) | Out of scope for v1.1 — web UI strings only |
| Languages beyond de/en | Deferred — de/en covers the pilot club's needs |
| Per-visitor browser language detection | Complexity not justified for single-club deployments |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| I18N-01 | Phase 5 | Pending |
| I18N-02 | Phase 5 | Pending |
| I18N-03 | Phase 5 | Pending |
| I18N-07 | Phase 5 | Pending |
| I18N-04 | Phase 6 | Pending |
| I18N-05 | Phase 6 | Pending |
| I18N-06 | Phase 6 | Pending |

**Coverage:**
- v1.1 requirements: 7 total
- Mapped to phases: 7
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-24*
*Last updated: 2026-03-24 — traceability updated after roadmap creation (Phases 5–6)*
