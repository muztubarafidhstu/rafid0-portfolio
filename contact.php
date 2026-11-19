<?php
require __DIR__ . '/includes/bootstrap.php';

$page_title = 'Contact';
$active = 'contact';
$meta_description = 'Get in touch with ' . site_name() . ' for collaborations, projects and opportunities.';
$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $tooFast = (time() - (int) ($_POST['_t'] ?? 0)) < 3;
    $honeypot = trim((string) ($_POST['website'] ?? '')) !== '';
    $_SESSION['contact_sent'] = array_filter($_SESSION['contact_sent'] ?? [], function ($t) { return $t > time() - 3600; });

    if ($old['name'] === '' || mb_strlen($old['name']) > 100) $errors['name'] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (mb_strlen($old['subject']) > 200) $errors['subject'] = 'Subject is too long.';
    if (mb_strlen($old['message']) < 10) $errors['message'] = 'Please write a little more (at least 10 characters).';
    if (mb_strlen($old['message']) > 5000) $errors['message'] = 'Message is too long (max 5000 characters).';
    if (count($_SESSION['contact_sent']) >= 5) $errors['form'] = 'You have sent several messages already. Please try again later.';

    if (!$errors) {
        if (!$honeypot && !$tooFast) {
            db()->prepare('INSERT INTO messages(name, email, subject, message, ip) VALUES(?,?,?,?,?)')
                ->execute([$old['name'], $old['email'], $old['subject'], $old['message'], client_ip()]);
            $_SESSION['contact_sent'][] = time();
            $to = setting('contact_email');
            if ($to !== '' && setting('notify_email') === '1' && function_exists('mail')) {
                $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
                $host = preg_replace('/^www\./', '', $host);
                $subject = 'New website message: ' . ($old['subject'] !== '' ? $old['subject'] : 'from ' . $old['name']);
                $body = "Name: {$old['name']}\nEmail: {$old['email']}\nSubject: {$old['subject']}\n\n{$old['message']}\n\n— Sent from your website contact form";
                $headers = "From: Website <no-reply@{$host}>\r\nReply-To: " . str_replace(["\r", "\n"], '', $old['email']) . "\r\nContent-Type: text/plain; charset=UTF-8";
                @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
            }
        }
        flash('success', 'Thank you! Your message has been sent. I will get back to you soon.');
        redirect('contact.php');
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?= icon('chevron-right') ?><span>Contact</span></nav>
    <h1>Let&rsquo;s work together</h1>
    <p class="lead muted"><?= e(setting('availability')) ?></p>
  </div>
</section>
<section class="section section-top-0">
  <div class="container contact-grid">
    <div class="card pad">
      <?= render_flashes() ?>
      <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>
      <form method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_t" value="<?= time() ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-row two">
          <label class="field">Your name
            <input type="text" name="name" value="<?= e($old['name']) ?>" required maxlength="100" autocomplete="name">
            <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
          </label>
          <label class="field">Email address
            <input type="email" name="email" value="<?= e($old['email']) ?>" required maxlength="190" autocomplete="email">
            <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
          </label>
        </div>
        <label class="field">Subject
          <input type="text" name="subject" value="<?= e($old['subject']) ?>" maxlength="200">
          <?php if (isset($errors['subject'])): ?><span class="field-error"><?= e($errors['subject']) ?></span><?php endif; ?>
        </label>
        <label class="field">Message
          <textarea name="message" rows="7" required maxlength="5000"><?= e($old['message']) ?></textarea>
          <?php if (isset($errors['message'])): ?><span class="field-error"><?= e($errors['message']) ?></span><?php endif; ?>
        </label>
        <button class="btn btn-primary" type="submit"><?= icon('send') ?> Send message</button>
      </form>
    </div>
    <aside class="stack">
      <div class="card pad">
        <h2 class="card-heading">Contact details</h2>
        <ul class="contact-list">
          <?php if (setting('contact_email') !== ''): ?><li><span class="ci"><?= icon('mail') ?></span><div><small>Email</small><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></div></li><?php endif; ?>
          <?php if (setting('contact_phone') !== ''): ?><li><span class="ci"><?= icon('phone') ?></span><div><small>Phone</small><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></div></li><?php endif; ?>
          <?php if (setting('contact_location') !== ''): ?><li><span class="ci"><?= icon('map-pin') ?></span><div><small>Location</small><span><?= e(setting('contact_location')) ?></span></div></li><?php endif; ?>
        </ul>
      </div>
      <?php if (social_links()): ?>
      <div class="card pad">
        <h2 class="card-heading">Find me online</h2>
        <div class="social-row"><?php foreach (social_links() as $s): ?><a class="icon-btn" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['icon']) ?></a><?php endforeach; ?></div>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
