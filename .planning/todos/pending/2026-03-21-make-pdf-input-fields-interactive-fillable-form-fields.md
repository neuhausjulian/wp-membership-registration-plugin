---
created: 2026-03-21T13:23:41.176Z
title: Make PDF input fields interactive fillable form fields
area: ui
files:
  - templates/pdf/membership-form.php
  - src/Pdf/PdfGenerator.php
---

## Problem

The blank PDF currently renders input fields as static underlines (___). Admins and members would benefit from actual interactive PDF form fields that can be typed into directly in a PDF viewer.

## Solution

DOMPDF does not support AcroForm interactive fields natively. Options:
1. Use a different library for the blank PDF specifically (e.g. TCPDF or a dedicated AcroForm library) that supports fillable fields.
2. Post-process the DOMPDF output with a library like `mikehaertl/php-pdftk` (requires pdftk binary) to overlay form fields.
3. Accept the limitation for v1 and revisit in v2 — underlines are a common pattern in printable membership forms.

Option 3 is likely the pragmatic v1 choice; options 1/2 add significant complexity.
