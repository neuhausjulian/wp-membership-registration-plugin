# Phase 2: PDF Engine - Context

**Gathered:** 2026-03-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Generate a branded, pre-filled PDF from submitted form data and store it in the system temp directory. A blank (empty-fields) version of the same template is downloadable via shortcode parameter as a manual fallback. DOMPDF is bundled safely (vendor-namespaced) for WordPress plugin distribution. No email sending in this phase — that is Phase 3.

</domain>

<decisions>
## Implementation Decisions

### PDF header layout
- Logo top-left, club name + document title on the right, accent color separator line below the header block
- If no logo is configured, club name + document title span the full header width
- Structure:
  ```
  +----------------------------------------+
  | [LOGO]   Club Name                     |
  |          Document Title                |
  +----------------------------------------+
  [accent color separator line]
  ```

### Admin-configurable static text sections
- **Four new fields** added to the PDF Branding settings tab (Phase 1 settings extended in this phase):
  - `wmr_document_title` — text input, e.g. "Aufnahmeantrag" (document title shown in header)
  - `wmr_gdpr_text` — textarea, GDPR/consent paragraph shown above the signature line
  - `wmr_footer_text` — textarea, board addresses + bank details shown at the bottom of page 1
  - `wmr_page2_content` — textarea (HTML or plain text), rendered as a static second page (e.g. Datenschutzerklärung, membership fee info). If empty, PDF is single-page.

### Field rendering style
- Each field rendered as: `Label:  Value` on a single line
- Label in regular weight, value filled in after colon — matches the reference document layout
- For blank PDF (empty values): label shown with a long underline (e.g. `Name:  _______________`)

### Document structure (page 1)
1. Header block (logo + club name + document title + accent separator)
2. Dynamic fields (from admin-configured field schema) — label: value rows
3. GDPR/consent paragraph (from `wmr_gdpr_text`)
4. Place + date line: `[City], ____________________`
5. Signature line: `Unterschrift: _______________________`
6. Footer (from `wmr_footer_text`)

### Page 2
- If `wmr_page2_content` is set: appended as a second page with the static content (rendered via DOMPDF)
- If empty: single-page PDF

### Blank PDF download
- Trigger: shortcode parameter — `[membership_form download="blank"]` renders a download link instead of the form
- Same template as filled PDF, empty field values shown as underlines
- All static sections (GDPR text, footer, page 2) included — full document, just no submitted data

### Paper format
- A4 portrait, hardcoded — standard for European associations
- No admin override needed for v1

### Temp file handling
- PDF written to `sys_get_temp_dir()` with a unique filename
- Deleted after use (after email attachment in Phase 3, or after blank download response)
- Never written to `wp-content/uploads/` — no persistent storage of member data

### DOMPDF bundling
- DOMPDF vendored via Composer and namespace-prefixed (using Mozart or manual scoping) to avoid conflicts with other plugins
- Prefix: `WpMembershipRegistration\Vendor\` — standard WordPress plugin distribution pattern
- Claude's discretion on exact scoping toolchain (Mozart vs manual)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 2 covers PDF-01, PDF-02, PDF-03
- `.planning/PROJECT.md` — Constraints: PHP 8.0+, WP 6.0+, no external SaaS, GPL-2.0

### Reference document
- `/home/jean-luc/Schreibtisch/Aufnahmeantrag-2025_.pdf` — The real membership form this plugin replicates. Page 1: dynamic fields + GDPR paragraph + signature + footer. Page 2: static Datenschutzerklärung + membership fee info. A4 portrait. Label-value rows.

### Phase 1 (settings contracts)
- `.planning/phases/01-plugin-foundation-and-settings/01-CONTEXT.md` — Branding options: club name (`wmr_branding_settings[club_name]`), logo URL (`wmr_branding_settings[logo_url]`), accent color (`wmr_branding_settings[accent_color]`). Field schema: `wmr_field_schema` (JSON via `FieldSchema::decode()`).
- `src/Admin/SettingsRegistrar.php` — Option group registrations and sanitize callbacks; Phase 2 must extend this with 4 new branding fields
- `src/Util/FieldSchema.php` — `encode()`/`decode()` for the field schema JSON option

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Util/FieldSchema.php` — `FieldSchema::decode()` returns the admin-configured field array; PdfGenerator reads this to know which fields to render
- `src/Admin/SettingsRegistrar.php` — Must be extended with 4 new `wmr_branding_settings` sub-keys: `document_title`, `gdpr_text`, `footer_text`, `page2_content`
- `src/Plugin.php` — Hook orchestrator; new `PdfGenerator` hooks (`wp_ajax_wmr_download_blank_pdf`, and a submission action for Phase 3) registered here

### Established Patterns
- PSR-4 autoloading via `spl_autoload_register` in main plugin file — new classes go in `src/` under the `WpMembershipRegistration\` namespace
- WordPress options API for all settings — `get_option('wmr_branding_settings')` returns array with sub-keys
- WPCS coding standards enforced via PHPCS — all new PHP must pass `vendor/bin/phpcs`

### Integration Points
- Phase 3 (Email) will call `PdfGenerator::generate(array $field_values): string` and receive a temp file path — this is the contract to establish in Phase 2
- Phase 4 (Form) will trigger PDF generation on form submission and pass the submitted field values
- Blank PDF shortcode: `[membership_form download="blank"]` — new shortcode or parameter on existing shortcode; handled by a new `ShortcodeHandler` class or registered in `Plugin.php`

</code_context>

<specifics>
## Specific Ideas

- Reference document: `/home/jean-luc/Schreibtisch/Aufnahmeantrag-2025_.pdf` — the real form for the pilot club (Bienenzüchterverein für Leipzig und Umgegend e.V.). The generated PDF should be a branded, dynamic version of this exact document structure.
- "Document title" field was specifically requested by the user — must be configurable, not hardcoded as "Aufnahmeantrag"

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 02-pdf-engine*
*Context gathered: 2026-03-21*
