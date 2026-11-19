<?php
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete'])) {
        $st = db()->prepare('SELECT * FROM downloads WHERE id = ?');
        $st->execute([(int) $_POST['delete']]);
        if ($d = $st->fetch()) {
            delete_upload($d['file_path']);
            db()->prepare('DELETE FROM downloads WHERE id = ?')->execute([$d['id']]);
            flash('success', 'Download removed.');
        }
    } elseif (isset($_POST['save'])) {
        $st = db()->prepare('UPDATE downloads SET title = ?, description = ?, category = ?, sort_order = ? WHERE id = ?');
        foreach ((array) ($_POST['title'] ?? []) as $did => $t) {
            if (trim((string) $t) === '') continue;
            $st->execute([trim((string) $t), trim((string) ($_POST['description'][$did] ?? '')), trim((string) ($_POST['category'][$did] ?? '')), (int) ($_POST['order'][$did] ?? 0), (int) $did]);
        }
        flash('success', 'Downloads updated.');
    } else {
        $title = trim((string) ($_POST['new_title'] ?? ''));
        $files = files_list('file');
        $link = clean_link((string) ($_POST['new_link'] ?? ''));
        if (trim((string) ($_POST['new_link'] ?? '')) !== '' && $link === '') {
            flash('error', 'That link does not look right. Copy the full share link (starting with https://).');
        } elseif ($link !== '') {
            $t = $title !== '' ? $title : 'Download';
            db()->prepare('INSERT INTO downloads(title, description, category, file_path, file_size) VALUES(?,?,?,?,0)')
                ->execute([$t, trim((string) ($_POST['new_description'] ?? '')), trim((string) ($_POST['new_category'] ?? '')), $link]);
            flash('success', '"' . $t . '" link is now on the Downloads page.');
        } elseif (!$files) {
            flash('error', 'Please choose a file to upload, or paste a link.');
        }
        foreach ($files as $f) {
            try {
                $up = store_upload($f);
                $t = $title !== '' ? $title : ucfirst(trim(preg_replace('/[_\-]+/', ' ', pathinfo($f['name'], PATHINFO_FILENAME))));
                db()->prepare('INSERT INTO downloads(title, description, category, file_path, file_size) VALUES(?,?,?,?,?)')
                    ->execute([$t, trim((string) ($_POST['new_description'] ?? '')), trim((string) ($_POST['new_category'] ?? '')), $up['path'], $up['size']]);
                flash('success', '"' . $t . '" is now available on the Downloads page.');
            } catch (RuntimeException $ex) {
                flash('error', $ex->getMessage());
            }
        }
    }
    redirect('admin/downloads.php');
}

$items = db()->query('SELECT * FROM downloads ORDER BY sort_order, id DESC')->fetchAll();
$cats = db()->query("SELECT DISTINCT category FROM downloads WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
admin_header('Downloads', 'downloads');
?>
<form method="post" enctype="multipart/form-data" class="card pad mb" data-upload-form>
  <?= csrf_field() ?>
  <h2 class="card-heading">Add a downloadable file</h2>
  <div class="form">
    <label class="dropzone small-zone" data-dropzone>
      <input type="file" name="file[]" multiple>
      <?= icon('cloud-upload') ?><span><strong>Choose file(s)</strong> — PDF, DOCX, XLSX, ZIP, STEP, STL, code…</span>
    </label>
    <div class="preview-grid" data-previews></div>
    <label class="field">…or paste a link instead <small class="hint">Google Drive, Dropbox or OneDrive share link. Best for big files (datasets, CAD, videos). In Google Drive: Share → "Anyone with the link" → Copy link</small><input type="url" name="new_link" placeholder="https://drive.google.com/file/d/…"></label>
    <div class="form-row two">
      <label class="field">Title <small class="hint">Blank = use file name</small><input type="text" name="new_title" placeholder="e.g. Source Code (ZIP)"></label>
      <label class="field">Category<input type="text" name="new_category" list="dcats" placeholder="e.g. Datasets, Source Code, Papers"><datalist id="dcats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
    </div>
    <label class="field">Short description<input type="text" name="new_description" placeholder="Optional"></label>
    <div class="upload-progress" data-progress hidden><div><span></span></div><small>Uploading… <b>0%</b></small></div>
    <div><button class="btn btn-primary" type="submit"><?= icon('upload') ?> Upload</button></div>
  </div>
  <p class="muted small m0 mt-1">Files attached to projects (PDFs, ZIPs…) are listed on the Downloads page automatically as well.</p>
</form>

<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="save" value="1">
  <div class="card-toolbar"><h2 class="card-heading m0">Files (<?= count($items) ?>)</h2><?php if ($items): ?><button class="btn btn-outline btn-sm" type="submit">Save changes</button><?php endif; ?></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Order</th><th>File</th><th>Title</th><th>Category</th><th>Description</th><th>Downloads</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $d): ?>
        <tr>
          <td><input class="order-input" type="number" name="order[<?= (int) $d['id'] ?>]" value="<?= (int) $d['sort_order'] ?>" aria-label="Order"></td>
          <td><a href="<?= e(media_url($d['file_path'])) ?>" target="_blank" rel="noopener" class="file-cell"><span class="file-icon <?= file_icon_class($d['file_path']) ?>"><?= e(file_badge($d['file_path'])) ?></span><small class="muted"><?= is_external_url($d['file_path']) ? 'Link' : e(human_size((int) $d['file_size'])) ?></small></a></td>
          <td><input class="table-input" type="text" name="title[<?= (int) $d['id'] ?>]" value="<?= e($d['title']) ?>" aria-label="Title"></td>
          <td><input class="table-input" type="text" name="category[<?= (int) $d['id'] ?>]" value="<?= e($d['category']) ?>" list="dcats" aria-label="Category"></td>
          <td><input class="table-input" type="text" name="description[<?= (int) $d['id'] ?>]" value="<?= e($d['description']) ?>" aria-label="Description"></td>
          <td class="muted"><?= (int) $d['download_count'] ?></td>
          <td class="ta-r"><button class="icon-btn sm danger" type="submit" name="delete" value="<?= (int) $d['id'] ?>" data-confirm-click="Delete this file permanently?" aria-label="Delete"><?= icon('trash') ?></button></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="7" class="center muted">No downloads yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</form>
<?php admin_footer(); ?>
