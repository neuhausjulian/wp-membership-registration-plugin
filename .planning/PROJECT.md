# WP Membership Registration Plugin

## What This Is

An open-source WordPress plugin that digitizes the member registration process for small clubs and associations. Visitors fill an interactive, admin-configured form on the frontend and immediately receive a pre-filled PDF (ready to print, sign, and return). The club admin gets an email copy of the same PDF, and optionally a webhook or JSON payload fires to trigger further automation. No SaaS, no subscription — self-hosted and free forever.

## Core Value

A member can register digitally and receive a legally valid, pre-filled membership form (PDF) without the club needing to pay for any software or manage a spreadsheet.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Admin can configure form fields (add, remove, reorder) in WP settings
- [ ] Frontend registration form is embeddable via shortcode
- [ ] Admin can configure the PDF layout/template in WP settings
- [ ] On form submission, plugin generates a pre-filled PDF from the configured template
- [ ] Member receives the generated PDF by email after submitting the form
- [ ] Admin receives a copy of the generated PDF by email
- [ ] Admin can configure the recipient email address(es) for notifications
- [ ] Optionally: plugin fires a webhook (HTTP POST) on submission
- [ ] Optionally: plugin sends a JSON payload by email on submission
- [ ] Plugin ships with a Docker-based local development environment (WordPress in a container)
- [ ] Plugin includes a developer guide (setup, local dev, testing, PR workflow)
- [ ] Plugin includes a user guide (installation, configuration, usage)

### Out of Scope

- Member list / admin backend — email delivery is sufficient for v1, no stored member records
- Payment / dues collection — out of scope, clubs handle this separately
- Filling existing AcroForm PDFs — fragile, replaced by configurable template approach
- Gutenberg block — shortcode sufficient for v1, reduces complexity
- Member approval workflow — v1 is self-service, no admin gating
- OAuth / social login — not relevant, members don't need WP accounts

## Context

- **Target users**: Small clubs and associations (sports, hobby, community) using WordPress
- **Club admin persona**: Non-technical, manages the club website, wants simple WP settings UI
- **Member persona**: Anyone wanting to join the club, may be non-technical, fills form once
- **Pilot club**: Developer's own club — real-world usage from day one
- **Open source**: GPL-2.0 license, hosted on GitHub, accepts community contributions
- **Existing process**: Clubs currently use paper forms or spreadsheets; the blank PDF form will remain available for download as a manual fallback
- **Legal requirement**: PDF must be printable and signable (wet signature still required by many clubs)

## Constraints

- **Tech stack**: PHP (WordPress plugin API), JavaScript for interactive form UX, HTML→PDF generation (no AcroForm dependency)
- **Compatibility**: WordPress 6.0+, PHP 8.0+
- **Dev environment**: Docker Compose with an official WordPress image for local testing
- **Distribution**: WordPress plugin directory standards (or direct GitHub install via ZIP)
- **No external SaaS**: All processing happens on the WordPress server — no third-party APIs required for core functionality

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Generate PDF from template (not fill existing PDF) | Mapping values to existing PDFs requires AcroForm fields and breaks when PDFs change; generating from a configured layout is more reliable | — Pending |
| Email-only for v1 (no member database) | Keeps v1 scope tight; clubs already handle record-keeping; a member list can be added in v2 | — Pending |
| Shortcode over Gutenberg block for v1 | Works in all editors/builders, less code to maintain, community PRs easier | — Pending |

---
*Last updated: 2026-03-20 after initialization*
