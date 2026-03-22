<?php
/**
 * Patch hard-coded class name strings in vendor-prefixed/ that Strauss cannot rewrite.
 *
 * dompdf/php-font-lib uses dynamic class instantiation via hard-coded "FontLib\\" strings
 * and a getFontType() method that assumes a fixed namespace depth — both invisible to Strauss.
 *
 * Upstream fix: https://github.com/dompdf/php-font-lib/pull/148 (open, unmerged as of 2026-03)
 * Remove this script once that PR is released and composer.json is updated past that version.
 *
 * Run this after `composer prefix-namespaces`.
 * Called via composer scripts: post-install-cmd, post-update-cmd.
 */

$base = __DIR__ . '/../vendor-prefixed';

$patches = [
    // Font.php: dynamic class string "FontLib\\$class" -> prefixed
    $base . '/dompdf/php-font-lib/src/FontLib/Font.php' => [
        [
            'find'    => '"FontLib\\\\',
            'replace' => '"WpMembershipRegistration\\\\Vendor\\\\FontLib\\\\',
        ],
    ],

    // TrueType/File.php: two fixes —
    //   1. getFontType() uses explode()[1] which is now "Vendor" instead of "TrueType"
    //   2. dynamic class string "FontLib\\$type\\TableDirectoryEntry" -> prefixed
    $base . '/dompdf/php-font-lib/src/FontLib/TrueType/File.php' => [
        [
            'find'    => "  function getFontType(){\n    \$class_parts = explode(\"\\\\\\\\\", get_class(\$this));\n    return \$class_parts[1];\n  }",
            'replace' => "  function getFontType(){\n    \$class_parts = explode(\"\\\\\\\\\", get_class(\$this));\n    \$idx = array_search('FontLib', \$class_parts);\n    return \$class_parts[\$idx !== false ? \$idx + 1 : 1];\n  }",
        ],
        [
            'find'    => '"FontLib\\\\',
            'replace' => '"WpMembershipRegistration\\\\Vendor\\\\FontLib\\\\',
        ],
    ],
];

foreach ( $patches as $file => $file_patches ) {
    if ( ! file_exists( $file ) ) {
        echo "SKIP (not found): $file\n";
        continue;
    }

    $contents = file_get_contents( $file );
    $patched  = $contents;

    foreach ( $file_patches as $p ) {
        $patched = str_replace( $p['find'], $p['replace'], $patched );
    }

    if ( $patched === $contents ) {
        echo "SKIP (already patched): $file\n";
        continue;
    }

    file_put_contents( $file, $patched );
    echo "PATCHED: $file\n";
}

echo "Done.\n";
