---
created: 2026-03-21T13:23:41.176Z
title: Add single and multi select field types
area: ui
files:
  - src/Admin/SettingsPage.php
  - templates/admin-settings-page.php
  - src/Util/FieldSchema.php
  - templates/pdf/membership-form.php
---

## Problem

The form field builder currently only supports text fields. Clubs need dropdown (single select) and checkbox/multi-select field types for things like membership category, payment method, or interests.

## Solution

Add `select` and `multiselect` as field type options in the field builder. Each needs an options list (comma-separated or repeater). The PDF template needs to render selected values correctly — single select as the chosen value, multi-select as a comma-separated list or checkboxes.
