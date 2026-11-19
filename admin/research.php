<?php
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete'])) {
        $st = db()->prepare('SELECT * FROM research WHERE id = ?');
        $st->execute([(int) $_POST['delete']]);
        if ($r = $st->fetch()) {
            delete_upload($r['image']);
            delete_upload($r['pdf_file']);
            db()->prepare('DELETE FROM research WHERE id = ?')->execute([$r['id']]);
            flash('success', 'Research item deleted.');
        }
    }
    if (isset($_POST['toggle'])) {
        db()->prepare('UPDATE research SET published = 1 - published WHERE id = ?')->execute([(int) $_POST['toggle']]);
        flash('success', 'Visibility updated.');
    }
    if (isset($_POST['feature'])) {
        db()->prepare('UPDATE research SET featured = 1 - featured WHERE id = ?')->execute([(int) $_POST['feature']]);
        flash('success', 'Featured status updated.');
    }
    redirect('admin/research.php');
}

$items = db()->query('SELECT * FROM research ORDER BY sort_order, id DESC')->fetchAll();
admin_header('Research', 'research');
?>
<div class="card">
  <div class="card-toolbar">
    <p class="muted small m0">Publications, patents and ongoing research. <strong>★ Featured</strong> items appear on the home page (first 3).</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/research-edit.php')) ?>"><?= icon('plus') ?> New Research</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Title</th><th>Type / Status</th><th>Venue</th><th>Visible</th><th>Home</th><th class="ta-r">Action</th></tr></thead>
      <tbody>
      <?php foreach ($items as $r): ?>
        <tr>
          <td><div class="cell-title"><?php if ($r['image']): ?><img src="<?= e(media_url($r['image'])) ?>" alt=""><?php endif; ?><a href="<?= e(url('admin/research-edit.php?id=' . $r['id'])) ?>"><?= e($r['title']) ?></a></div></td>
          <td><span class="pill pill-<?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td class="muted"><?= e(trim($r['venue'] . ' ' . $r['year'])) ?></td>
          <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="toggle" value="<?= (int) $r['id'] ?>"><button class="pill-btn" type="submit"><?= $r['published'] ? '<span class="pill pill-green">Published</span>' : '<span class="pill pill-amber">Draft</span>' ?></button></form></td>
          <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="feature" value="<?= (int) $r['id'] ?>"><button class="star-btn<?= $r['featured'] ? ' on' : '' ?>" type="submit" aria-label="Toggle featured"><?= icon('star') ?></button></form></td>
          <td class="ta-r nowrap">
            <a class="icon-btn sm" href="<?= e(url('research-item.php?slug=' . rawurlencode($r['slug']))) ?>" target="_blank" title="View"><?= icon('eye') ?></a>
            <a class="icon-btn sm" href="<?= e(url('admin/research-edit.php?id=' . $r['id'])) ?>" title="Edit"><?= icon('edit') ?></a>
            <?= delete_button('admin/research.php', (int) $r['id']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="6" class="center muted">No research yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
