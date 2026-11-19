<?php
require __DIR__ . '/_layout.php';
require_admin();

$textKeys = ['site_name', 'site_alias', 'site_title', 'meta_description', 'accent_color', 'footer_text',
    'hero_badge', 'hero_title', 'hero_text', 'stats', 'expertise',
    'contact_email', 'contact_phone', 'contact_location', 'availability',
    'social_linkedin', 'social_github', 'social_youtube', 'social_instagram', 'social_facebook', 'social_x', 'social_scholar', 'social_researchgate'];
$imageKeys = ['logo' => 'logo_file', 'hero_image' => 'hero_file'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $section = $_POST['section'] ?? '';

    if ($section === 'account') {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $new2 = (string) ($_POST['new_password2'] ?? '');
        $user = trim((string) ($_POST['admin_user'] ?? ''));
        if (!password_verify($cur, setting('admin_pass_hash'))) {
            flash('error', 'Current password is incorrect.');
        } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,60}$/', $user)) {
            flash('error', 'Username must be 3–60 characters (letters, numbers, . _ - @).');
        } elseif ($new !== '' && strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $new2) {
            flash('error', 'New passwords do not match.');
        } else {
            set_setting('admin_user', $user);
            if ($new !== '') {
                set_setting('admin_pass_hash', password_hash($new, PASSWORD_DEFAULT));
            }
            session_regenerate_id(true);
            flash('success', 'Account updated.');
        }
        redirect('admin/settings.php#account');
    }

    foreach ($textKeys as $k) {
        if (isset($_POST[$k])) {
            $v = trim((string) $_POST[$k]);
            if (strpos($k, 'social_') === 0 && $v !== '' && !preg_match('~^https?://~i', $v)) {
                $v = 'https://' . $v;
            }
            if ($k === 'accent_color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                continue;
            }
            if ($k === 'contact_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Contact email is not valid.');
                continue;
            }
            set_setting($k, $v);
        }
    }
    if (isset($_POST['notify_toggle'])) {
        set_setting('notify_email', isset($_POST['notify_email']) ? '1' : '0');
    }
    foreach ($imageKeys as $key => $field) {
        if (!empty($_POST['remove_' . $key])) {
            delete_upload(setting($key));
            set_setting($key, '');
        }
        foreach (files_list($field) as $f) {
            try {
                $up = store_upload($f, ['image']);
                delete_upload(setting($key));
                set_setting($key, $up['path']);
            } catch (RuntimeException $ex) {
                flash('error', $ex->getMessage());
            }
        }
    }
    flash('success', 'Settings saved. Your website is updated.');
    redirect('admin/settings.php' . ($section !== '' ? '#' . preg_replace('/[^a-z]/', '', $section) : ''));
}

admin_header('Site Settings', 'settings');
?>
<div class="settings-tabs" role="tablist">
  <a href="#general" data-stab="general" class="active">General</a>
  <a href="#home" data-stab="home">Home page</a>
  <a href="#contact" data-stab="contact">Contact &amp; Social</a>
  <a href="#account" data-stab="account">Account &amp; Password</a>
</div>

<form method="post" enctype="multipart/form-data" class="card pad settings-panel" id="general" data-spanel="general">
  <?= csrf_field() ?><input type="hidden" name="section" value="general">
  <h2 class="card-heading">General</h2>
  <div class="form">
    <div class="form-row two">
      <label class="field">Your name / site name<input type="text" name="site_name" value="<?= e(setting('site_name')) ?>" required></label>
      <label class="field">Job title<input type="text" name="site_title" value="<?= e(setting('site_title')) ?>" placeholder="R&D Engineer"></label>
    </div>
    <label class="field">Username / also known as <small class="hint">Helps Google show your site when people search this name, e.g. rafid0. Use the same username on LinkedIn, GitHub and other profiles</small><input type="text" name="site_alias" value="<?= e(setting('site_alias')) ?>" placeholder="rafid0"></label>
    <label class="field">Site description <small class="hint">Shown in Google results and link previews</small><textarea name="meta_description" rows="2"><?= e(setting('meta_description')) ?></textarea></label>
    <label class="field">Footer text<input type="text" name="footer_text" value="<?= e(setting('footer_text')) ?>"></label>
    <div class="form-row two">
      <label class="field">Accent colour
        <span class="color-row"><input type="color" name="accent_color" value="<?= e(setting('accent_color', '#22c55e')) ?>">
          <?php foreach (['#22c55e', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444'] as $c): ?><button type="button" class="swatch" style="background:<?= $c ?>" data-swatch="<?= $c ?>" aria-label="Use <?= $c ?>"></button><?php endforeach; ?>
        </span>
      </label>
      <div class="field">Logo <small class="hint">Optional. Square PNG works best. Without a logo your initials are shown</small>
        <?php if (setting('logo')): ?><span class="inline-preview"><img src="<?= e(media_url(setting('logo'))) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_logo" value="1"> Remove</label></span><?php endif; ?>
        <input type="file" name="logo_file" accept="image/*" class="file-input">
      </div>
    </div>
    <div><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </div>
</form>

<form method="post" enctype="multipart/form-data" class="card pad settings-panel" id="home" data-spanel="home" hidden>
  <?= csrf_field() ?><input type="hidden" name="section" value="home">
  <h2 class="card-heading">Home page</h2>
  <div class="form">
    <div class="form-row two">
      <label class="field">Small badge above headline<input type="text" name="hero_badge" value="<?= e(setting('hero_badge')) ?>"></label>
      <label class="field">Headline <small class="hint">One phrase per line</small><textarea name="hero_title" rows="3"><?= e(setting('hero_title')) ?></textarea></label>
    </div>
    <label class="field">Intro text<textarea name="hero_text" rows="3"><?= e(setting('hero_text')) ?></textarea></label>
    <div class="field">Hero image <small class="hint">A wide photo of your best work (robot, hardware, lab). PNG with transparent or dark background looks great</small>
      <?php if (setting('hero_image')): ?><span class="inline-preview wide"><img src="<?= e(media_url(setting('hero_image'))) ?>" alt=""><label class="small muted"><input type="checkbox" name="remove_hero_image" value="1"> Remove</label></span><?php endif; ?>
      <input type="file" name="hero_file" accept="image/*" class="file-input">
    </div>
    <div class="form-row two">
      <label class="field">Statistics bar <small class="hint">One per line: <code>Number | Label</code></small><textarea name="stats" rows="7"><?= e(setting('stats')) ?></textarea></label>
      <label class="field">Expertise cards <small class="hint">One per line: <code>Title | Description | icon</code><br>Icons: cpu, robot-arm, robot, factory, target, zap, network, shield, layers, globe</small><textarea name="expertise" rows="7"><?= e(setting('expertise')) ?></textarea></label>
    </div>
    <div><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </div>
</form>

<form method="post" class="card pad settings-panel" id="contact" data-spanel="contact" hidden>
  <?= csrf_field() ?><input type="hidden" name="section" value="contact"><input type="hidden" name="notify_toggle" value="1">
  <h2 class="card-heading">Contact details</h2>
  <div class="form">
    <div class="form-row two">
      <label class="field">Email<input type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>" placeholder="you@yourdomain.com"></label>
      <label class="field">Phone<input type="text" name="contact_phone" value="<?= e(setting('contact_phone')) ?>" placeholder="+880 …"></label>
    </div>
    <div class="form-row two">
      <label class="field">Location<input type="text" name="contact_location" value="<?= e(setting('contact_location')) ?>"></label>
      <label class="field">Availability message<input type="text" name="availability" value="<?= e(setting('availability')) ?>"></label>
    </div>
    <label class="switch"><input type="checkbox" name="notify_email" value="1"<?= checked(setting('notify_email') === '1') ?>><span></span> Email me when someone sends a message (to the email above)</label>
    <h3 class="card-heading sub">Social links <small class="hint">Leave empty to hide</small></h3>
    <div class="form-row two">
      <?php foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'youtube' => 'YouTube', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'x' => 'X / Twitter', 'scholar' => 'Google Scholar', 'researchgate' => 'ResearchGate'] as $k => $label): ?>
        <label class="field"><?= e($label) ?><input type="url" name="social_<?= $k ?>" value="<?= e(setting('social_' . $k)) ?>" placeholder="https://"></label>
      <?php endforeach; ?>
    </div>
    <div><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </div>
</form>

<form method="post" class="card pad settings-panel narrow-form" id="account" data-spanel="account" hidden>
  <?= csrf_field() ?><input type="hidden" name="section" value="account">
  <h2 class="card-heading">Account &amp; Password</h2>
  <div class="form">
    <label class="field">Username<input type="text" name="admin_user" value="<?= e(setting('admin_user')) ?>" required autocomplete="username"></label>
    <label class="field">New password <small class="hint">Leave blank to keep the current one</small><input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
    <label class="field">Confirm new password<input type="password" name="new_password2" autocomplete="new-password"></label>
    <label class="field">Current password (required)<input type="password" name="current_password" required autocomplete="current-password"></label>
    <div><button class="btn btn-primary" type="submit"><?= icon('shield') ?> Update account</button></div>
  </div>
</form>
<?php admin_footer(); ?>
