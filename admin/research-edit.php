<?php
require __DIR__ . '/_layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$fields = ['title', 'slug', 'status', 'venue', 'year', 'authors', 'summary', 'body', 'link_url', 'doi'];
$r = array_fill_keys($fields, '') + ['id' => 0, 'image' => '', 'pdf_file' => '', 'featured' => 1, 'published' => 1, 'sort_order' => 0];
$r['status'] = 'Published';
if ($id) {
    $st = db()->prepare('SELECT * FROM research WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) {
        flash('error', 'Item not found.');
        redirect('admin/research.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $in = [];
    foreach ($fields as $f) {
        $in[$f] = trim((string) ($_POST[$f] ?? ''));
    }
    $in['featured'] = isset($_POST['featured']) ? 1 : 0;
    $in['published'] = isset($_POST['published']) ? 1 : 0;
    $in['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    if ($in['link_url'] !== '' && !preg_match('~^https?://~i', $in['link_url'])) {
        $in['link_url'] = 'https://' . $in['link_url'];
    }
    if ($in['title'] === '') {
        flash('error', 'Please enter a title.');
        $r = array_merge($r, $in);
    } else {
        $in['slug'] = unique_slug('research', slugify($in['slug'] !== '' ? $in['slug'] : $in['title']), $id);
        $in['image'] = $r['image'];
        $in['pdf_file'] = $r['pdf_file'];
        if (!empty($_POST['remove_image'])) { delete_upload($r['image']); $in['image'] = ''; }
        if (!empty($_POST['remove_pdf'])) { delete_upload($r['pdf_file']); $in['pdf_file'] = ''; }
        try {
            foreach (files_list('image_file') as $f) {
                $up = store_upload($f, ['image']);
                delete_upload($r['image']);
                $in['image'] = $up['path'];
            }
            foreach (files_list('pdf') as $f) {
                $up = store_upload($f, ['document']);
                delete_upload($r['pdf_file']);
                $in['pdf_file'] = $up['path'];
            }
        } catch (RuntimeException $ex) {
            flash('warning', $ex->getMessage());
        }
        if ($id) {
            $sets = implode(', ', array_map(function ($c) { return "$c = :$c"; }, array_keys($in)));
            db()->prepare("UPDATE research SET $sets, updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute($in + ['id' => $id]);
        } else {
            $cols = array_keys($in);
            db()->prepare('INSERT INTO research(' . implode(',', $cols) . ') VALUES(:' . implode(', :', $cols) . ')')->execute($in);
            $id = (int) db()->lastInsertId();
        }
        flash('success', 'Research saved. Your website is updated.');
        redirect('admin/research-edit.php?id=' . $id);
    }
}

admin_header($id ? 'Edit Research' : 'Add Research', 'research');
?>
<nav class="breadcrumb"><a href="<?= e(url('admin/index.php')) ?>">Dashboard</a><?= icon('chevron-right') ?><a href="<?= e(url('admin/research.php')) ?>">Research</a><?= icon('chevron-right') ?><span><?= $id ? e($r['title']) : 'Add New' ?></span></nav>
<form method="post" enctype="multipart/form-data" class="edit-layout" data-upload-form>
  <?= csrf_field() ?>
  <div class="stack">
    <section class="card pad">
      <h2 class="card-heading">Research details</h2>
      <div class="form">
        <label class="field">Title *<input type="text" name="title" value="<?= e($r['title']) ?>" required></label>
        <div class="form-row two">
          <label class="field">Type / status
            <input type="text" name="status" value="<?= e($r['status']) ?>" list="status-list">
            <datalist id="status-list"><option value="Published"><option value="Patent Filed"><option value="Patent Granted"><option value="In Progress"><option value="Under Review"><option value="Conference Paper"></datalist>
          </label>
          <label class="field">Year<input type="text" name="year" value="<?= e($r['year']) ?>" placeholder="2025"></label>
        </div>
        <div class="form-row two">
          <label class="field">Journal / venue / patent office<input type="text" name="venue" value="<?= e($r['venue']) ?>" placeholder="IEEE Access"></label>
          <label class="field">Authors<input type="text" name="authors" value="<?= e($r['authors']) ?>" placeholder="A. Author, B. Author"></label>
        </div>
        <label class="field">Abstract / summary<textarea name="summary" rows="4"><?= e($r['summary']) ?></textarea></label>
        <label class="field">Full description <small class="hint">Blank line = new paragraph, **bold**</small><textarea name="body" rows="10"><?= e($r['body']) ?></textarea></label>
        <div class="form-row two">
          <label class="field">Publisher / paper link<input type="url" name="link_url" value="<?= e($r['link_url']) ?>" placeholder="https://ieeexplore.ieee.org/..."></label>
          <label class="field">DOI<input type="text" name="doi" value="<?= e($r['doi']) ?>" placeholder="10.1109/ACCESS.2025.xxxxx"></label>
        </div>
      </div>
    </section>
  </div>
  <aside class="stack sticky-side">
    <section class="card pad">
      <h2 class="card-heading">Publish</h2>
      <div class="form">
        <label class="switch"><input type="checkbox" name="published" value="1"<?= checked($r['published']) ?>><span></span> Published</label>
        <label class="switch"><input type="checkbox" name="featured" value="1"<?= checked($r['featured']) ?>><span></span> Featured on home page</label>
        <label class="field">Display order<input type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>"></label>
        <div class="upload-progress" data-progress hidden><div><span></span></div><small>Uploading… <b>0%</b></small></div>
        <div class="btn-row m0"><a class="btn btn-outline" href="<?= e(url('admin/research.php')) ?>">Cancel</a><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
      </div>
    </section>
    <section class="card pad">
      <h2 class="card-heading">Image</h2>
      <?php if ($r['image']): ?><img class="side-preview" src="<?= e(media_url($r['image'])) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_image" value="1"> Remove</label><?php endif; ?>
      <input type="file" name="image_file" accept="image/*" class="file-input">
    </section>
    <section class="card pad">
      <h2 class="card-heading">Paper PDF</h2>
      <?php if ($r['pdf_file']): ?><p class="small"><a href="<?= e(media_url($r['pdf_file'])) ?>" target="_blank"><?= icon('file-text') ?> <?= e(basename($r['pdf_file'])) ?></a></p><label class="small muted"><input type="checkbox" name="remove_pdf" value="1"> Remove</label><?php endif; ?>
      <input type="file" name="pdf" accept=".pdf,.doc,.docx" class="file-input">
    </section>
    <section class="card pad">
      <label class="field">URL slug <small class="hint">Leave blank to generate</small><input type="text" name="slug" value="<?= e($r['slug']) ?>"></label>
    </section>
  </aside>
</form>
<?php admin_footer(); ?>
