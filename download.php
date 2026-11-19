<?php
/** Counts a download, then sends the visitor to the file. */
require __DIR__ . '/includes/bootstrap.php';

$st = db()->prepare('SELECT * FROM downloads WHERE id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$d = $st->fetch();
if (!$d) {
    not_found();
}
db()->prepare('UPDATE downloads SET download_count = download_count + 1 WHERE id = ?')->execute([$d['id']]);
redirect(media_url($d['file_path']));
