<?php
/** Default favicon: the site's initials in the accent colour. Replaced automatically once a logo is uploaded. */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
$accent = setting('accent_color', '#22c55e');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) $accent = '#22c55e';
echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#0b1117"/>'
   . '<text x="32" y="42" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="28" fill="' . e($accent) . '">'
   . e(initials(site_name())) . '</text></svg>';
