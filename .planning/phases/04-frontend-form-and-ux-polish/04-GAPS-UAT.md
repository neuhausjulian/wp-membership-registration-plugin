---
status: complete
phase: 04-frontend-form-and-ux-polish
source: 04-06-SUMMARY.md, 04-07-SUMMARY.md, 04-08-SUMMARY.md
started: 2026-03-23T22:30:00Z
updated: 2026-03-23T22:30:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Consent Checkbox Always Renders
expected: Visit a page with [membership_form]. The consent checkbox (DSGVO) is visible in the form even if you have never saved a custom "Consent Text" in the Form Settings tab. The checkbox label shows a German default text like "Ich stimme der Verarbeitung meiner personenbezogenen Daten zu."
result: pass

### 2. Success Message Not Empty
expected: Submit the form with valid data. The success area shows a text message — either your configured "Success Message" from Form Settings, or a default "Thank you for your registration!" fallback — not an empty green box.
result: pass

### 3. Email-Sent Note in Success Area
expected: Submit the form with a valid email address filled in. After the success message text, a note appears indicating the form/PDF will be sent to the email address provided.
result: pass

### 4. Offer Direct Download Checkbox in Form Settings
expected: In wp-admin → WP Membership Registration → Form Settings tab, a new "Offer direct download" checkbox is present. Enabling it and saving works without error.
result: pass

### 5. PDF Download Link on Success (when enabled)
expected: With "Offer direct download" enabled in Form Settings, submit the form. The success area contains a clickable link to download the pre-filled PDF immediately. Clicking it downloads the PDF.
result: pass

### 6. form_notes HTML Formatting in PDF
expected: In PDF Branding settings, add formatted content in the "Form Notes" field using TinyMCE (e.g. a paragraph and a line break). Generate a filled PDF (submit the form). Open the PDF — the form_notes content respects paragraphs and line breaks, matching how "Page 2 Content" renders.
result: issue
reported: "pdf is getting generated, but line breaks (visibell in the richtext editor in the settings) are not respected. Only the section with bulletpoints seems to have some spacing"
severity: major

## Summary

total: 6
passed: 5
issues: 1
pending: 0
skipped: 0

## Gaps

- truth: "form_notes HTML content from wp_editor() renders with correct paragraph spacing and line breaks in the generated filled PDF"
  status: failed
  reason: "User reported: pdf is getting generated, but line breaks (visibell in the richtext editor in the settings) are not respected. Only the section with bulletpoints seems to have some spacing"
  severity: major
  test: 6
  root_cause: ""
  artifacts: []
  missing: []
  debug_session: ""
