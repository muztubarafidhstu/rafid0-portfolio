<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = '';
$active = 'home';

$featured = db()->query('SELECT * FROM projects WHERE published = 1 ORDER BY featured DESC, sort_order, id DESC LIMIT 4')->fetchAll();
$research = db()->query('SELECT * FROM research WHERE published = 1 ORDER BY featured DESC, sort_order, id DESC LIMIT 3')->fetchAll();
$stats = line_parts(setting('stats'), '|', 2);
$expertise = line_parts(setting('expertise'), '|', 3);
$heroImg = media_url(setting('hero_image'));

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-glow" aria-hidden="true"></div>
  <div class="container hero-grid">
    <div class="hero-copy reveal">
      <?php if (setting('hero_badge') !== ''): ?>
        <span class="badge"><?= icon('robot-arm') ?> <?= e(setting('hero_badge')) ?></span>
      <?php endif; ?>
      <h1 class="hero-title">
        <?php foreach (lines(setting('hero_title')) as $line):
            $dot = substr($line, -1) === '.';
            $txt = $dot ? substr($line, 0, -1) : $line; ?>
          <span><?= e($txt) ?><?= $dot ? '<span class="accent-text">.</span>' : '' ?></span>
        <?php endforeach; ?>
      </h1>
      <p class="hero-text"><?= e(setting('hero_text')) ?></p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('projects.php')) ?>">Explore Projects <?= icon('arrow-right') ?></a>
        <a class="btn btn-outline" href="<?= e(cv_url()) ?>"<?= setting('cv_file') !== '' ? ' download' : '' ?>><?= icon('download') ?> Download CV</a>
      </div>
      <div class="social-row">
        <?php foreach (social_links() as $s): ?>
          <a class="icon-btn" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['icon']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($heroImg !== ''): ?>
      <div class="hero-visual reveal">
        <img src="<?= e($heroImg) ?>" alt="<?= e(site_name()) ?> — featured work" fetchpriority="high">
      </div>
    <?php endif; ?>
  </div>

  <?php if ($stats): ?>
    <div class="container">
      <div class="stats-bar reveal">
        <?php foreach ($stats as $s): ?>
          <div class="stat">
            <strong data-count="<?= e($s[0]) ?>"><?= e($s[0]) ?></strong>
            <span><?= e($s[1] ?? '') ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <?= section_head('Featured Projects', 'View All Projects', 'projects.php') ?>
    <div class="grid grid-4">
      <?php foreach ($featured as $p) echo project_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($research): ?>
<section class="section">
  <div class="container">
    <?= section_head('Research & Innovation', 'View All Research', 'research.php') ?>
    <div class="grid grid-3">
      <?php foreach ($research as $r) echo research_card($r); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($expertise): ?>
<section class="section section-tight">
  <div class="container">
    <div class="expertise-bar">
      <?php foreach ($expertise as $x): ?>
        <div class="expertise">
          <span class="expertise-icon"><?= icon($x[2] ?? 'cpu') ?></span>
          <div><h3><?= e($x[0]) ?></h3><p><?= e($x[1] ?? '') ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="cta-card reveal">
      <div>
        <h2>Have a project or collaboration in mind?</h2>
        <p class="muted"><?= e(setting('availability')) ?></p>
      </div>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('contact.php')) ?>"><?= icon('send') ?> Get in touch</a>
        <a class="btn btn-outline" href="<?= e(url('about.php')) ?>">About me</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
