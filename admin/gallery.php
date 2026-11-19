<?php
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete'])) {
        $st = db()->prepare('SELECT * FROM gallery WHERE id = ?');
        $st->execute([(int) $_POST['delete']]);
        if ($g = $st->fetch()) {
            delete_upload($g['file_path']);
            db()->prepare('DELETE FROM gallery WHERE id = ?')->execute([$g['id']]);
            flash('success', 'Removed from gallery.');
        }
    } elseif (isset($_POST['save'])) {
        $st = db()->prepare('UPDATE gallery SET title = ?, category = ?, sort_order = ? WHERE id = ?');
        foreach ((array) ($_POST['title'] ?? []) as $gid => $t) {
            $st->execute([trim((string) $t), trim((string) ($_POST['category'][$gid] ?? '')), (int) ($_POST['order'][$gid] ?? 0), (int) $gid]);
        }
        flash('success', 'Gallery updated.');
    } else {
        $cat = trim((string) ($_POST['new_category'] ?? ''));
        $n = 0;
        foreach (files_list('files') as $f) {
            try {
                $up = store_upload($f, ['image', 'video']);
                $title = ucfirst(trim(preg_replace('/[_\-]+/', ' ', pathinfo($f['name'], PATHINFO_FILENAME))));
                db()->prepare('INSERT INTO gallery(title, category, file_path, type) VALUES(?,?,?,?)')->execute([$title, $cat, $up['path'], $up['kind']]);
                $n++;
            } catch (RuntimeException $ex) {
                flash('warning', $ex->getMessage());
            }
        }
        flash($n ? 'success' : 'warning', $n ? "{$n} file(s) added to the gallery." : 'No files were uploaded.');
    }
    redirect('admin/gallery.php');
}

$items = db()->query('SELECT * FROM gallery ORDER BY sort_order, id DESC')->fetchAll();
$cats = db()->query("SELECT DISTINCT category FROM gallery WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
admin_header('Gallery', 'gallery');
?>
<form method="post" enctype="multipart/form-data" class="card pad mb" data-upload-form>
  <?= csrf_field() ?>
  <h2 class="card-heading">Upload photos &amp; videos</h2>
  <label class="dropzone" data-dropzone>
    <input type="file" name="files[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime" required>
    <?= icon('cloud-upload') ?><span><strong>Drag &amp; drop images here</strong><br>or click to browse (JPG, PNG, WEBP, MP4)</span>
  </label>
  <div class="preview-grid" data-previews></div>
  <div class="form-row two mt-1">
    <label class="field">Category (optional)<input type="text" name="new_category" list="gcats" placeholder="e.g. Lab, Field Work, Events, Awards"><datalist id="gcats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
    <div class="field"><span>&nbsp;</span><button class="btn btn-primary" type="submit"><?= icon('upload') ?> Upload to gallery</button></div>
  </div>
  <div class="upload-progress" data-progress hidden><div><span></span></div><small>Uploading… <b>0%</b></small></div>
  <p class="muted small m0">Tip: images you add to a project appear in the Gallery page automatically too.</p>
</form>

<form method="post" class="card pad">
  <?= csrf_field() ?><input type="hidden" name="save" value="1">
  <div class="card-toolbar flat"><h2 class="card-heading">Gallery items (<?= count($items) ?>)</h2><?php if ($items): ?><button class="btn btn-outline btn-sm" type="submit">Save changes</button><?php endif; ?></div>
  <?php if (!$items): ?><p class="muted">No gallery items yet.</p><?php endif; ?>
  <div class="media-grid">
    <?php foreach ($items as $g): ?>
      <div class="media-item">
        <div class="media-thumb">
          <?php if ($g['type'] === 'video'): ?><video src="<?= e(media_url($g['file_path'])) ?>" preload="metadata" muted></video>
          <?php else: ?><img src="<?= e(media_url($g['file_path'])) ?>" alt="" loading="lazy"><?php endif; ?>
        </div>
        <input type="text" name="title[<?= (int) $g['id'] ?>]" value="<?= e($g['title']) ?>" placeholder="Title" aria-label="Title">
        <input type="text" name="category[<?= (int) $g['id'] ?>]" value="<?= e($g['category']) ?>" placeholder="Category" list="gcats" aria-label="Category">
        <div class="media-meta">
          <input type="number" name="order[<?= (int) $g['id'] ?>]" value="<?= (int) $g['sort_order'] ?>" class="order-input" aria-label="Order" title="Order">
          <button class="icon-btn sm danger" type="submit" name="delete" value="<?= (int) $g['id'] ?>" formnovalidate data-confirm-click="Delete this item permanently?" aria-label="Delete"><?= icon('trash') ?></button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</form>
<?php admin_footer(); ?>
