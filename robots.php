<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: " . url('admin/') . "\nDisallow: " . url('data/') . "\n\nSitemap: " . absolute_url('sitemap.php') . "\n";
