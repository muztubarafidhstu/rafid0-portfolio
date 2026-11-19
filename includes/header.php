<?php
/** Public site header. Expects $page_title, $active, optional $meta_description, $meta_image. */
$page_title = $page_title ?? '';
$active = $active ?? '';
$__desc = $meta_description ?? setting('meta_description');
$__img = $meta_image ?? setting('hero_image');
$__alias = setting('site_alias');
$__title = $page_title !== '' ? $page_title . ' — ' . site_name()
    : site_name() . ($__alias !== '' ? ' (' . $__alias . ')' : '') . ' — ' . setting('site_title');
$__accent = setting('accent_color', '#22c55e');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $__accent)) {
    $__accent = '#22c55e';
}
$__nav = [
    'home' => ['Home', 'index.php'],
    'projects' => ['Projects', 'projects.php'],
    'research' => ['Research', 'research.php'],
    'gallery' => ['Gallery', 'gallery.php'],
    'downloads' => ['Downloads', 'downloads.php'],
    'about' => ['About', 'about.php'],
    'cv' => ['CV', 'cv.php'],
    'contact' => ['Contact', 'contact.php'],
];
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
?><!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__title) ?></title>
<meta name="description" content="<?= e(excerpt($__desc, 160)) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($__title) ?>">
<meta property="og:description" content="<?= e(excerpt($__desc, 200)) ?>">
<?php if ($__img !== ''): ?><meta property="og:image" content="<?= e(preg_match('~^https?://~', $__img) ? $__img : absolute_url($__img)) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($active === 'home'):
    $__same = array_values(array_filter(array_map(function ($s) { return $s['url']; }, social_links()), function ($u) { return strpos($u, 'http') === 0; }));
    $__person = ['@context' => 'https://schema.org', '@type' => 'Person', 'name' => site_name(), 'jobTitle' => setting('site_title'),
        'url' => absolute_url(), 'description' => excerpt($__desc, 300)];
    if ($__alias !== '') $__person['alternateName'] = $__alias;
    if (setting('about_photo') !== '') $__person['image'] = absolute_url(setting('about_photo'));
    if ($__same) $__person['sameAs'] = $__same;
    if (setting('contact_location') !== '') $__person['address'] = ['@type' => 'PostalAddress', 'addressLocality' => setting('contact_location')];
    $__site = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => site_name() . ($__alias !== '' ? ' (' . $__alias . ')' : ''),
        'alternateName' => $__alias !== '' ? $__alias : site_name(), 'url' => absolute_url()];
?>
<script type="application/ld+json"><?= json_encode([$__person, $__site], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
<link rel="canonical" href="<?= e(absolute_url(ltrim(substr(strtok($_SERVER['REQUEST_URI'] ?? '/', '#'), strlen(BASE_URL)), '/'))) ?>">
<link rel="icon" href="<?= e(setting('logo') !== '' ? media_url(setting('logo')) : url('favicon.php')) ?>">
<script>
(function(){document.documentElement.classList.add('js');try{var t=localStorage.getItem('theme');if(!t){t=window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<style>:root{--accent:<?= e($__accent) ?>;}</style>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" id="top">
  <div class="container header-inner">
    <a class="brand" href="<?= e(url()) ?>">
      <?= logo_html() ?>
      <span class="brand-text">
        <strong><?= e(site_name()) ?></strong>
        <small><?= e(setting('site_title')) ?></small>
      </span>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="Main">
      <?php foreach ($__nav as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle light / dark theme">
        <?= icon('moon', 'only-dark') ?><?= icon('sun', 'only-light') ?>
      </button>
      <button class="icon-btn" type="button" data-search-open aria-label="Search">
        <?= icon('search') ?>
      </button>
      <button class="icon-btn nav-toggle" type="button" data-nav-toggle aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">
        <?= icon('menu') ?>
      </button>
    </div>
  </div>
</header>

<div class="search-overlay" data-search hidden>
  <form class="search-box" action="<?= e(url('search.php')) ?>" method="get" role="search">
    <?= icon('search') ?>
    <input type="search" name="q" placeholder="Search projects, research, downloads…" aria-label="Search" autocomplete="off">
    <button type="button" class="icon-btn" data-search-close aria-label="Close search"><?= icon('x-close') ?></button>
  </form>
</div>

<main id="main">
