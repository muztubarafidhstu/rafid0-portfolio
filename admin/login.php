<?php
require __DIR__ . '/_layout.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$needsSetup = setting('admin_pass_hash') === '';
$error = '';
$ip = client_ip();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($needsSetup) {
        $user = trim((string) ($_POST['username'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password2'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,60}$/', $user)) {
            $error = 'Username must be 3–60 characters (letters, numbers, . _ - @).';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass !== $pass2) {
            $error = 'The two passwords do not match.';
        } else {
            set_setting('admin_user', $user);
            set_setting('admin_pass_hash', password_hash($pass, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
            flash('success', 'Welcome! Your admin account is ready. Start by updating your profile and site settings.');
            redirect('admin/index.php');
        }
    } else {
        $st = db()->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?');
        $st->execute([$ip]);
        $row = $st->fetch() ?: ['attempts' => 0, 'last_attempt' => 0];
        $locked = $row['attempts'] >= 5 && (time() - (int) $row['last_attempt']) < 900;

        if ($locked) {
            $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } else {
            $user = trim((string) ($_POST['username'] ?? ''));
            $pass = (string) ($_POST['password'] ?? '');
            $okUser = hash_equals(strtolower(setting('admin_user')), strtolower($user));
            $okPass = password_verify($pass, setting('admin_pass_hash'));
            if ($okUser && $okPass) {
                db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
                if (password_needs_rehash(setting('admin_pass_hash'), PASSWORD_DEFAULT)) {
                    set_setting('admin_pass_hash', password_hash($pass, PASSWORD_DEFAULT));
                }
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
                redirect('admin/index.php');
            }
            $attempts = ((time() - (int) $row['last_attempt']) > 900) ? 1 : $row['attempts'] + 1;
            db()->prepare('INSERT INTO login_attempts(ip, attempts, last_attempt) VALUES(?,?,?) ON CONFLICT(ip) DO UPDATE SET attempts = excluded.attempts, last_attempt = excluded.last_attempt')
                ->execute([$ip, $attempts, time()]);
            usleep(400000);
            $error = 'Incorrect username or password.';
        }
    }
}
?><!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $needsSetup ? 'Create admin account' : 'Admin login' ?> — <?= e(site_name()) ?></title>
<link rel="icon" href="<?= e(url('favicon.php')) ?>">
<script>(function(){try{var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-page">
  <div class="login-card card pad">
    <a class="brand" href="<?= e(url()) ?>"><?= logo_html() ?><span class="brand-text"><strong><?= e(site_name()) ?></strong><small>Admin Panel</small></span></a>
    <?php if ($needsSetup): ?>
      <h1>Create your admin account</h1>
      <p class="muted small">This is a one-time step. Choose the username and password you will use to manage your website.</p>
    <?php else: ?>
      <h1>Welcome back</h1>
      <p class="muted small">Sign in to manage your portfolio.</p>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label class="field">Username
        <input type="text" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" autofocus>
      </label>
      <label class="field">Password
        <input type="password" name="password" required autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" minlength="<?= $needsSetup ? 8 : 1 ?>">
      </label>
      <?php if ($needsSetup): ?>
        <label class="field">Confirm password
          <input type="password" name="password2" required autocomplete="new-password" minlength="8">
        </label>
      <?php endif; ?>
      <button class="btn btn-primary" type="submit"><?= $needsSetup ? 'Create account & continue' : 'Sign in' ?> <?= icon('arrow-right') ?></button>
    </form>
    <p class="small muted center"><a href="<?= e(url()) ?>"><?= icon('arrow-left') ?> Back to website</a></p>
  </div>
</body>
</html>
