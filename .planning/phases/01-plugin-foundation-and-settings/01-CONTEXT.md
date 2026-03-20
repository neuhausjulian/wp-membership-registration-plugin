# Phase 1: Plugin Foundation and Settings - Context

**Gathered:** 2026-03-20
**Status:** Ready for planning

<domain>
## Phase Boundary

Admin can configure every aspect of the plugin — form fields, PDF branding, and recipient emails — through a standard WordPress settings page. Developers can launch a local WordPress environment and run code-quality checks with one command. No PDF generation, no email sending, no frontend form in this phase.

</domain>

<decisions>
## Implementation Decisions

### Settings page location and structure
- Lives under Settings › Membership Registration (WP standard convention for plugins)
- Three tabs: **Form Fields** | **PDF Branding** | **Email Settings**
- Tab switching is JavaScript-driven (single page load, JS shows/hides sections) — not separate WP settings pages per tab
- All settings saved via a single save button per tab (standard WP settings pattern — no auto-save)

### Field schema editor (Form Fields tab)
- Drag-and-drop row reordering using jQuery UI Sortable (already bundled with WordPress — no extra dependency)
- Drag handle on the left of each row; rows show: label input, field type dropdown, required toggle, delete button
- New rows default to field type "text"; admin changes via the type dropdown (no upfront type picker)
- Field types supported: text, email, date, tel, textarea, select
- Field schema stored as serialized JSON in a single WP option (Settings API does not natively support repeater fields)

### PDF Branding tab
- Club logo: WP Media Library picker (standard `wp.media` frame) — stores the attachment URL in options
- Accent color: WP iris color picker (`wp-color-picker`) — ships with WordPress, no extra dependency
- Club name: plain text input

### Email Settings tab
- One or more recipient email addresses (textarea, one per line, or comma-separated — Claude's discretion on format)
- "Send test email" button lives on this tab, contextually next to the recipient config
- Button triggers an AJAX call to send a test message to the configured addresses

### Docker dev environment
- Official `wordpress` image (PHP + Apache) as specified in DEV-01 — easiest for contributors
- Services: WordPress + MySQL + **Mailpit** (SMTP catcher with web UI, included from Phase 1 so no Docker changes are needed in Phase 3) + **Adminer** (lightweight DB inspector)
- Plugin source mounted as a volume into the WordPress plugins directory
- `docker compose up` is the single command to launch the full environment

### PHPCS / coding standards
- WordPress Coding Standards (WPCS) enforced via PHPCS with a `.phpcs.xml` config
- CI runs PHPCS on plugin source files; zero violations required to pass

### Claude's Discretion
- Exact column widths and visual spacing in the field schema editor table
- Whether recipient emails are stored comma-separated or one-per-line (textarea)
- Port assignments for Mailpit (UI) and Adminer in docker-compose.yml
- WordPress version pinned in docker-compose.yml (latest or specific)
- Composer or manual PHPCS setup (whichever fits contributor workflow better)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — Full v1 requirement list; Phase 1 covers CONF-01–05, DEV-01, DEV-03
- `.planning/PROJECT.md` — Non-negotiables: GPL-2.0 license, no external SaaS, WP 6.0+, PHP 8.0+

No external specs or ADRs exist yet — requirements are fully captured in decisions above and in REQUIREMENTS.md.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- None yet — this is a greenfield project with no existing PHP or JS files.

### Established Patterns
- None yet — Phase 1 establishes the patterns all subsequent phases will follow.

### Integration Points
- Plugin main file will register the settings page, enqueue scripts/styles, and hook into `admin_menu` and `admin_init`
- Field schema stored as a WP option will be the contract that Phase 2 (PDF) and Phase 4 (Form) read from

</code_context>

<specifics>
## Specific Ideas

No specific references or "I want it like X" moments came up during discussion — open to standard WordPress patterns for all implementation details.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-plugin-foundation-and-settings*
*Context gathered: 2026-03-20*
