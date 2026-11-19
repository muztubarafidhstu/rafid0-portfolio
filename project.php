<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$st = db()->prepare('SELECT * FROM projects WHERE slug = ? AND (published = 1 OR ? = 1)');
$st->execute([$slug, is_admin() ? 1 : 0]);
$p = $st->fetch();
if (!$p) {
    not_found();
}
db()->prepare('UPDATE projects SET views = views + 1 WHERE id = ?')->execute([$p['id']]);

$images = project_media((int) $p['id'], 'image');
$videos = project_media((int) $p['id'], 'video');
$docsAll = project_media((int) $p['id'], 'document');
$docs = [];
$files = [];
foreach ($docsAll as $d) {
    if (in_array(file_ext($d['file_path']), ['pdf', 'doc', 'docx', 'txt', 'md', 'ppt', 'pptx', 'xls', 'xlsx', 'csv'], true)) {
        $docs[] = $d;
    } else {
        $files[] = $d;
    }
}
$gallery = $images;
if ($p['cover_image'] !== '') {
    array_unshift($gallery, ['file_path' => $p['cover_image'], 'title' => $p['title']]);
}
$specs = line_parts($p['specs'], ':', 2);
$results = line_parts($p['results'], '|', 2);
$features = lines($p['features']);
$tech = lines($p['technologies']);
$chart = line_chart_svg(explode(',', (string) $p['chart_data']), $p['chart_label']);
$hasVideos = $p['video_url'] !== '' || $videos;

$rel = db()->prepare('SELECT * FROM projects WHERE published = 1 AND id != ? ORDER BY (category = ?) DESC, featured DESC, sort_order LIMIT 4');
$rel->execute([$p['id'], $p['category']]);
$related = $rel->fetchAll();

$page_title = $p['title'];
$active = 'projects';
$meta_description = $p['summary'] !== '' ? $p['summary'] : $p['overview'];
$meta_image = $p['cover_image'];

function doc_row(array $d, bool $download = true): string
{
    $size = human_size((int) $d['file_size']);
    if ($size === '' && strpos($d['file_path'], 'uploads/') === 0 && is_file(ROOT . '/' . $d['file_path'])) {
        $size = human_size((int) filesize(ROOT . '/' . $d['file_path']));
    }
    $name = $d['title'] !== '' ? $d['title'] : basename($d['file_path']);
    return '<li class="file-row"><span class="file-icon ' . file_icon_class($d['file_path']) . '">' . e(strtoupper(file_ext($d['file_path']))) . '</span>'
        . '<span class="file-meta"><strong>' . e($name) . '</strong>' . ($size !== '' ? '<small>' . e($size) . '</small>' : '') . '</span>'
        . '<a class="icon-btn sm" href="' . e(media_url($d['file_path'])) . '"' . ($download ? ' download' : ' target="_blank" rel="noopener"') . ' aria-label="Download ' . e($name) . '">' . icon('download') . '</a></li>';
}

require __DIR__ . '/includes/header.php';
?>
<section class="section project-top">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><a href="<?= e(url('projects.php')) ?>">Projects</a><?= icon('chevron-right') ?><span><?= e($p['title']) ?></span>
    </nav>
    <?php if (!$p['published']): ?><div class="alert alert-warning">This project is a draft and only visible to you.</div><?php endif; ?>

    <div class="project-hero">
      <div class="project-cover" data-gallery="main">
        <?php if ($gallery): ?>
          <a href="<?= e(media_url($gallery[0]['file_path'])) ?>" data-lb data-caption="<?= e($gallery[0]['title'] ?? '') ?>">
            <img src="<?= e(media_url($gallery[0]['file_path'])) ?>" alt="<?= e($p['title']) ?>">
          </a>
          <?php if (count($gallery) > 1): ?>
            <span class="cover-count">1 / <?= count($gallery) ?> <?= icon('arrow-right') ?></span>
            <?php foreach (array_slice($gallery, 1) as $g): ?>
              <a href="<?= e(media_url($g['file_path'])) ?>" data-lb data-caption="<?= e($g['title'] ?? '') ?>" hidden></a>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php else: ?>
          <span class="media-fallback big"><?= icon('box') ?></span>
        <?php endif; ?>
      </div>
      <div class="project-info">
        <h1><?= e($p['title']) ?></h1>
        <?php if ($p['subtitle'] !== ''): ?><p class="lead muted"><?= e($p['subtitle']) ?></p><?php endif; ?>
        <?php if (tags($p['tags'])): ?>
          <div class="chips chips-lg"><?php foreach (tags($p['tags']) as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach; ?></div>
        <?php endif; ?>
        <dl class="meta-list">
          <?php if ($p['category'] !== ''): ?><div><dt><?= icon('folder') ?> Category</dt><dd><?= e($p['category']) ?></dd></div><?php endif; ?>
          <?php if ($p['completed_date'] !== ''): ?><div><dt><?= icon('calendar') ?> <?= $p['status'] === 'Completed' ? 'Completed' : 'Date' ?></dt><dd><?= e(format_date($p['completed_date'], 'F Y')) ?></dd></div><?php endif; ?>
          <?php if ($p['team_size'] !== ''): ?><div><dt><?= icon('users') ?> Team Size</dt><dd><?= e($p['team_size']) ?></dd></div><?php endif; ?>
          <?php if ($p['duration'] !== ''): ?><div><dt><?= icon('clock') ?> Project Duration</dt><dd><?= e($p['duration']) ?></dd></div><?php endif; ?>
          <?php if ($p['status'] !== ''): ?><div><dt><?= icon('flag') ?> Status</dt><dd><span class="pill pill-<?= status_class($p['status']) ?>"><?= e($p['status']) ?></span></dd></div><?php endif; ?>
        </dl>
        <div class="btn-row">
          <?php if ($p['demo_url'] !== ''): ?><a class="btn btn-primary" href="<?= e($p['demo_url']) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Live Demo</a><?php endif; ?>
          <?php if ($hasVideos): ?><a class="btn btn-outline" href="#videos" data-tab-link="videos"><?= icon('play') ?> Watch Video</a><?php endif; ?>
          <?php if ($docs): ?><a class="btn btn-outline" href="#documents" data-tab-link="documents"><?= icon('file-text') ?> Documentation</a><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="tabs" role="tablist">
      <button role="tab" class="tab active" data-tab="overview" aria-selected="true">Overview</button>
      <?php if ($gallery): ?><button role="tab" class="tab" data-tab="gallery" aria-selected="false">Gallery</button><?php endif; ?>
      <?php if ($hasVideos): ?><button role="tab" class="tab" data-tab="videos" aria-selected="false">Videos</button><?php endif; ?>
      <?php if ($docs || $files): ?><button role="tab" class="tab" data-tab="documents" aria-selected="false">Documentation</button><?php endif; ?>
      <?php if ($p['details'] !== ''): ?><button role="tab" class="tab" data-tab="details" aria-selected="false">Details</button><?php endif; ?>
    </div>

    <!-- OVERVIEW -->
    <div class="tab-panel active" id="overview" data-panel="overview" role="tabpanel">
      <div class="detail-grid">
        <div class="card pad">
          <h2 class="card-heading">Overview</h2>
          <div class="prose small"><?= format_text($p['overview']) ?></div>
          <?php if ($features): ?>
            <h3 class="card-heading sub">Key Features</h3>
            <ul class="check-list"><?php foreach ($features as $f): ?><li><?= icon('check-circle') ?><?= e($f) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>
        </div>
        <?php if ($p['diagram_image'] !== ''): ?>
          <div class="card pad span-2-md">
            <h2 class="card-heading">System Block Diagram</h2>
            <a class="diagram" href="<?= e(media_url($p['diagram_image'])) ?>" data-lb data-caption="System block diagram"><img src="<?= e(media_url($p['diagram_image'])) ?>" alt="System block diagram of <?= e($p['title']) ?>" loading="lazy"></a>
          </div>
        <?php endif; ?>
        <?php if ($tech): ?>
          <div class="card pad">
            <h2 class="card-heading">Technologies Used</h2>
            <ul class="dot-list"><?php foreach ($tech as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($images || $docs): ?>
      <div class="detail-grid two">
        <?php if ($images): ?>
          <div class="card pad">
            <h2 class="card-heading">Gallery Preview</h2>
            <div class="thumb-row">
              <?php foreach (array_slice($images, 0, 3) as $img): ?>
                <a href="#gallery" data-tab-link="gallery" class="thumb"><img src="<?= e(media_url($img['file_path'])) ?>" alt="<?= e($img['title']) ?>" loading="lazy"></a>
              <?php endforeach; ?>
              <?php if (count($images) > 3): ?>
                <a href="#gallery" data-tab-link="gallery" class="thumb more"><img src="<?= e(media_url($images[3]['file_path'])) ?>" alt="" loading="lazy"><span>+<?= count($images) - 3 ?><small>More</small></span></a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($docs): ?>
          <div class="card pad">
            <h2 class="card-heading">Documents</h2>
            <ul class="file-list"><?php foreach (array_slice($docs, 0, 4) as $d) echo doc_row($d); ?></ul>
            <?php if (count($docs) > 4): ?><a class="link-accent small right" href="#documents" data-tab-link="documents">View All Documents <?= icon('arrow-right') ?></a><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($specs || $results || $chart || $files): ?>
      <div class="detail-grid auto">
        <?php if ($specs): ?>
          <div class="card pad">
            <h2 class="card-heading">Specifications</h2>
            <dl class="spec-list"><?php foreach ($specs as $s): ?><div><dt><?= e($s[0]) ?></dt><dd><?= e($s[1] ?? '') ?></dd></div><?php endforeach; ?></dl>
          </div>
        <?php endif; ?>
        <?php if ($results || $chart): ?>
          <div class="card pad">
            <h2 class="card-heading">Results</h2>
            <?php if ($results): ?>
              <div class="result-boxes"><?php foreach ($results as $r): ?><div><strong><?= e($r[0]) ?></strong><span><?= e($r[1] ?? '') ?></span></div><?php endforeach; ?></div>
            <?php endif; ?>
            <?= $chart ?>
          </div>
        <?php endif; ?>
        <?php if ($files): ?>
          <div class="card pad">
            <h2 class="card-heading">Downloads</h2>
            <ul class="file-list"><?php foreach ($files as $d) echo doc_row($d); ?></ul>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- GALLERY -->
    <?php if ($gallery): ?>
    <div class="tab-panel" id="gallery" data-panel="gallery" role="tabpanel">
      <div class="masonry">
        <?php foreach ($gallery as $g): ?>
          <a href="<?= e(media_url($g['file_path'])) ?>" data-lb data-caption="<?= e($g['title'] ?? '') ?>" class="masonry-item">
            <img src="<?= e(media_url($g['file_path'])) ?>" alt="<?= e($g['title'] ?? '') ?>" loading="lazy">
            <?php if (!empty($g['title'])): ?><span class="caption"><?= e($g['title']) ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- VIDEOS -->
    <?php if ($hasVideos): ?>
    <div class="tab-panel" id="videos" data-panel="videos" role="tabpanel">
      <div class="grid grid-2">
        <?php if ($p['video_url'] !== ''): ?><div class="card pad"><?= video_embed_html($p['video_url'], $p['title']) ?></div><?php endif; ?>
        <?php foreach ($videos as $v): ?>
          <div class="card pad">
            <?= video_embed_html($v['file_path'], $v['title']) ?>
            <?php if ($v['title'] !== ''): ?><p class="small muted mt-1"><?= e($v['title']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- DOCUMENTS -->
    <?php if ($docs || $files): ?>
    <div class="tab-panel" id="documents" data-panel="documents" role="tabpanel">
      <div class="grid grid-2">
        <?php if ($docs): ?><div class="card pad"><h2 class="card-heading">Documents</h2><ul class="file-list"><?php foreach ($docs as $d) echo doc_row($d); ?></ul></div><?php endif; ?>
        <?php if ($files): ?><div class="card pad"><h2 class="card-heading">Source files &amp; downloads</h2><ul class="file-list"><?php foreach ($files as $d) echo doc_row($d); ?></ul></div><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- DETAILS -->
    <?php if ($p['details'] !== ''): ?>
    <div class="tab-panel" id="details" data-panel="details" role="tabpanel">
      <div class="card pad prose narrow-prose"><?= format_text($p['details']) ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-top-0">
  <div class="container">
    <?= section_head('Related Projects', 'All Projects', 'projects.php') ?>
    <div class="related-row">
      <?php foreach ($related as $r): ?>
        <a class="card related" href="<?= e(url('project.php?slug=' . rawurlencode($r['slug']))) ?>">
          <?php if ($r['cover_image'] !== ''): ?><img src="<?= e(media_url($r['cover_image'])) ?>" alt="" loading="lazy"><?php endif; ?>
          <span><strong><?= e($r['title']) ?></strong><small><?= e($r['category']) ?></small></span>
          <?= icon('arrow-right') ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
