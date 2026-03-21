---
created: 2026-03-21T13:23:41.176Z
title: Align field labels right and normalize input field start width in PDF
area: ui
files:
  - templates/pdf/membership-form.php
---

## Problem

In the generated PDF, field labels are left-aligned and input fields start at different horizontal positions depending on label length. This looks inconsistent and unprofessional.

## Solution

Use a two-column table layout in the PDF template: fixed-width right-aligned label column (e.g. 50mm) and a fixed-start input column that fills the remaining width. This gives all input underlines/fields the same left edge regardless of label length. DOMPDF supports basic table layouts well.
