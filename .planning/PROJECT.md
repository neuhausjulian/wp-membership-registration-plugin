# WP Membership Registration Plugin

## What This Is

An open-source WordPress plugin that digitizes the member registration process for small clubs and associations. Visitors fill an interactive, admin-configured form on the frontend and immediately receive a pre-filled, branded PDF (ready to print, sign, and return). The club admin gets an email copy of the same PDF. A blank PDF template is available for manual download as a fallback. No SaaS, no subscription — self-hosted and free forever.

## Core Value

A member can register digitally and receive a legally valid, pre-filled membership form (PDF) without the club needing to pay for any software or manage a spreadsheet.

## Requirements

### Validated

- ✓ Admin can configure form fields (add, remove, reorder) in WP settings — v1.0
- ✓ Frontend registration form is embeddable via shortcode — v1.0
- ✓ Admin can configure PDF branding (club name, logo, accent color, document title) in WP settings — v1.0
- ✓ On form submission, plugin generates a pre-filled, branded PDF — v1.0 (using TCPDF)
- ✓ Member receives the generated PDF by email after submitting the form — v1.0
- ✓ Admin receives a copy of the generated PDF by email — v1.0
- ✓ Admin can configure the recipient email address(es) for notifications — v1.0
- ✓ Admin can send a test email from settings to verify SMTP delivery — v1.0
- ✓ Form displays GDPR consent checkbox with admin-configurable text — v1.0
- ✓ Form uses honeypot spam protection — v1.0
- ✓ Form validates required fields and email format client-side — v1.0
- ✓ Form displays configurable success message after submission — v1.0
- ✓ Plugin ships with Docker-based local development environment — v1.0
- ✓ Plugin includes PHPUnit test suite (Brain Monkey unit tests) — v1.0
- ✓ WordPress coding standards enforced via PHPCS/WPCS — v1.0
- ✓ Plugin includes CONTRIBUTING.md developer guide — v1.0
- ✓ Plugin includes user-facing README.md — v1.0

### Active

(None — v1.0 delivered all planned requirements. Add v1.1 requirements via `/gsd:new-milestone`.)

### Out of Scope

- Member list / admin backend — email delivery sufficient for v1, no stored member records
- Payment / dues collection — clubs handle this separately
- Filling existing AcroForm PDFs — fragile, replaced by configurable template approach; confirmed correct decision
- Gutenberg block — shortcode sufficient for v1, reduces complexity; confirmed correct decision
- Member approval workflow — v1 is self-service, no admin gating
- OAuth / social login — not relevant, members don't need WP accounts
- Webhook / JSON payload on submission — deferred beyond v1 scope
- Direct PDF download on submission — opted for transient-based base64 approach to avoid writes to uploads/

## Context

- **Target users**: Small clubs and associations (sports, hobby, community) using WordPress
- **Club admin persona**: Non-technical, manages the club website, wants simple WP settings UI
- **Member persona**: Anyone wanting to join the club, may be non-technical, fills form once
- **Pilot club**: Developer's own club — real-world usage from day one
- **Open source**: GPL-2.0 license, hosted on GitHub, accepts community contributions
- **Existing process**: Clubs currently use paper forms or spreadsheets; the blank PDF form remains available for download as a manual fallback
- **Legal requirement**: PDF must be printable and signable (wet signature still required by many clubs)
- **Shipped v1.0**: 2026-03-24 — 4 phases, 21 plans, ~2,100 LOC (PHP + JS + CSS)
- **Tech stack confirmed**: PHP 8.0+ / WordPress 6.0+, TCPDF (vendor-scoped via Strauss), Brain Monkey PHPUnit, Docker Compose

## Constraints

- **Tech stack**: PHP (WordPress plugin API), JavaScript for interactive form UX, HTML→PDF generation (no AcroForm dependency)
- **Compatibility**: WordPress 6.0+, PHP 8.0+
- **Dev environment**: Docker Compose with official WordPress image for local testing (ports 8080 + 8025 Mailpit)
- **Distribution**: WordPress plugin directory standards (or direct GitHub install via ZIP)
- **No external SaaS**: All processing happens on the WordPress server — no third-party APIs required for core functionality
- **PDF security**: `$isRemoteEnabled = false` — DOMPDF/TCPDF must never fetch remote resources; PDFs written to `sys_get_temp_dir()` and deleted after use, never to `wp-content/uploads/`

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Generate PDF from template (not fill existing PDF) | Mapping values to existing PDFs requires AcroForm fields and breaks when PDFs change; generating from a configured layout is more reliable | ✓ Good — TCPDF Cell() layout proved stable and easy to maintain |
| Email-only for v1 (no member database) | Keeps v1 scope tight; clubs already handle record-keeping | ✓ Good — no scope creep, core flow clean |
| Shortcode over Gutenberg block for v1 | Works in all editors/builders, less code to maintain, community PRs easier | ✓ Good — zero compatibility issues reported |
| Strauss over PHP-Scoper for vendor namespace scoping | Simpler config, no build step for dev; PHP-Scoper requires separate build pipeline | ✓ Good — classmap prefix `WpMembershipRegistration_Vendor_TCPDF` worked cleanly |
| TCPDF replacing DOMPDF | DOMPDF had remote-resource security risks; TCPDF offers direct Cell()/AcroForm API with full control | ✓ Good — native Cell() layout, AcroForm blank PDFs, zero security concerns |
| gdpr_text → form_notes rename | "gdpr_text" implied it was only for legal text; "form_notes" better reflects its role as free-form information block in the PDF | ✓ Good — migration fallback in sanitize_pdf_branding for existing installs |
| JSON-encoded field schema (wmr_field_schema) | Enables safe json_decode on both PHP and JS sides; wp_unslash() applied before decode | ✓ Good — no serialization issues in 21 plans |
| Consent checkbox always rendered (no admin gate) | GDPR compliance requires it always appear; default German label as translatable fallback | ✓ Good — compliance-correct, admin can still customize the label |
| PDF download via transient-stored base64 (not temp file) | Avoids writes to uploads/; satisfies security constraint; transient auto-expires | ✓ Good — clean, no file cleanup needed |

---
*Last updated: 2026-03-24 after v1.0 milestone*
