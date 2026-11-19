<?php
require __DIR__ . '/_layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$fields = ['title', 'slug', 'subtitle', 'category', 'tags', 'summary', 'overview', 'features', 'technologies', 'specs', 'results',
    'chart_data', 'chart_label', 'details', 'completed_date', 'team_size', 'duration', 'status', 'demo_url', 'video_url'];
$p = array_fill_keys($fields, '');
$p += ['id' => 0, 'cover_image' => '', 'diagram_image' => '', 'featured' => 0, 'published' => 1, 'sort_order' => 0];
$p['status'] = 'Completed';

if ($id) {
    $st = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) {
        flash('error', 'Project not found.');
        redirect('admin/projects.php');
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
    foreach (['demo_url', 'video_url'] as $u) {
        if ($in[$u] !== '' && !preg_match('~^https?://~i', $in[$u])) {
            $in[$u] = 'https://' . $in[$u];
        }
    }

    if ($in['title'] === '') {
        flash('error', 'Please enter a project title.');
        $p = array_merge($p, $in);
    } else {
        $in['slug'] = unique_slug('projects', slugify($in['slug'] !== '' ? $in['slug'] : $in['title']), $id);
        $warnings = [];

        // Single images: cover + diagram
        foreach (['cover_image' => 'cover_file', 'diagram_image' => 'diagram_file'] as $col => $field) {
            $in[$col] = $p[$col];
            if (!empty($_POST['remove_' . $col])) {
                delete_upload($p[$col]);
                $in[$col] = '';
            }
            foreach (files_list($field) as $f) {
                try {
                    $up = store_upload($f, ['image']);
                    delete_upload($p[$col]);
                    $in[$col] = $up['path'];
                } catch (RuntimeException $ex) {
                    $warnings[] = $ex->getMessage();
                }
            }
        }

        if ($id) {
            $sets = implode(', ', array_map(function ($c) { return "$c = :$c"; }, array_keys($in)));
            $st = db()->prepare("UPDATE projects SET $sets, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $st->execute($in + ['id' => $id]);
        } else {
            $cols = array_keys($in);
            $st = db()->prepare('INSERT INTO projects(' . implode(',', $cols) . ') VALUES(:' . implode(', :', $cols) . ')');
            $st->execute($in);
            $id = (int) db()->lastInsertId();
        }

        // Existing media: rename / reorder / delete
        $titles = $_POST['media_title'] ?? [];
        $orders = $_POST['media_order'] ?? [];
        $deletes = array_map('intval', (array) ($_POST['media_delete'] ?? []));
        foreach (project_media($id) as $m) {
            if (in_array((int) $m['id'], $deletes, true)) {
                delete_upload($m['file_path']);
                db()->prepare('DELETE FROM project_media WHERE id = ?')->execute([$m['id']]);
                continue;
            }
            if (isset($titles[$m['id']])) {
                db()->prepare('UPDATE project_media SET title = ?, sort_order = ? WHERE id = ?')
                    ->execute([trim((string) $titles[$m['id']]), (int) ($orders[$m['id']] ?? 0), $m['id']]);
            }
        }

        // New media files
        $next = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM project_media WHERE project_id = ' . $id)->fetchColumn();
        $added = 0;
        foreach (files_list('media') as $f) {
            try {
                $up = store_upload($f);
                $title = pathinfo($f['name'], PATHINFO_FILENAME);
                $title = ucfirst(trim(preg_replace('/[_\-]+/', ' ', $title)));
                db()->prepare('INSERT INTO project_media(project_id, type, file_path, title, file_size, sort_order) VALUES(?,?,?,?,?,?)')
                    ->execute([$id, $up['kind'], $up['path'], $title, $up['size'], $next++]);
                $added++;
            } catch (RuntimeException $ex) {
                $warnings[] = $ex->getMessage();
            }
        }
        // Video links (YouTube etc.) as media
        foreach (lines($_POST['video_links'] ?? '') as $link) {
            if (preg_match('~^https?://~i', $link)) {
                db()->prepare('INSERT INTO project_media(project_id, type, file_path, title, sort_order) VALUES(?,?,?,?,?)')
                    ->execute([$id, 'video', $link, '', $next++]);
                $added++;
            }
        }

        // Document links (Google Drive etc.): "Title | URL" or just URL per line
        foreach (lines($_POST['doc_links'] ?? '') as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            $url = clean_link(count($parts) === 2 ? $parts[1] : $parts[0]);
            if ($url === '') {
                $warnings[] = 'Skipped a document link that does not look right: ' . $line;
                continue;
            }
            $title = count($parts) === 2 ? $parts[0] : 'Document';
            db()->prepare('INSERT INTO project_media(project_id, type, file_path, title, sort_order) VALUES(?,?,?,?,?)')
                ->execute([$id, 'document', $url, $title, $next++]);
            $added++;
        }

        foreach ($warnings as $w) {
            flash('warning', $w);
        }
        flash('success', 'Project saved' . ($added ? " with {$added} new file(s)" : '') . '. Your website is updated.');
        redirect('admin/project-edit.php?id=' . $id);
    }
}

$media = $id ? project_media($id) : [];
$cats = db()->query('SELECT DISTINCT category FROM projects WHERE category != \'\' ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

admin_header($id ? 'Edit Project' : 'Add New Project', 'projects');
?>
<nav class="breadcrumb"><a href="<?= e(url('admin/index.php')) ?>">Dashboard</a><?= icon('chevron-right') ?><a href="<?= e(url('admin/projects.php')) ?>">Projects</a><?= icon('chevron-right') ?><span><?= $id ? e($p['title']) : 'Add New' ?></span></nav>

<form method="post" enctype="multipart/form-data" class="edit-layout" data-upload-form>
  <?= csrf_field() ?>
  <div class="stack">
    <section class="card pad">
      <h2 class="card-heading">Project Information</h2>
      <div class="form">
        <label class="field">Project title *<input type="text" name="title" value="<?= e($p['title']) ?>" required placeholder="e.g. Egg Sorting Robot"></label>
        <label class="field">Subtitle / one-line description<input type="text" name="subtitle" value="<?= e($p['subtitle']) ?>" placeholder="Automated egg sorting and grading system"></label>
        <div class="form-row two">
          <label class="field">Category <small class="hint">Separate several with commas</small>
            <input type="text" name="category" value="<?= e($p['category']) ?>" list="cat-list" placeholder="Robotics, Automation">
            <datalist id="cat-list"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
          </label>
          <label class="field">Technologies (tags) <small class="hint">Comma separated — shown as chips</small>
            <input type="text" name="tags" value="<?= e($p['tags']) ?>" placeholder="ROS, Jetson Nano, Python, OpenCV">
          </label>
        </div>
        <label class="field">Short summary <small class="hint">Used on cards and for Google</small><textarea name="summary" rows="2"><?= e($p['summary']) ?></textarea></label>
        <label class="field">Overview<textarea name="overview" rows="4"><?= e($p['overview']) ?></textarea></label>
        <label class="field">Key features <small class="hint">One per line</small><textarea name="features" rows="5" placeholder="Automatic egg detection and grading&#10;High accuracy sorting mechanism"><?= e($p['features']) ?></textarea></label>
      </div>
    </section>

    <section class="card pad">
      <h2 class="card-heading">Technical details</h2>
      <div class="form">
        <div class="form-row two">
          <label class="field">Technologies used <small class="hint">One per line</small><textarea name="technologies" rows="7"><?= e($p['technologies']) ?></textarea></label>
          <label class="field">Specifications <small class="hint">One per line as <code>Name: Value</code></small><textarea name="specs" rows="7" placeholder="Accuracy: > 95%&#10;Power Supply: 24V DC"><?= e($p['specs']) ?></textarea></label>
        </div>
        <div class="form-row two">
          <label class="field">Results <small class="hint">One per line as <code>Value | Label</code></small><textarea name="results" rows="4" placeholder="95.6% | Sorting Accuracy&#10;3600 | Eggs / Hour"><?= e($p['results']) ?></textarea></label>
          <div class="form">
            <label class="field">Results chart numbers <small class="hint">Comma separated, e.g. 88, 91, 94, 96</small><input type="text" name="chart_data" value="<?= e($p['chart_data']) ?>"></label>
            <label class="field">Chart caption<input type="text" name="chart_label" value="<?= e($p['chart_label']) ?>" placeholder="Accuracy (%) across test runs"></label>
          </div>
        </div>
        <label class="field">Full details / write-up <small class="hint">Optional "Details" tab. Blank line = new paragraph, **bold**, lines starting with "- " become a list</small><textarea name="details" rows="8"><?= e($p['details']) ?></textarea></label>
      </div>
    </section>

    <section class="card pad">
      <h2 class="card-heading">Upload Project Files</h2>
      <label class="dropzone" data-dropzone>
        <input type="file" name="media[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.rar,.7z,.step,.stp,.stl,.dxf,.txt,.ino,.py">
        <?= icon('cloud-upload') ?>
        <span><strong>Drag &amp; drop files here</strong><br>or click to browse</span>
      </label>
      <p class="muted small">You can upload: <span class="chip">Images (JPG, PNG, WEBP)</span> <span class="chip">Videos (MP4)</span> <span class="chip">Documents (PDF, DOCX, XLSX)</span> <span class="chip">Files (ZIP, STEP, STL)</span></p>
      <div class="preview-grid" data-previews></div>
      <label class="field mt-1">Video links (YouTube / Vimeo) <small class="hint">One per line — great for large videos</small><textarea name="video_links" rows="2" placeholder="https://www.youtube.com/watch?v=..."></textarea></label>
      <label class="field mt-1">Document / file links (Google Drive, Dropbox…) <small class="hint">One per line as <code>Title | link</code> — for big files like datasets, CAD models, source code</small><textarea name="doc_links" rows="2" placeholder="3D Models (STEP) | https://drive.google.com/file/d/…"></textarea></label>

      <?php if ($media): ?>
        <h3 class="card-heading sub">Current files (<?= count($media) ?>)</h3>
        <div class="media-grid">
          <?php foreach ($media as $m): ?>
            <div class="media-item">
              <div class="media-thumb">
                <?php if ($m['type'] === 'image'): ?><img src="<?= e(media_url($m['file_path'])) ?>" alt="">
                <?php elseif ($m['type'] === 'video'): ?><span class="file-icon zip"><?= icon('play') ?></span>
                <?php else: ?><span class="file-icon <?= file_icon_class($m['file_path']) ?>"><?= e(file_badge($m['file_path'])) ?></span><?php endif; ?>
              </div>
              <input type="text" name="media_title[<?= (int) $m['id'] ?>]" value="<?= e($m['title']) ?>" placeholder="Title" aria-label="Title">
              <div class="media-meta">
                <input type="number" name="media_order[<?= (int) $m['id'] ?>]" value="<?= (int) $m['sort_order'] ?>" class="order-input" title="Order" aria-label="Order">
                <small class="muted"><?= e(ucfirst($m['type'])) ?><?= $m['file_size'] ? ' · ' . e(human_size((int) $m['file_size'])) : '' ?></small>
                <label class="del-check" title="Delete on save"><input type="checkbox" name="media_delete[]" value="<?= (int) $m['id'] ?>"><?= icon('trash') ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="muted small">Tick <?= icon('trash') ?> and save to remove a file. Lower order numbers show first.</p>
      <?php endif; ?>
    </section>
  </div>

  <aside class="stack sticky-side">
    <section class="card pad">
      <h2 class="card-heading">Publish</h2>
      <div class="form">
        <label class="switch"><input type="checkbox" name="published" value="1"<?= checked($p['published']) ?>><span></span> Published (visible on website)</label>
        <label class="switch"><input type="checkbox" name="featured" value="1"<?= checked($p['featured']) ?>><span></span> Featured on home page</label>
        <label class="field">Display order<input type="number" name="sort_order" value="<?= (int) $p['sort_order'] ?>"></label>
        <div class="upload-progress" data-progress hidden><div><span></span></div><small>Uploading… <b>0%</b></small></div>
        <div class="btn-row m0">
          <a class="btn btn-outline" href="<?= e(url('admin/projects.php')) ?>">Cancel</a>
          <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save Project</button>
        </div>
        <?php if ($id): ?><a class="link-accent small" href="<?= e(url('project.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank"><?= icon('eye') ?> View on website</a><?php endif; ?>
      </div>
    </section>

    <section class="card pad">
      <h2 class="card-heading">Cover image</h2>
      <?php if ($p['cover_image']): ?><img class="side-preview" src="<?= e(media_url($p['cover_image'])) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_cover_image" value="1"> Remove</label><?php endif; ?>
      <input type="file" name="cover_file" accept="image/*" class="file-input">
    </section>

    <section class="card pad">
      <h2 class="card-heading">System block diagram</h2>
      <?php if ($p['diagram_image']): ?><img class="side-preview light-bg" src="<?= e(media_url($p['diagram_image'])) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_diagram_image" value="1"> Remove</label><?php endif; ?>
      <input type="file" name="diagram_file" accept="image/*" class="file-input">
    </section>

    <section class="card pad">
      <h2 class="card-heading">Project facts</h2>
      <div class="form">
        <label class="field">Status
          <select name="status">
            <?php foreach (['Completed', 'In Progress', 'Prototype', 'Research', 'On Hold'] as $s): ?><option<?= $p['status'] === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field">Completed / date<input type="month" name="completed_date" value="<?= e(substr($p['completed_date'], 0, 7)) ?>"></label>
        <label class="field">Team size<input type="text" name="team_size" value="<?= e($p['team_size']) ?>" placeholder="3 Members"></label>
        <label class="field">Project duration<input type="text" name="duration" value="<?= e($p['duration']) ?>" placeholder="4 Months"></label>
        <label class="field">Live demo URL<input type="url" name="demo_url" value="<?= e($p['demo_url']) ?>" placeholder="https://"></label>
        <label class="field">Main video (YouTube link)<input type="url" name="video_url" value="<?= e($p['video_url']) ?>" placeholder="https://youtube.com/watch?v=…"></label>
        <label class="field">URL slug <small class="hint">Leave blank to generate</small><input type="text" name="slug" value="<?= e($p['slug']) ?>" placeholder="egg-sorting-robot"></label>
      </div>
    </section>
  </aside>
</form>
<?php admin_footer(); ?>
