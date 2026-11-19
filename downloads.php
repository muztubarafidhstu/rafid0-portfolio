<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Downloads';
$active = 'downloads';
$meta_description = 'Documentation, datasets, source code and other resources to download.';

$downloads = db()->query('SELECT * FROM downloads ORDER BY sort_order, id DESC')->fetchAll();
$groups = [];
foreach ($downloads as $d) {
    $groups[$d['category'] !== '' ? $d['category'] : 'General'][] = $d;
}
$projectFiles = db()->query("SELECT m.*, p.title AS ptitle, p.slug FROM project_media m JOIN projects p ON p.id = m.project_id
    WHERE p.published = 1 AND m.type = 'document' ORDER BY p.sort_order, m.sort_order")->fetchAll();
$byProject = [];
foreach ($projectFiles as $f) {
    $byProject[$f['ptitle']]['slug'] = $f['slug'];
    $byProject[$f['ptitle']]['files'][] = $f;
}
if (setting('cv_file') !== '') {
    $cvPath = setting('cv_file');
    $cvSize = is_file(ROOT . '/' . $cvPath) ? (int) filesize(ROOT . '/' . $cvPath) : 0;
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>Downloads</span></nav>
    <h1>Downloads</h1>
    <p class="lead muted">Documentation, source code, datasets and other resources.</p>
  </div>
</section>
<section class="section section-top-0">
  <div class="container">
    <?php if (!$downloads && !$byProject && empty($cvPath)): ?>
      <div class="empty-state"><p class="muted">No downloads available yet.</p></div>
    <?php endif; ?>
    <div class="grid grid-2">
      <?php if (!empty($cvPath)): ?>
        <div class="card pad">
          <h2 class="card-heading">Curriculum Vitae</h2>
          <ul class="file-list">
            <li class="file-row"><span class="file-icon pdf"><?= e(file_badge($cvPath)) ?></span>
              <span class="file-meta"><strong><?= e(site_name()) ?> — CV</strong><small><?= e(human_size($cvSize)) ?></small></span>
              <a class="icon-btn sm" href="<?= e(media_url($cvPath)) ?>" download aria-label="Download CV"><?= icon('download') ?></a></li>
          </ul>
        </div>
      <?php endif; ?>
      <?php foreach ($groups as $cat => $list): ?>
        <div class="card pad">
          <h2 class="card-heading"><?= e($cat) ?></h2>
          <ul class="file-list">
            <?php foreach ($list as $d): ?>
              <li class="file-row">
                <span class="file-icon <?= file_icon_class($d['file_path']) ?>"><?= e(file_badge($d['file_path'])) ?></span>
                <span class="file-meta"><strong><?= e($d['title']) ?></strong>
                  <small><?= e(trim(human_size((int) $d['file_size']) . ($d['description'] !== '' ? ' · ' . $d['description'] : ''))) ?></small></span>
                <a class="icon-btn sm" href="<?= e(url('download.php?id=' . (int) $d['id'])) ?>" aria-label="Download <?= e($d['title']) ?>"><?= icon('download') ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <?php foreach ($byProject as $ptitle => $info): ?>
        <div class="card pad">
          <h2 class="card-heading"><a href="<?= e(url('project.php?slug=' . rawurlencode($info['slug']))) ?>"><?= e($ptitle) ?></a></h2>
          <ul class="file-list">
            <?php foreach ($info['files'] as $f):
                $name = $f['title'] !== '' ? $f['title'] : basename($f['file_path']); ?>
              <li class="file-row">
                <span class="file-icon <?= file_icon_class($f['file_path']) ?>"><?= e(file_badge($f['file_path'])) ?></span>
                <span class="file-meta"><strong><?= e($name) ?></strong><small><?= e(human_size((int) $f['file_size'])) ?></small></span>
                <a class="icon-btn sm" href="<?= e(media_url($f['file_path'])) ?>"<?= download_attrs($f['file_path']) ?> aria-label="Download <?= e($name) ?>"><?= icon('download') ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
