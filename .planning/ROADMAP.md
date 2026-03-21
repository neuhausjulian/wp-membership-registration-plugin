# Roadmap: WP Membership Registration Plugin

## Overview

Four phases derived from the component dependency graph. Settings drive everything else, so the admin configuration layer is built first. The PDF engine comes next as the highest-risk component — isolated for focused security review before anything depends on it. Email notifications follow once the PDF temp-file contract is established. The frontend form is built last so that a single test submission exercises the entire stack end-to-end. Each phase delivers a verifiable capability before the next begins.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Plugin Foundation and Settings** - Working admin settings page with field schema editor, PDF branding config, and email recipient config; dev environment and coding standards running in CI (completed 2026-03-21)
- [ ] **Phase 2: PDF Engine** - Plugin generates a pre-filled, branded PDF from submitted data; blank template downloadable; DOMPDF secured and vendor-namespaced
- [ ] **Phase 3: Email Notifications** - Member and admin both receive the generated PDF by email on submission; test-email button confirms SMTP before go-live
- [ ] **Phase 4: Frontend Form and UX Polish** - Embeddable registration form via shortcode with validation, spam protection, GDPR consent, and success message; full end-to-end integration test passes

## Phase Details

### Phase 1: Plugin Foundation and Settings
**Goal**: Admin can configure every aspect of the plugin — form fields, PDF branding, and recipient emails — through a standard WordPress settings page, and developers can run the local environment and code-quality checks with one command
**Depends on**: Nothing (first phase)
**Requirements**: CONF-01, CONF-02, CONF-03, CONF-04, CONF-05, DEV-01, DEV-03
**Success Criteria** (what must be TRUE):
  1. Admin can navigate to the plugin settings page and add, remove, and reorder registration form fields (label, field type, required toggle) without touching code
  2. Admin can upload a club logo, set a club name, and choose an accent color for the PDF template from the settings UI
  3. Admin can enter one or more recipient email addresses for notification emails and save them from the settings UI
  4. Admin can click a "Send test email" button and receive a test email at the configured address
  5. Running `docker compose up` launches a working local WordPress instance; running PHPCS reports zero violations on plugin source files
**Plans**: 3 plans

Plans:
- [ ] 01-01-PLAN.md — Dev environment (docker-compose.yml, Mailpit mu-plugin) and PHPCS/WPCS tooling (composer.json, .phpcs.xml)
- [ ] 01-02-PLAN.md — Plugin PHP skeleton: main file, Plugin.php, SettingsPage.php, SettingsRegistrar.php, FieldSchema.php
- [ ] 01-03-PLAN.md — Settings UI: admin template, admin JS, admin CSS, AjaxHandlers.php; human verification checkpoint

### Phase 2: PDF Engine
**Goal**: Every form submission produces a correctly branded, pre-filled PDF in the system temp directory; the same template with empty fields is available for manual download; the DOMPDF dependency is secure and namespace-prefixed for distribution
**Depends on**: Phase 1
**Requirements**: PDF-01, PDF-02, PDF-03
**Success Criteria** (what must be TRUE):
  1. Submitting a test form produces a PDF file containing all submitted field values in the correct positions
  2. The generated PDF displays the admin-configured club name, logo, and accent color
  3. A visitor can download a blank version of the PDF template (empty fields, with branding) via a shortcode parameter without submitting any data
  4. The PDF is written to `sys_get_temp_dir()` and deleted after use; no PDF file appears in `wp-content/uploads/`
**Plans**: 4 plans

Plans:
- [ ] 02-01-PLAN.md — DOMPDF install + Strauss namespace scoping + PHPUnit/Brain Monkey scaffold + test stub (Wave 0)
- [ ] 02-02-PLAN.md — Extend wmr_pdf_branding settings with 4 new fields (document_title, gdpr_text, footer_text, page2_content) (Wave 1, parallel)
- [ ] 02-03-PLAN.md — PdfGenerator class + HTML template + 3 passing PHPUnit tests (Wave 1, parallel)
- [ ] 02-04-PLAN.md — Wire blank-PDF AJAX handler + human-verify checkpoint (Wave 2)

### Phase 3: Email Notifications
**Goal**: Every form submission triggers email delivery of the generated PDF to both the submitting member and all configured admin recipients; admins can verify email deliverability from the settings page before going live
**Depends on**: Phase 2
**Requirements**: MAIL-01, MAIL-02, CONF-05
**Success Criteria** (what must be TRUE):
  1. A member who submits the registration form receives an email with the generated PDF attached within seconds of submission
  2. All configured admin recipient addresses receive a copy of the same PDF by email after each submission
  3. Admin can click "Send test email" in settings and confirm a test message arrives at the configured address (SMTP verification)
**Plans**: TBD

### Phase 4: Frontend Form and UX Polish
**Goal**: Any WordPress page with the `[membership_form]` shortcode shows a working registration form that validates input, blocks bots, requires GDPR consent, submits successfully, and confirms to the user that their registration was received; all user-facing guides are complete
**Depends on**: Phase 3
**Requirements**: FORM-01, FORM-02, FORM-03, FORM-04, FORM-05, DEV-02, DEV-04, DEV-05
**Success Criteria** (what must be TRUE):
  1. A visitor on any page with `[membership_form]` sees a registration form rendered from the admin-configured field schema
  2. The form displays a GDPR consent checkbox with admin-configurable text; submission is blocked until it is checked
  3. Submitting the form with missing required fields or an invalid email shows inline error messages without a page reload
  4. After a valid submission, the form shows a configurable success message and the full pipeline (PDF generation + both emails) completes
  5. PHPUnit unit tests and integration tests pass in the Docker environment; `CONTRIBUTING.md` and user `README.md` exist and cover setup, configuration, and usage
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Plugin Foundation and Settings | 3/3 | Complete   | 2026-03-21 |
| 2. PDF Engine | 2/4 | In Progress|  |
| 3. Email Notifications | 0/TBD | Not started | - |
| 4. Frontend Form and UX Polish | 0/TBD | Not started | - |
