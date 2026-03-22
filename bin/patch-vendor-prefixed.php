<?php
/**
 * Post-Strauss patch script.
 *
 * No patches required for TCPDF (unlike DOMPDF, no hard-coded namespace strings to patch).
 * TCPDF uses classmap prefixing so Strauss renames class names directly in the source files.
 *
 * Previously patched dompdf/php-font-lib hard-coded "FontLib\\" strings. Those patches are
 * now obsolete because dompdf is no longer a dependency.
 *
 * This file must remain — composer.json scripts reference it via @php bin/patch-vendor-prefixed.php.
 */

echo "No patches required.\n";
