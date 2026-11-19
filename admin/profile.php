<?php
require __DIR__ . '/_layout.php';
require_admin();

$textKeys = ['about_short', 'about_bio', 'experience', 'education', 'skills', 'achievements', 'cv_updated'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($textKeys as $k) {
        if (isset($_POST[$k])) {
            set_setting($k, trim((string) $_POST[$k]));
        }
    }
    if (!empty($_POST['remove_about_photo'])) {
        delete_upload(setting('about_photo'));
        set_setting('about_photo', '');
    }
    if (!empty($_POST['remove_cv'])) {
        delete_upload(setting('cv_file'));
        set_setting('cv_file', '');
    }
    try {
        foreach (files_list('about_photo_file') as $f) {
            $up = store_upload($f, ['image']);
            delete_upload(setting('about_photo'));
            set_setting('about_photo', $up['path']);
        }
        foreach (files_list('cv_upload') as $f) {
            $up = store_upload($f, ['document']);
            delete_upload(setting('cv_file'));
            set_setting('cv_file', $up['path']);
            set_setting('cv_updated', date('Y-m'));
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
    }
    flash('success', 'Profile saved. Your About and CV pages are updated.');
    redirect('admin/profile.php');
}

admin_header('Profile, About & CV', 'profile');
?>
<form method="post" enctype="multipart/form-data" class="edit-layout" data-upload-form>
  <?= csrf_field() ?>
  <div class="stack">
    <section class="card pad">
      <h2 class="card-heading">About me</h2>
      <div class="form">
        <label class="field">Short introduction <small class="hint">1–2 sentences. Shown on About and CV pages</small><textarea name="about_short" rows="3"><?= e(setting('about_short')) ?></textarea></label>
        <label class="field">Full biography <small class="hint">Blank line = new paragraph</small><textarea name="about_bio" rows="9"><?= e(setting('about_bio')) ?></textarea></label>
      </div>
    </section>
    <section class="card pad">
      <h2 class="card-heading"><?= icon('briefcase') ?> Experience</h2>
      <p class="muted small">One job per line: <code>Period | Job title | Company | Short description</code></p>
      <label class="field"><textarea name="experience" rows="6" placeholder="2023 – Present | R&D Engineer | Company | What you do"><?= e(setting('experience')) ?></textarea></label>
    </section>
    <section class="card pad">
      <h2 class="card-heading"><?= icon('graduation') ?> Education</h2>
      <p class="muted small">One per line: <code>Years | Degree | Institution | Note</code></p>
      <label class="field"><textarea name="education" rows="4"><?= e(setting('education')) ?></textarea></label>
    </section>
    <section class="card pad">
      <h2 class="card-heading"><?= icon('cpu') ?> Skills</h2>
      <p class="muted small">One group per line: <code>Group: skill, skill, skill</code></p>
      <label class="field"><textarea name="skills" rows="6"><?= e(setting('skills')) ?></textarea></label>
    </section>
    <section class="card pad">
      <h2 class="card-heading"><?= icon('trophy') ?> Awards &amp; Achievements</h2>
      <p class="muted small">One per line: <code>Year | Title | Details</code></p>
      <label class="field"><textarea name="achievements" rows="5"><?= e(setting('achievements')) ?></textarea></label>
    </section>
  </div>
  <aside class="stack sticky-side">
    <section class="card pad">
      <h2 class="card-heading">Save</h2>
      <div class="upload-progress" data-progress hidden><div><span></span></div><small>Uploading… <b>0%</b></small></div>
      <button class="btn btn-primary w-100" type="submit"><?= icon('check') ?> Save profile</button>
      <p class="small mt-1"><a class="link-accent" href="<?= e(url('about.php')) ?>" target="_blank"><?= icon('eye') ?> View About page</a></p>
    </section>
    <section class="card pad">
      <h2 class="card-heading">Profile photo</h2>
      <?php if (setting('about_photo')): ?><img class="side-preview portrait" src="<?= e(media_url(setting('about_photo'))) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_about_photo" value="1"> Remove</label><?php endif; ?>
      <input type="file" name="about_photo_file" accept="image/*" class="file-input">
    </section>
    <section class="card pad" id="cv">
      <h2 class="card-heading">CV / Résumé (PDF)</h2>
      <?php if (setting('cv_file')): ?>
        <p class="small"><a href="<?= e(media_url(setting('cv_file'))) ?>" target="_blank"><?= icon('file-text') ?> <?= e(basename(setting('cv_file'))) ?></a></p>
        <label class="small muted"><input type="checkbox" name="remove_cv" value="1"> Remove</label>
      <?php else: ?><p class="small muted">No CV uploaded yet. The "Download CV" buttons will point to your CV page until you upload one.</p><?php endif; ?>
      <input type="file" name="cv_upload" accept=".pdf,.doc,.docx" class="file-input">
      <label class="field mt-1">Last updated <small class="hint">Set automatically on upload</small><input type="month" name="cv_updated" value="<?= e(substr(setting('cv_updated'), 0, 7)) ?>"></label>
    </section>
  </aside>
</form>
<?php admin_footer(); ?>
