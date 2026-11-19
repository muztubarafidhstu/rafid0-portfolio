<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Research & Innovation';
$active = 'research';
$meta_description = 'Publications, patents and ongoing research by ' . site_name() . '.';
$items = db()->query('SELECT * FROM research WHERE published = 1 ORDER BY sort_order, year DESC, id DESC')->fetchAll();
$statuses = array_values(array_unique(array_map(function ($r) { return $r['status']; }, $items)));

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>Research</span></nav>
    <h1>Research &amp; Innovation</h1>
    <p class="lead muted">Publications, patents and work in progress.</p>
  </div>
</section>
<section class="section section-top-0">
  <div class="container">
    <?php if (count($statuses) > 1): ?>
      <div class="filter-row" role="group" aria-label="Filter by status">
        <button type="button" class="filter-chip active" data-filter="*">All <span><?= count($items) ?></span></button>
        <?php foreach ($statuses as $s): ?><button type="button" class="filter-chip" data-filter="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$items): ?>
      <div class="empty-state"><p class="muted">No research published yet.</p></div>
    <?php else: ?>
      <div class="research-list" data-filter-grid>
        <?php foreach ($items as $r):
            $href = url('research-item.php?slug=' . rawurlencode($r['slug'])); ?>
          <article class="card research-row filter-item" data-cats="<?= e($r['status']) ?>">
            <?php if ($r['image'] !== ''): ?><a href="<?= e($href) ?>" class="research-thumb" tabindex="-1"><img src="<?= e(media_url($r['image'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
            <div class="research-row-body">
              <span class="pill pill-<?= status_class($r['status']) ?>"><?= e($r['status']) ?></span>
              <h2><a href="<?= e($href) ?>"><?= e($r['title']) ?></a></h2>
              <p class="small muted"><?= e(trim($r['venue'] . ' ' . $r['year'])) ?><?= $r['authors'] !== '' ? ' · ' . e($r['authors']) : '' ?></p>
              <p><?= e(excerpt($r['summary'], 220)) ?></p>
              <div class="btn-row">
                <a class="btn btn-sm btn-outline" href="<?= e($href) ?>">Read more <?= icon('arrow-right') ?></a>
                <?php if ($r['pdf_file'] !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(media_url($r['pdf_file'])) ?>" target="_blank" rel="noopener"><?= icon('file-text') ?> PDF</a><?php endif; ?>
                <?php if ($r['link_url'] !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Publisher</a><?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
