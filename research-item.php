<?php
require __DIR__ . '/includes/bootstrap.php';

$st = db()->prepare('SELECT * FROM research WHERE slug = ? AND (published = 1 OR ? = 1)');
$st->execute([(string) ($_GET['slug'] ?? ''), is_admin() ? 1 : 0]);
$r = $st->fetch();
if (!$r) {
    not_found();
}
$page_title = $r['title'];
$active = 'research';
$meta_description = $r['summary'];
$meta_image = $r['image'];
$others = db()->prepare('SELECT * FROM research WHERE published = 1 AND id != ? ORDER BY sort_order LIMIT 3');
$others->execute([$r['id']]);
$others = $others->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="section project-top">
  <div class="container narrow">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><a href="<?= e(url('research.php')) ?>">Research</a><?= icon('chevron-right') ?><span><?= e($r['title']) ?></span>
    </nav>
    <span class="pill pill-<?= status_class($r['status']) ?>"><?= e($r['status']) ?></span>
    <h1 class="article-title"><?= e($r['title']) ?></h1>
    <p class="muted"><?= e(trim($r['venue'] . ' ' . $r['year'])) ?><?= $r['authors'] !== '' ? ' · ' . e($r['authors']) : '' ?><?= $r['doi'] !== '' ? ' · DOI: ' . e($r['doi']) : '' ?></p>
    <div class="btn-row">
      <?php if ($r['pdf_file'] !== ''): ?><a class="btn btn-primary" href="<?= e(media_url($r['pdf_file'])) ?>" target="_blank" rel="noopener"><?= icon('file-text') ?> Read PDF</a><?php endif; ?>
      <?php if ($r['link_url'] !== ''): ?><a class="btn btn-outline" href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View publication</a><?php endif; ?>
    </div>
    <?php if ($r['image'] !== ''): ?>
      <a class="article-image" href="<?= e(media_url($r['image'])) ?>" data-lb><img src="<?= e(media_url($r['image'])) ?>" alt="<?= e($r['title']) ?>"></a>
    <?php endif; ?>
    <?php if ($r['summary'] !== '' && $r['summary'] !== $r['body']): ?>
      <div class="card pad abstract"><h2 class="card-heading">Abstract</h2><div class="prose"><?= format_text($r['summary']) ?></div></div>
    <?php endif; ?>
    <div class="prose article-body"><?= format_text($r['body'] !== '' ? $r['body'] : $r['summary']) ?></div>
  </div>
</section>
<?php if ($others): ?>
<section class="section section-top-0">
  <div class="container">
    <?= section_head('More Research', 'View All Research', 'research.php') ?>
    <div class="grid grid-3"><?php foreach ($others as $o) echo research_card($o); ?></div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
