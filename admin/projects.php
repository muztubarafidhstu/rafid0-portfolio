<?php
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['delete'];
        $st = db()->prepare('SELECT * FROM projects WHERE id = ?');
        $st->execute([$id]);
        if ($p = $st->fetch()) {
            foreach (project_media($id) as $m) {
                delete_upload($m['file_path']);
            }
            delete_upload($p['cover_image']);
            delete_upload($p['diagram_image']);
            db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            flash('success', 'Project "' . $p['title'] . '" deleted.');
        }
    }
    if (isset($_POST['toggle'])) {
        db()->prepare('UPDATE projects SET published = 1 - published, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(int) $_POST['toggle']]);
        flash('success', 'Visibility updated.');
    }
    if (isset($_POST['feature'])) {
        db()->prepare('UPDATE projects SET featured = 1 - featured WHERE id = ?')->execute([(int) $_POST['feature']]);
        flash('success', 'Featured status updated.');
    }
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $st = db()->prepare('UPDATE projects SET sort_order = ? WHERE id = ?');
        foreach ($_POST['order'] as $id => $o) {
            $st->execute([(int) $o, (int) $id]);
        }
        flash('success', 'Order saved.');
    }
    redirect('admin/projects.php');
}

$projects = db()->query('SELECT p.*, (SELECT COUNT(*) FROM project_media m WHERE m.project_id = p.id) AS media_count FROM projects p ORDER BY sort_order, id DESC')->fetchAll();
admin_header('Projects', 'projects');
?>
<div class="card">
  <div class="card-toolbar">
    <p class="muted small m0">Projects marked <strong>★ Featured</strong> appear on the home page (first 4). Lower order numbers appear first.</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/project-edit.php')) ?>"><?= icon('plus') ?> New Project</a>
  </div>
  <form method="post" id="order-form"><?= csrf_field() ?></form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Order</th><th>Project</th><th>Category</th><th>Files</th><th>Status</th><th>Home</th><th class="ta-r">Action</th></tr></thead>
      <tbody>
      <?php foreach ($projects as $p): ?>
        <tr>
          <td><input form="order-form" class="order-input" type="number" name="order[<?= (int) $p['id'] ?>]" value="<?= (int) $p['sort_order'] ?>" aria-label="Order"></td>
          <td><div class="cell-title"><?php if ($p['cover_image']): ?><img src="<?= e(media_url($p['cover_image'])) ?>" alt=""><?php endif; ?><div><a href="<?= e(url('admin/project-edit.php?id=' . $p['id'])) ?>"><?= e($p['title']) ?></a><small class="muted d-block"><?= (int) $p['views'] ?> views</small></div></div></td>
          <td class="muted"><?= e($p['category']) ?></td>
          <td class="muted"><?= (int) $p['media_count'] ?></td>
          <td>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="toggle" value="<?= (int) $p['id'] ?>">
              <button class="pill-btn" type="submit" title="Click to toggle"><?= $p['published'] ? '<span class="pill pill-green">Published</span>' : '<span class="pill pill-amber">Draft</span>' ?></button></form>
          </td>
          <td>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="feature" value="<?= (int) $p['id'] ?>">
              <button class="star-btn<?= $p['featured'] ? ' on' : '' ?>" type="submit" title="Toggle featured" aria-label="Toggle featured"><?= icon('star') ?></button></form>
          </td>
          <td class="ta-r nowrap">
            <a class="icon-btn sm" href="<?= e(url('project.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank" title="View"><?= icon('eye') ?></a>
            <a class="icon-btn sm" href="<?= e(url('admin/project-edit.php?id=' . $p['id'])) ?>" title="Edit"><?= icon('edit') ?></a>
            <?= delete_button('admin/projects.php', (int) $p['id']) ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$projects): ?><tr><td colspan="7" class="center muted">No projects yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($projects): ?><div class="card-footer"><button form="order-form" class="btn btn-outline btn-sm" type="submit">Save order</button></div><?php endif; ?>
</div>
<?php admin_footer(); ?>
