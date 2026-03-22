# Requirements: WP Membership Registration Plugin

**Defined:** 2026-03-20
**Core Value:** A member can register digitally and receive a legally valid, pre-filled membership form (PDF) without the club needing to pay for any software or manage a spreadsheet.

## v1 Requirements

### Admin Configuration

- [x] **CONF-01**: Admin can define registration form fields via settings page (label, field type: text/email/date/tel/textarea/select, required toggle)
- [x] **CONF-02**: Admin can add, remove, and reorder form fields in the settings UI
- [x] **CONF-03**: Admin can configure PDF branding: club name, logo upload, accent color
- [x] **CONF-04**: Admin can configure one or more recipient email addresses for PDF notification emails
- [x] **CONF-05**: Admin can send a test email from the settings page to verify SMTP delivery is working

### Frontend Form

- [ ] **FORM-01**: Visitor can access the registration form on any page via the `[membership_form]` shortcode
- [x] **FORM-02**: Form includes a GDPR/privacy consent checkbox with admin-configurable text (required to submit)
- [ ] **FORM-03**: Form uses honeypot spam protection to block automated bot submissions
- [ ] **FORM-04**: Form validates required fields and email format client-side before submission
- [x] **FORM-05**: Form displays a configurable success message after successful submission

### PDF Generation

- [x] **PDF-01**: Plugin generates a pre-filled PDF on every form submission, containing all submitted field values
- [x] **PDF-02**: Generated PDF incorporates admin-configured branding (club name, logo, accent color)
- [x] **PDF-03**: A blank (empty-fields) version of the PDF template is downloadable via shortcode parameter for manual fallback

### Email Notifications

- [x] **MAIL-01**: Member receives the generated PDF as an email attachment immediately after submitting the form
- [x] **MAIL-02**: All configured admin recipient(s) receive a copy of the generated PDF by email after each submission

### Developer Experience

- [x] **DEV-01**: Plugin ships a Docker-based local development environment using `docker-compose.yml` with the official WordPress image (no Node.js/npm required)
- [ ] **DEV-02**: Plugin includes a PHPUnit test suite: unit tests (Brain Monkey, no WP bootstrap) and integration tests (Docker WP environment)
- [x] **DEV-03**: WordPress coding standards enforced via PHPCS/WPCS with a `.phpcs.xml` config that runs in CI
- [ ] **DEV-04**: Plugin includes a `CONTRIBUTING.md` developer guide covering local setup, running tests, coding conventions, and PR submission
- [ ] **DEV-05**: Plugin includes a user-facing `README.md` covering installation, settings configuration, and shortcode usage

## v2 Requirements

### Automation

- **AUTO-01**: Plugin fires a webhook (HTTP POST with JSON payload) to a configurable URL on each form submission
- **AUTO-02**: Plugin sends a machine-readable JSON payload by email to a configurable address on each submission

### Member Management

- **MGMT-01**: Admin can view a list of all form submissions in the WordPress backend
- **MGMT-02**: Admin can export submission data as CSV

### UX Enhancements

- **UX-01**: Plugin provides a Gutenberg block alternative to the shortcode for block editor users
- **UX-02**: Form fields support conditional logic (show/hide based on another field's value)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Payment / dues collection | Different product category; clubs handle this separately |
| WordPress user account creation on registration | Clubs don't need members to log into the site |
| Filling existing AcroForm PDFs | Fragile without proper AcroForm fields; generating from template is more reliable |
| File upload fields | Storage, MIME validation, and GDPR implications are out of v1 scope |
| Member approval workflow (admin gates registration) | v1 is self-service; approval adds admin burden for small clubs |
| CAPTCHA / reCAPTCHA | Honeypot is sufficient for v1 and avoids accessibility issues |
| Multisite WordPress support | Out of scope; standard single-site install covers all pilot use cases |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| CONF-01 | Phase 1 | Complete |
| CONF-02 | Phase 1 | Complete |
| CONF-03 | Phase 1 | Complete |
| CONF-04 | Phase 1 | Complete |
| CONF-05 | Phase 1 | Complete |
| FORM-01 | Phase 4 | Pending |
| FORM-02 | Phase 4 | Complete |
| FORM-03 | Phase 4 | Pending |
| FORM-04 | Phase 4 | Pending |
| FORM-05 | Phase 4 | Complete |
| PDF-01 | Phase 2 | Complete |
| PDF-02 | Phase 2 | Complete |
| PDF-03 | Phase 2 | Complete |
| MAIL-01 | Phase 3 | Complete |
| MAIL-02 | Phase 3 | Complete |
| DEV-01 | Phase 1 | Complete |
| DEV-02 | Phase 4 | Pending |
| DEV-03 | Phase 1 | Complete |
| DEV-04 | Phase 4 | Pending |
| DEV-05 | Phase 4 | Pending |

**Coverage:**
- v1 requirements: 20 total
- Mapped to phases: 20
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-20*
*Last updated: 2026-03-20 after roadmap creation — all 20 v1 requirements mapped*
