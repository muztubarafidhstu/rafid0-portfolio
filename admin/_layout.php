<?php
/** Admin panel layout helpers. */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

function admin_header(string $title, string $active = ''): void
{
    $unread = count_rows('messages', 'is_read = 0');
    $nav = [
        'dashboard' => ['Dashboard', 'admin/index.php', 'dashboard'],
        'projects' => ['Projects', 'admin/projects.php', 'box'],
        'research' => ['Research', 'admin/research.php', 'book'],
        'gallery' => ['Gallery', 'admin/gallery.php', 'image'],
        'downloads' => ['Downloads', 'admin/downloads.php', 'download'],
        'messages' => ['Messages', 'admin/messages.php', 'inbox'],
        'profile' => ['Profile, About & CV', 'admin/profile.php', 'user'],
        'settings' => ['Site Settings', 'admin/settings.php', 'settings'],
    ];
    $accent = setting('accent_color', '#22c55e');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) $accent = '#22c55e';
    ?><!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Admin Panel</title>
<link rel="icon" href="<?= e(url('favicon.php')) ?>">
<script>(function(){try{var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style>:root{--accent:<?= e($accent) ?>;}</style>
</head>
<body class="admin" data-max-upload="<?= (int) max_upload_bytes() ?>">
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="brand admin-brand" href="<?= e(url('admin/index.php')) ?>"><?= logo_html() ?><span class="brand-text"><strong>Admin Panel</strong><small><?= e(site_name()) ?></small></span></a>
    <nav class="admin-nav">
      <?php foreach ($nav as $key => [$label, $href, $ic]): ?>
        <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' class="active"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span>
          <?php if ($key === 'messages' && $unread > 0): ?><em class="count"><?= $unread ?></em><?php endif; ?></a>
      <?php endforeach; ?>
      <hr>
      <a href="<?= e(url()) ?>" target="_blank"><?= icon('external') ?><span>View website</span></a>
      <a href="<?= e(url('admin/logout.php')) ?>"><?= icon('logout') ?><span>Logout</span></a>
    </nav>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button class="icon-btn admin-menu" type="button" data-admin-menu aria-label="Menu"><?= icon('menu') ?></button>
      <h1><?= e($title) ?></h1>
      <div class="admin-top-actions">
        <button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle theme"><?= icon('moon', 'only-dark') ?><?= icon('sun', 'only-light') ?></button>
      </div>
    </header>
    <div class="admin-content">
      <?= render_flashes() ?>
<?php
}

function admin_footer(): void
{
    ?>
      <p class="admin-foot muted small">&copy; <?= date('Y') ?> <?= e(site_name()) ?>. Portfolio CMS v<?= e(APP_VERSION) ?></p>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Delete-button form with confirmation. */
function delete_button(string $action, int $id, string $label = 'Delete', string $extra = ''): string
{
    return '<form method="post" action="' . e(url($action)) . '" class="inline" data-confirm="Delete this item permanently? This cannot be undone.">'
        . csrf_field() . '<input type="hidden" name="delete" value="' . $id . '">' . $extra
        . '<button class="icon-btn sm danger" type="submit" title="' . e($label) . '" aria-label="' . e($label) . '">' . icon('trash') . '</button></form>';
}

function checked($v): string
{
    return $v ? ' checked' : '';
}
