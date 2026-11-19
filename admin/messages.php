<?php
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM messages WHERE id = ?')->execute([(int) $_POST['delete']]);
        flash('success', 'Message deleted.');
    }
    if (isset($_POST['unread'])) {
        db()->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([(int) $_POST['unread']]);
    }
    if (isset($_POST['read_all'])) {
        db()->exec('UPDATE messages SET is_read = 1');
        flash('success', 'All messages marked as read.');
    }
    redirect('admin/messages.php');
}

$view = null;
if (!empty($_GET['id'])) {
    $st = db()->prepare('SELECT * FROM messages WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $view = $st->fetch();
    if ($view && !$view['is_read']) {
        db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$view['id']]);
    }
}
$messages = db()->query('SELECT * FROM messages ORDER BY id DESC LIMIT 500')->fetchAll();
admin_header('Messages', 'messages');
?>
<div class="msg-layout">
  <div class="card">
    <div class="card-toolbar"><h2 class="card-heading m0">Inbox (<?= count($messages) ?>)</h2>
      <form method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" name="read_all" value="1">Mark all read</button></form></div>
    <?php if (!$messages): ?><p class="muted pad-x">No messages yet. When someone uses your Contact page, the message appears here<?= setting('contact_email') !== '' ? ' and is emailed to ' . e(setting('contact_email')) : '' ?>.</p><?php endif; ?>
    <ul class="msg-list">
      <?php foreach ($messages as $m): ?>
        <li class="<?= $m['is_read'] ? '' : 'unread' ?><?= $view && $view['id'] == $m['id'] ? ' current' : '' ?>">
          <a href="<?= e(url('admin/messages.php?id=' . $m['id'])) ?>">
            <strong><?= e($m['name']) ?></strong><small><?= e(format_date($m['created_at'], 'd M Y')) ?></small>
            <span><?= e(excerpt($m['subject'] !== '' ? $m['subject'] : $m['message'], 70)) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card pad">
    <?php if ($view): ?>
      <div class="msg-head">
        <div>
          <h2><?= e($view['subject'] !== '' ? $view['subject'] : 'Message from ' . $view['name']) ?></h2>
          <p class="muted small"><?= e($view['name']) ?> &lt;<a href="mailto:<?= e($view['email']) ?>"><?= e($view['email']) ?></a>&gt; · <?= e(format_date($view['created_at'], 'd M Y, H:i')) ?> (UTC)</p>
        </div>
      </div>
      <div class="msg-body"><?= nl2br(e($view['message'])) ?></div>
      <div class="btn-row">
        <a class="btn btn-primary" href="mailto:<?= e($view['email']) ?>?subject=<?= rawurlencode('Re: ' . ($view['subject'] !== '' ? $view['subject'] : 'Your message')) ?>"><?= icon('send') ?> Reply by email</a>
        <form method="post" class="inline"><?= csrf_field() ?><button class="btn btn-outline" name="unread" value="<?= (int) $view['id'] ?>">Mark unread</button></form>
        <form method="post" class="inline" data-confirm="Delete this message?"><?= csrf_field() ?><button class="btn btn-outline danger-text" name="delete" value="<?= (int) $view['id'] ?>"><?= icon('trash') ?> Delete</button></form>
      </div>
    <?php else: ?>
      <div class="empty-state"><?= icon('inbox') ?><p class="muted">Select a message to read it.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php admin_footer(); ?>
