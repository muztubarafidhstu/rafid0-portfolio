<?php
require __DIR__ . '/includes/bootstrap.php';

$q = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
$page_title = $q !== '' ? 'Search: ' . $q : 'Search';
$active = '';
$projects = $research = $downloads = [];
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $st = db()->prepare("SELECT * FROM projects WHERE published = 1 AND (title LIKE :q ESCAPE '\\' OR subtitle LIKE :q ESCAPE '\\' OR tags LIKE :q ESCAPE '\\'
        OR category LIKE :q ESCAPE '\\' OR summary LIKE :q ESCAPE '\\' OR overview LIKE :q ESCAPE '\\' OR technologies LIKE :q ESCAPE '\\') ORDER BY sort_order");
    $st->execute([':q' => $like]);
    $projects = $st->fetchAll();
    $st = db()->prepare("SELECT * FROM research WHERE published = 1 AND (title LIKE :q ESCAPE '\\' OR summary LIKE :q ESCAPE '\\' OR venue LIKE :q ESCAPE '\\' OR body LIKE :q ESCAPE '\\') ORDER BY sort_order");
    $st->execute([':q' => $like]);
    $research = $st->fetchAll();
    $st = db()->prepare("SELECT * FROM downloads WHERE title LIKE :q ESCAPE '\\' OR description LIKE :q ESCAPE '\\'");
    $st->execute([':q' => $like]);
    $downloads = $st->fetchAll();
}
$total = count($projects) + count($research) + count($downloads);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <h1>Search</h1>
    <form class="search-inline" action="<?= e(url('search.php')) ?>" method="get" role="search">
      <?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search projects, research, downloads…" aria-label="Search">
      <button class="btn btn-primary btn-sm" type="submit">Search</button>
    </form>
    <?php if ($q !== ''): ?><p class="muted"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for &ldquo;<?= e($q) ?>&rdquo;</p><?php endif; ?>
  </div>
</section>
<section class="section section-top-0">
  <div class="container stack-lg">
    <?php if ($projects): ?><div><?= section_head('Projects') ?><div class="grid grid-4"><?php foreach ($projects as $p) echo project_card($p); ?></div></div><?php endif; ?>
    <?php if ($research): ?><div><?= section_head('Research') ?><div class="grid grid-3"><?php foreach ($research as $r) echo research_card($r); ?></div></div><?php endif; ?>
    <?php if ($downloads): ?><div><?= section_head('Downloads') ?><div class="card pad"><ul class="file-list"><?php foreach ($downloads as $d): ?>
      <li class="file-row"><span class="file-icon <?= file_icon_class($d['file_path']) ?>"><?= e(strtoupper(file_ext($d['file_path']))) ?></span><span class="file-meta"><strong><?= e($d['title']) ?></strong><small><?= e($d['description']) ?></small></span><a class="icon-btn sm" href="<?= e(url('download.php?id=' . (int) $d['id'])) ?>" aria-label="Download"><?= icon('download') ?></a></li>
    <?php endforeach; ?></ul></div></div><?php endif; ?>
    <?php if ($q !== '' && $total === 0): ?><div class="empty-state"><p class="muted">Nothing found. Try a different keyword, like &ldquo;robot&rdquo; or &ldquo;ESP32&rdquo;.</p></div><?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
