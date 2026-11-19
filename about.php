<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'About';
$active = 'about';
$meta_description = setting('about_short');
$meta_image = setting('about_photo');
$experience = line_parts(setting('experience'), '|', 4);
$education = line_parts(setting('education'), '|', 4);
$skills = line_parts(setting('skills'), ':', 2);
$achievements = line_parts(setting('achievements'), '|', 3);
$stats = line_parts(setting('stats'), '|', 2);

function timeline(array $rows): string
{
    $html = '<ol class="timeline">';
    foreach ($rows as $r) {
        $html .= '<li><span class="tl-date">' . e($r[0]) . '</span><div><h3>' . e($r[1] ?? '') . '</h3>'
            . (!empty($r[2]) ? '<p class="tl-org">' . e($r[2]) . '</p>' : '')
            . (!empty($r[3]) ? '<p class="muted small">' . e($r[3]) . '</p>' : '') . '</div></li>';
    }
    return $html . '</ol>';
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>About</span></nav>
  </div>
</section>
<section class="section section-top-0">
  <div class="container about-grid">
    <div class="about-photo">
      <?php if (setting('about_photo') !== ''): ?><img src="<?= e(media_url(setting('about_photo'))) ?>" alt="<?= e(site_name()) ?>"><?php endif; ?>
    </div>
    <div>
      <span class="badge"><?= icon('user') ?> About me</span>
      <h1><?= e(site_name()) ?></h1>
      <p class="lead accent-text"><?= e(setting('site_title')) ?></p>
      <p class="lead"><?= e(setting('about_short')) ?></p>
      <div class="prose"><?= format_text(setting('about_bio')) ?></div>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(cv_url()) ?>"<?= setting('cv_file') !== '' ? ' download' : '' ?>><?= icon('download') ?> Download CV</a>
        <a class="btn btn-outline" href="<?= e(url('contact.php')) ?>"><?= icon('send') ?> Contact me</a>
      </div>
    </div>
  </div>
</section>

<?php if ($stats): ?>
<section class="section section-tight">
  <div class="container"><div class="stats-bar"><?php foreach ($stats as $s): ?><div class="stat"><strong><?= e($s[0]) ?></strong><span><?= e($s[1] ?? '') ?></span></div><?php endforeach; ?></div></div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container grid grid-2 align-start">
    <?php if ($experience): ?><div class="card pad"><h2 class="card-heading"><?= icon('briefcase') ?> Experience</h2><?= timeline($experience) ?></div><?php endif; ?>
    <div class="stack">
      <?php if ($education): ?><div class="card pad"><h2 class="card-heading"><?= icon('graduation') ?> Education</h2><?= timeline($education) ?></div><?php endif; ?>
      <?php if ($achievements): ?>
        <div class="card pad"><h2 class="card-heading"><?= icon('trophy') ?> Awards &amp; Achievements</h2>
          <ul class="award-list"><?php foreach ($achievements as $a): ?><li><span class="tl-date"><?= e($a[0]) ?></span><div><strong><?= e($a[1] ?? '') ?></strong><?php if (!empty($a[2])): ?><p class="muted small"><?= e($a[2]) ?></p><?php endif; ?></div></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($skills): ?>
<section class="section section-top-0">
  <div class="container">
    <?= section_head('Skills & Tools') ?>
    <div class="grid grid-3">
      <?php foreach ($skills as $s): ?>
        <div class="card pad"><h3 class="card-heading sub"><?= e($s[0]) ?></h3><div class="chips"><?php foreach (tags($s[1] ?? '') as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach; ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
