---
created: 2026-03-21T13:23:41.176Z
title: Add rich text editor for GDPR and Page 2 fields
area: ui
files:
  - templates/admin-settings-page.php
  - src/Admin/SettingsRegistrar.php
---

## Problem

The `gdpr_text` and `page2_content` fields in the PDF Branding settings tab are plain textareas. Admins who want formatted text (bold, links, line breaks) must write raw HTML, which is not user-friendly. The fields already use `wp_kses_post()` so HTML is supported — it's only the editing experience that is missing.

## Solution

Replace the plain textareas with a WordPress rich text editor (wp_editor() / TinyMCE) or a markdown editor. WordPress's built-in `wp_editor()` is the simplest option — it renders a TinyMCE instance inside the settings page and outputs HTML that `wp_kses_post()` already accepts. Alternatively, a lightweight markdown editor (e.g. EasyMDE) could be used with a markdown-to-HTML conversion step before saving.
