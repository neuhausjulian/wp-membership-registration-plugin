---
created: 2026-03-21T13:23:41.176Z
title: Increase spacing between GDPR text and signature section in PDF
area: ui
files:
  - templates/pdf/membership-form.php
---

## Problem

In the generated PDF, the space between the GDPR/consent text block and the signature/date section is too tight. It looks cramped and is hard to read.

## Solution

Increase the `margin-top` or `padding-top` on the signature section div in `membership-form.php`. Likely a small CSS tweak (e.g. `margin-top: 8mm`). Test by regenerating the blank PDF and visually inspecting.
