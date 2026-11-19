<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Gallery';
$active = 'gallery';
$meta_description = 'Photos and videos from projects, lab work and field testing.';

// Combine the main gallery with every image uploaded to a published project.
$items = [];
foreach (db()->query('SELECT * FROM gallery ORDER BY sort_order, id DESC') as $g) {
    $items[] = ['src' => $g['file_path'], 'title' => $g['title'], 'cat' => $g['category'] !== '' ? $g['category'] : 'General', 'type' => $g['type'], 'link' => ''];
}
$sql = 'SELECT m.file_path, m.title, m.type, p.title AS ptitle, p.slug FROM project_media m JOIN projects p ON p.id = m.project_id
        WHERE p.published = 1 AND m.type IN (\'image\', \'video\') ORDER BY p.sort_order, m.sort_order';
foreach (db()->query($sql) as $m) {
    $items[] = ['src' => $m['file_path'], 'title' => $m['title'] !== '' ? $m['title'] : $m['ptitle'], 'cat' => 'Projects', 'type' => $m['type'],
        'link' => url('project.php?slug=' . rawurlencode($m['slug'])), 'project' => $m['ptitle']];
}
$cats = array_values(array_unique(array_map(function ($i) { return $i['cat']; }, $items)));

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>Gallery</span></nav>
    <h1>Gallery</h1>
    <p class="lead muted">Behind the scenes: prototypes, lab work, field tests and finished systems.</p>
  </div>
</section>
<section class="section section-top-0">
  <div class="container">
    <?php if (count($cats) > 1): ?>
      <div class="filter-row" role="group" aria-label="Filter by category">
        <button type="button" class="filter-chip active" data-filter="*">All <span><?= count($items) ?></span></button>
        <?php foreach ($cats as $c): ?><button type="button" class="filter-chip" data-filter="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$items): ?>
      <div class="empty-state"><p class="muted">No photos yet.</p></div>
    <?php else: ?>
      <div class="masonry" data-filter-grid>
        <?php foreach ($items as $i): ?>
          <?php if ($i['type'] === 'video'): ?>
            <div class="masonry-item filter-item" data-cats="<?= e($i['cat']) ?>">
              <?= video_embed_html($i['src'], $i['title']) ?>
              <?php if ($i['title'] !== ''): ?><span class="caption static"><?= e($i['title']) ?></span><?php endif; ?>
            </div>
          <?php else: ?>
            <a class="masonry-item filter-item" data-cats="<?= e($i['cat']) ?>" href="<?= e(media_url($i['src'])) ?>" data-lb data-caption="<?= e($i['title'] . (!empty($i['project']) && $i['project'] !== $i['title'] ? ' — ' . $i['project'] : '')) ?>">
              <img src="<?= e(media_url($i['src'])) ?>" alt="<?= e($i['title']) ?>" loading="lazy">
              <?php if ($i['title'] !== ''): ?><span class="caption"><?= e($i['title']) ?></span><?php endif; ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
