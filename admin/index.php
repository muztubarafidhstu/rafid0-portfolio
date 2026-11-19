<?php
require __DIR__ . '/_layout.php';
require_admin();

$cards = [
    ['Projects', count_rows('projects'), 'box', 'admin/projects.php'],
    ['Research', count_rows('research'), 'book', 'admin/research.php'],
    ['Images', count_rows('gallery', "type = 'image'") + count_rows('project_media', "type = 'image'"), 'image', 'admin/gallery.php'],
    ['Videos', count_rows('gallery', "type = 'video'") + count_rows('project_media', "type = 'video'"), 'video', 'admin/gallery.php'],
    ['Downloads', count_rows('downloads') + count_rows('project_media', "type = 'document'"), 'download', 'admin/downloads.php'],
    ['New messages', count_rows('messages', 'is_read = 0'), 'inbox', 'admin/messages.php'],
];
$recent = db()->query('SELECT * FROM projects ORDER BY updated_at DESC, id DESC LIMIT 6')->fetchAll();
$messages = db()->query('SELECT * FROM messages ORDER BY id DESC LIMIT 5')->fetchAll();
$totalViews = (int) db()->query('SELECT COALESCE(SUM(views),0) FROM projects')->fetchColumn();
$totalDl = (int) db()->query('SELECT COALESCE(SUM(download_count),0) FROM downloads')->fetchColumn();

$todo = [];
if (setting('contact_email') === '') $todo[] = ['Add your contact email so visitors can reach you', 'admin/settings.php'];
if (setting('cv_file') === '') $todo[] = ['Upload your CV (PDF)', 'admin/profile.php#cv'];
if (strpos(setting('about_photo'), 'assets/img/demo') === 0) $todo[] = ['Upload your profile photo', 'admin/profile.php'];
if (strpos(setting('hero_image'), 'assets/img/demo') === 0) $todo[] = ['Upload your own homepage hero image', 'admin/settings.php#home'];
$demoCount = (int) db()->query("SELECT COUNT(*) FROM projects WHERE cover_image LIKE 'assets/img/demo/%'")->fetchColumn();
if ($demoCount) $todo[] = ["Replace sample images on {$demoCount} project(s) with your real photos", 'admin/projects.php'];

admin_header('Dashboard', 'dashboard');
?>
<div class="stat-cards">
  <?php foreach ($cards as [$label, $n, $ic, $href]): ?>
    <a class="card stat-card" href="<?= e(url($href)) ?>"><div><strong><?= $n ?></strong><span><?= e($label) ?></span></div><span class="stat-icon"><?= icon($ic) ?></span></a>
  <?php endforeach; ?>
</div>

<?php if ($todo): ?>
<div class="card pad mb">
  <h2 class="card-heading"><?= icon('sparkles') ?> Getting started checklist</h2>
  <ul class="todo-list">
    <?php foreach ($todo as [$t, $href]): ?><li><a href="<?= e(url($href)) ?>"><span class="todo-dot"></span><?= e($t) ?> <?= icon('arrow-right') ?></a></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="card mb">
  <div class="card-toolbar"><h2 class="card-heading">Recent Projects</h2><a class="btn btn-primary btn-sm" href="<?= e(url('admin/project-edit.php')) ?>"><?= icon('plus') ?> New Project</a></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Project Title</th><th>Category</th><th>Status</th><th>Date</th><th class="ta-r">Action</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $p): ?>
        <tr>
          <td><div class="cell-title"><?php if ($p['cover_image']): ?><img src="<?= e(media_url($p['cover_image'])) ?>" alt=""><?php endif; ?><a href="<?= e(url('admin/project-edit.php?id=' . $p['id'])) ?>"><?= e($p['title']) ?></a></div></td>
          <td class="muted"><?= e($p['category']) ?></td>
          <td><?= $p['published'] ? '<span class="pill pill-green">Published</span>' : '<span class="pill pill-amber">Draft</span>' ?></td>
          <td class="muted nowrap"><?= e(format_date($p['updated_at'], 'd M Y')) ?></td>
          <td class="ta-r nowrap">
            <a class="icon-btn sm" href="<?= e(url('project.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank" title="View"><?= icon('eye') ?></a>
            <a class="icon-btn sm" href="<?= e(url('admin/project-edit.php?id=' . $p['id'])) ?>" title="Edit"><?= icon('edit') ?></a>
            <?= delete_button('admin/projects.php', (int) $p['id']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="5" class="muted center">No projects yet. <a href="<?= e(url('admin/project-edit.php')) ?>">Add your first project</a>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="grid grid-2">
  <div class="card pad">
    <div class="card-toolbar flat"><h2 class="card-heading">Latest Messages</h2><a class="link-accent" href="<?= e(url('admin/messages.php')) ?>">View all <?= icon('arrow-right') ?></a></div>
    <?php if (!$messages): ?><p class="muted small">No messages yet. Messages sent from your Contact page will appear here.</p><?php endif; ?>
    <ul class="msg-mini">
      <?php foreach ($messages as $m): ?>
        <li class="<?= $m['is_read'] ? '' : 'unread' ?>"><a href="<?= e(url('admin/messages.php?id=' . $m['id'])) ?>"><strong><?= e($m['name']) ?></strong><span><?= e(excerpt($m['subject'] !== '' ? $m['subject'] : $m['message'], 60)) ?></span><small><?= e(format_date($m['created_at'], 'd M')) ?></small></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card pad">
    <h2 class="card-heading">Quick actions</h2>
    <div class="quick-grid">
      <a href="<?= e(url('admin/project-edit.php')) ?>"><?= icon('plus') ?> Add project</a>
      <a href="<?= e(url('admin/research-edit.php')) ?>"><?= icon('book') ?> Add research</a>
      <a href="<?= e(url('admin/gallery.php')) ?>"><?= icon('image') ?> Upload photos</a>
      <a href="<?= e(url('admin/downloads.php')) ?>"><?= icon('upload') ?> Add download</a>
      <a href="<?= e(url('admin/profile.php#cv')) ?>"><?= icon('file-text') ?> Update CV</a>
      <a href="<?= e(url('admin/settings.php')) ?>"><?= icon('settings') ?> Site settings</a>
    </div>
    <p class="muted small mt-1"><?= icon('eye') ?> <?= number_format($totalViews) ?> project page views · <?= icon('download') ?> <?= number_format($totalDl) ?> file downloads</p>
  </div>
</div>
<?php admin_footer(); ?>
