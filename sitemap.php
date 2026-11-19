<?php
/** XML sitemap for search engines: /sitemap.php */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$urls = ['', 'projects.php', 'research.php', 'gallery.php', 'downloads.php', 'about.php', 'cv.php', 'contact.php'];
foreach (db()->query('SELECT slug FROM projects WHERE published = 1') as $p) {
    $urls[] = 'project.php?slug=' . rawurlencode($p['slug']);
}
foreach (db()->query('SELECT slug FROM research WHERE published = 1') as $r) {
    $urls[] = 'research-item.php?slug=' . rawurlencode($r['slug']);
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . e(absolute_url($u)) . "</loc></url>\n";
}
echo '</urlset>';
