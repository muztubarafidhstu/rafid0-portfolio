<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Curriculum Vitae';
$active = 'cv';
$cv = setting('cv_file');
$isPdf = $cv !== '' && file_ext($cv) === 'pdf';
$experience = line_parts(setting('experience'), '|', 4);
$education = line_parts(setting('education'), '|', 4);
$skills = line_parts(setting('skills'), ':', 2);
$achievements = line_parts(setting('achievements'), '|', 3);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>CV</span></nav>
    <div class="page-hero-row">
      <div>
        <h1>Curriculum Vitae</h1>
        <p class="lead muted"><?= e(site_name()) ?> · <?= e(setting('site_title')) ?><?= setting('cv_updated') !== '' ? ' · Updated ' . e(format_date(setting('cv_updated'), 'F Y')) : '' ?></p>
      </div>
      <?php if ($cv !== ''): ?>
        <div class="btn-row">
          <a class="btn btn-primary" href="<?= e(media_url($cv)) ?>" download><?= icon('download') ?> Download CV</a>
          <a class="btn btn-outline" href="<?= e(media_url($cv)) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Open in new tab</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<section class="section section-top-0">
  <div class="container">
    <?php if ($isPdf): ?>
      <div class="card cv-frame"><iframe src="<?= e(media_url($cv)) ?>#view=FitH" title="CV of <?= e(site_name()) ?>" loading="lazy"></iframe></div>
    <?php endif; ?>

    <div class="cv-summary card pad">
      <h2 class="card-heading">At a glance</h2>
      <p><?= e(setting('about_short')) ?></p>
      <div class="cv-cols">
        <?php if ($experience): ?><div><h3>Experience</h3><ul class="plain-list"><?php foreach ($experience as $x): ?><li><strong><?= e($x[1] ?? '') ?></strong><?= !empty($x[2]) ? ' — ' . e($x[2]) : '' ?><br><small class="muted"><?= e($x[0]) ?></small></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($education): ?><div><h3>Education</h3><ul class="plain-list"><?php foreach ($education as $x): ?><li><strong><?= e($x[1] ?? '') ?></strong><?= !empty($x[2]) ? ' — ' . e($x[2]) : '' ?><br><small class="muted"><?= e($x[0]) ?></small></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($skills): ?><div><h3>Skills</h3><ul class="plain-list"><?php foreach ($skills as $s): ?><li><strong><?= e($s[0]) ?>:</strong> <?= e($s[1] ?? '') ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($achievements): ?><div><h3>Achievements</h3><ul class="plain-list"><?php foreach ($achievements as $a): ?><li><strong><?= e($a[1] ?? '') ?></strong> <small class="muted">(<?= e($a[0]) ?>)</small></li><?php endforeach; ?></ul></div><?php endif; ?>
      </div>
      <?php if ($cv === ''): ?><p class="muted small mt-1">A downloadable PDF CV will be available here soon.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
