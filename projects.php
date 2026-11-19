<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Projects';
$active = 'projects';
$meta_description = 'Robotics, automation, embedded and IoT projects by ' . site_name() . '.';

$all = db()->query('SELECT * FROM projects WHERE published = 1 ORDER BY sort_order, id DESC')->fetchAll();
$cats = [];
foreach ($all as $p) {
    foreach (tags($p['category']) as $c) {
        $cats[$c] = ($cats[$c] ?? 0) + 1;
    }
}
ksort($cats);
$filter = trim((string) ($_GET['category'] ?? ''));

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>Projects</span></nav>
    <h1>Projects</h1>
    <p class="lead muted">A selection of robotics, automation, embedded and IoT systems I have designed and built.</p>
  </div>
</section>

<section class="section section-top-0">
  <div class="container">
    <?php if ($cats): ?>
      <div class="filter-row" role="group" aria-label="Filter by category">
        <button type="button" class="filter-chip<?= $filter === '' ? ' active' : '' ?>" data-filter="*">All <span><?= count($all) ?></span></button>
        <?php foreach ($cats as $c => $n): ?>
          <button type="button" class="filter-chip<?= $filter === $c ? ' active' : '' ?>" data-filter="<?= e($c) ?>"><?= e($c) ?> <span><?= $n ?></span></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$all): ?>
      <div class="empty-state"><p class="muted">No projects published yet.</p></div>
    <?php else: ?>
      <div class="grid grid-3" data-filter-grid>
        <?php foreach ($all as $p): ?>
          <div class="filter-item" data-cats="<?= e(implode('|', tags($p['category']))) ?>">
            <?= project_card($p) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
