<?php
/**
 * Shared helper functions.
 */

/* ---------- Output & URLs ---------- */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

function asset(string $path): string
{
    $full = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($full) ? (string) filemtime($full) : APP_VERSION;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** URL of a stored file (uploads/... or assets/...) or an external link. */
function media_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }
    return url($path);
}

function absolute_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (IS_HTTPS ? 'https://' : 'http://') . $host . url($path);
}

function redirect(string $to): void
{
    if (!preg_match('~^https?://~', $to) && strpos($to, '/') !== 0) {
        $to = url($to);
    }
    // Uploads sent by the admin panel's progress bar ask for JSON instead of a redirect.
    if (!empty($_SERVER['HTTP_X_UPLOAD_XHR'])) {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => $to]);
        exit;
    }
    header('Location: ' . $to);
    exit;
}

/** Convert php.ini sizes like "64M" to bytes. */
function ini_bytes(string $val): int
{
    $val = trim($val);
    if ($val === '') return 0;
    $num = (float) $val;
    switch (strtolower(substr($val, -1))) {
        case 'g': $num *= 1024;
        // no break
        case 'm': $num *= 1024;
        // no break
        case 'k': $num *= 1024;
    }
    return (int) $num;
}

/** Largest upload the server accepts, in bytes. */
function max_upload_bytes(): int
{
    $a = ini_bytes((string) ini_get('upload_max_filesize'));
    $b = ini_bytes((string) ini_get('post_max_size'));
    $vals = array_filter([$a, $b]);
    return $vals ? min($vals) : 0;
}

function not_found(): void
{
    http_response_code(404);
    $page_title = 'Page not found';
    $active = '';
    require ROOT . '/includes/header.php';
    echo '<section class="section"><div class="container narrow center empty-state">'
        . '<p class="eyebrow">Error 404</p><h1>Page not found</h1>'
        . '<p class="muted">The page you are looking for doesn&rsquo;t exist or has been moved.</p>'
        . '<p><a class="btn btn-primary" href="' . e(url()) . '">Back to home ' . icon('arrow-right') . '</a></p>'
        . '</div></section>';
    require ROOT . '/includes/footer.php';
    exit;
}

/* ---------- Settings ---------- */

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return isset($all[$key]) && $all[$key] !== null ? (string) $all[$key] : $default;
}

function set_setting(string $key, ?string $value): void
{
    $st = db()->prepare('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $st->execute([$key, $value]);
    settings_all(true);
}

/* ---------- Text helpers ---------- */

/** Non-empty trimmed lines of a multi-line text field. */
function lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/** Split each line into parts by a separator: "A | B | C" => ['A','B','C']. */
function line_parts(?string $text, string $sep = '|', int $limit = -1): array
{
    $out = [];
    foreach (lines($text) as $line) {
        $parts = $limit > 0 ? explode($sep, $line, $limit) : explode($sep, $line);
        $out[] = array_map('trim', $parts);
    }
    return $out;
}

function tags(?string $text): array
{
    $out = [];
    foreach (explode(',', (string) $text) as $t) {
        $t = trim($t);
        if ($t !== '') {
            $out[] = $t;
        }
    }
    return $out;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('~[^a-z0-9]+~', '-', $text);
    $text = trim((string) $text, '-');
    return $text !== '' ? $text : 'item-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = $slug;
    $i = 2;
    $st = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ? AND id != ?");
    while (true) {
        $st->execute([$slug, $ignoreId]);
        if ((int) $st->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

/** Convert plain text into safe HTML paragraphs, with links made clickable. */
function format_text(?string $text): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $html = '';
    foreach (preg_split('/(\r?\n){2,}/', $text) as $para) {
        $p = e(trim($para));
        $p = preg_replace('~\*\*(.+?)\*\*~s', '<strong>$1</strong>', $p);
        $p = preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener">$1</a>', $p);
        if (preg_match('/^(- |\* )/m', $p) && count(array_filter(lines($p), function ($l) {
            return preg_match('/^(- |\* )/', $l) === 0;
        })) === 0) {
            $items = array_map(function ($l) {
                return '<li>' . preg_replace('/^(- |\* )/', '', $l) . '</li>';
            }, lines($p));
            $html .= '<ul>' . implode('', $items) . '</ul>';
        } else {
            $html .= '<p>' . nl2br($p, false) . '</p>';
        }
    }
    return $html;
}

function excerpt(?string $text, int $len = 140): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $len - 1)) . '…';
}

function human_size(int $bytes): string
{
    if ($bytes <= 0) {
        return '';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int) floor(log($bytes, 1024));
    $i = min($i, count($units) - 1);
    $val = $bytes / pow(1024, $i);
    return ($i === 0 ? (string) $bytes : number_format($val, $val >= 10 ? 0 : 1)) . ' ' . $units[$i];
}

function format_date(?string $date, string $format = 'M Y'): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : $date;
}

function youtube_id(?string $url): string
{
    $url = (string) $url;
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}

function video_embed_html(?string $url, string $title = 'Video'): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if ($id = youtube_id($url)) {
        return '<div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/' . e($id)
            . '" title="' . e($title) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
        return '<div class="video-frame"><iframe src="https://player.vimeo.com/video/' . e($m[1])
            . '" title="' . e($title) . '" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
    }
    if (preg_match('~\.(mp4|webm|mov)(\?.*)?$~i', $url)) {
        return '<div class="video-frame"><video src="' . e(media_url($url)) . '" controls preload="metadata"></video></div>';
    }
    return '<p><a class="btn btn-outline" href="' . e($url) . '" target="_blank" rel="noopener">' . icon('play') . ' Watch video</a></p>';
}

function file_ext(string $path): string
{
    return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}

function is_external_url(?string $path): bool
{
    return (bool) preg_match('~^https?://~i', (string) $path);
}

/** Short label shown on a file icon, e.g. PDF, ZIP, or LINK for a Google Drive link. */
function file_badge(string $path): string
{
    $ext = is_external_url($path) ? strtolower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION)) : file_ext($path);
    if ($ext === '' || strlen($ext) > 5 || !preg_match('/^[a-z0-9]+$/', $ext)) {
        return is_external_url($path) ? 'LINK' : 'FILE';
    }
    return strtoupper($ext);
}

/** Attributes for a download link: real download for our files, new tab for outside links. */
function download_attrs(string $path): string
{
    return is_external_url($path) ? ' target="_blank" rel="noopener"' : ' download';
}

/** Validate a pasted link (Google Drive, Dropbox, OneDrive…). Returns '' if not valid. */
function clean_link(string $url): string
{
    $url = trim($url);
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function file_icon_class(string $path): string
{
    if (is_external_url($path) && file_badge($path) === 'LINK') return 'link';
    $ext = strtolower(file_badge($path));
    if ($ext === 'pdf') return 'pdf';
    if (in_array($ext, ['xls', 'xlsx', 'csv'], true)) return 'sheet';
    if (in_array($ext, ['doc', 'docx', 'txt', 'md'], true)) return 'doc';
    if (in_array($ext, ['zip', 'rar', '7z', 'gz', 'tar'], true)) return 'zip';
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return 'img';
    return 'file';
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function render_flashes(): string
{
    $html = '';
    foreach (get_flashes() as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    return $html;
}

/* ---------- Security ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        $msg = 'The upload was too large for your hosting limit (' . human_size(max_upload_bytes()) . ' per upload). '
            . 'Upload fewer files at once, compress the video, or use a YouTube link instead.';
        if (!empty($_SERVER['HTTP_X_UPLOAD_XHR'])) {
            header('Content-Type: application/json');
            exit(json_encode(['error' => $msg]));
        }
        exit($msg . ' <a href="javascript:history.back()">Go back</a>');
    }
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_logged_in'])
        && ($_SESSION['admin_ua'] ?? '') === hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('admin/login.php');
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: no-store');
}

/** Visitor IP. Behind Cloudflare, use the real visitor IP it sends — but only when the request really comes from Cloudflare. */
function client_ip(): string
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $cf = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) && is_cloudflare_ip($remote)) {
        return substr($cf, 0, 64);
    }
    return substr($remote, 0, 64);
}

function is_cloudflare_ip(string $ip): bool
{
    $ranges = ['173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32'];
    $bin = @inet_pton($ip);
    if ($bin === false) {
        return false;
    }
    foreach ($ranges as $range) {
        [$net, $bits] = explode('/', $range);
        $netBin = inet_pton($net);
        if (strlen($netBin) !== strlen($bin)) {
            continue;
        }
        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;
        if (substr($bin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            continue;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = chr((0xFF << (8 - $rem)) & 0xFF);
        if ((substr($bin, $bytes, 1) & $mask) === (substr($netBin, $bytes, 1) & $mask)) {
            return true;
        }
    }
    return false;
}

/* ---------- File uploads ---------- */

const UPLOAD_TYPES = [
    'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'video' => ['mp4', 'webm', 'mov', 'm4v'],
    'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'md', 'zip', 'rar', '7z',
        'step', 'stp', 'stl', 'iges', 'igs', 'dxf', 'dwg', 'gbr', 'ino', 'py', 'c', 'cpp', 'h', 'json'],
];

function upload_kind_for(string $filename): string
{
    $ext = file_ext($filename);
    foreach (UPLOAD_TYPES as $kind => $exts) {
        if (in_array($ext, $exts, true)) {
            return $kind;
        }
    }
    return '';
}

/** Turn $_FILES['x'] (single or multiple) into a list of single-file arrays, skipping empty slots. */
function files_list(string $field): array
{
    if (empty($_FILES[$field])) {
        return [];
    }
    $f = $_FILES[$field];
    if (!is_array($f['name'])) {
        return ($f['error'] === UPLOAD_ERR_NO_FILE) ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name' => $name,
            'type' => $f['type'][$i],
            'tmp_name' => $f['tmp_name'][$i],
            'error' => $f['error'][$i],
            'size' => $f['size'][$i],
        ];
    }
    return $out;
}

/**
 * Store an uploaded file safely. $allowed is a list of kinds (image|video|document).
 * Returns ['path','size','kind','name'] or throws RuntimeException with a friendly message.
 */
function store_upload(array $file, array $allowed = ['image', 'video', 'document']): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'is larger than your hosting allows (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE => 'is too large.',
            UPLOAD_ERR_PARTIAL => 'was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'could not be saved (missing temp folder).',
            UPLOAD_ERR_CANT_WRITE => 'could not be written to disk.',
        ];
        throw new RuntimeException('"' . $file['name'] . '" ' . ($map[$file['error']] ?? 'failed to upload.'));
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload.');
    }
    $kind = upload_kind_for($file['name']);
    if ($kind === '' || !in_array($kind, $allowed, true)) {
        throw new RuntimeException('"' . $file['name'] . '" is not an allowed file type.');
    }
    $ext = file_ext($file['name']);

    if ($kind === 'image') {
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new RuntimeException('"' . $file['name'] . '" is not a valid image.');
        }
    }

    $folder = $kind === 'image' ? 'images' : ($kind === 'video' ? 'videos' : 'documents');
    $dir = UPLOAD_DIR . '/' . $folder . '/' . date('Y/m');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('Could not create the upload folder. Check that /uploads is writable.');
    }
    // Some hosts create folders as private (0700), which blocks visitors (403). Make them public-readable.
    for ($d = $dir; strlen($d) > strlen(UPLOAD_DIR); $d = dirname($d)) {
        @chmod($d, 0755);
    }
    $base = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
    $base = substr($base, 0, 50);
    $name = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save "' . $file['name'] . '".');
    }
    @chmod($dest, 0644);
    if ($kind === 'image') {
        optimize_image($dest);
    }
    clearstatcache(true, $dest);
    return [
        'path' => 'uploads/' . $folder . '/' . date('Y/m') . '/' . $name,
        'size' => (int) filesize($dest),
        'kind' => $kind,
        'name' => $file['name'],
    ];
}

/** Shrink very large photos so pages stay fast (needs the GD extension; skipped otherwise). */
function optimize_image(string $path, int $maxWidth = 2000): void
{
    if (!function_exists('imagecreatefromjpeg')) {
        return;
    }
    $info = @getimagesize($path);
    if (!$info || $info[0] <= $maxWidth) {
        return;
    }
    [$w, $h, $type] = $info;
    switch ($type) {
        case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($path); break;
        case IMAGETYPE_PNG: $src = @imagecreatefrompng($path); break;
        case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
        default: return;
    }
    if (!$src) {
        return;
    }
    $nw = $maxWidth;
    $nh = (int) round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($type !== IMAGETYPE_JPEG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if ($type === IMAGETYPE_JPEG) imagejpeg($dst, $path, 85);
    elseif ($type === IMAGETYPE_PNG) imagepng($dst, $path, 7);
    elseif ($type === IMAGETYPE_WEBP) imagewebp($dst, $path, 85);
    imagedestroy($src);
    imagedestroy($dst);
}

/** Delete a stored file, but only if it lives inside /uploads. */
function delete_upload(?string $path): void
{
    $path = (string) $path;
    if (strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    $full = ROOT . '/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}

/* ---------- Data access ---------- */

function count_rows(string $table, string $where = '1=1'): int
{
    return (int) db()->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
}

function project_media(int $projectId, ?string $type = null): array
{
    if ($type) {
        $st = db()->prepare('SELECT * FROM project_media WHERE project_id = ? AND type = ? ORDER BY sort_order, id');
        $st->execute([$projectId, $type]);
    } else {
        $st = db()->prepare('SELECT * FROM project_media WHERE project_id = ? ORDER BY sort_order, id');
        $st->execute([$projectId]);
    }
    return $st->fetchAll();
}

function status_class(string $status): string
{
    $s = strtolower($status);
    if (strpos($s, 'patent') !== false) return 'amber';
    if (strpos($s, 'progress') !== false || strpos($s, 'ongoing') !== false) return 'blue';
    if (strpos($s, 'review') !== false) return 'violet';
    return 'green';
}

function site_name(): string
{
    return setting('site_name', 'My Portfolio');
}

function initials(string $name): string
{
    $name = preg_replace('/^(md\.?|mr\.?|dr\.?|engr\.?)\s+/i', '', trim($name));
    $parts = preg_split('/\s+/', (string) $name);
    $out = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        if (mb_strlen($out) >= 2) {
            break;
        }
    }
    return $out !== '' ? $out : 'P';
}

function logo_html(): string
{
    $logo = setting('logo');
    if ($logo !== '') {
        return '<img class="logo-img" src="' . e(media_url($logo)) . '" alt="' . e(site_name()) . ' logo">';
    }
    return '<span class="logo-mark" aria-hidden="true">' . e(initials(site_name())) . '</span>';
}

function social_links(): array
{
    $list = [
        'linkedin' => ['LinkedIn', setting('social_linkedin')],
        'github' => ['GitHub', setting('social_github')],
        'youtube' => ['YouTube', setting('social_youtube')],
        'instagram' => ['Instagram', setting('social_instagram')],
        'facebook' => ['Facebook', setting('social_facebook')],
        'x' => ['X / Twitter', setting('social_x')],
        'scholar' => ['Google Scholar', setting('social_scholar')],
        'researchgate' => ['ResearchGate', setting('social_researchgate')],
    ];
    $out = [];
    foreach ($list as $key => [$label, $href]) {
        if (trim($href) !== '') {
            $out[] = ['icon' => $key, 'label' => $label, 'url' => $href];
        }
    }
    if (setting('contact_email') !== '') {
        $out[] = ['icon' => 'mail', 'label' => 'Email', 'url' => 'mailto:' . setting('contact_email')];
    }
    return $out;
}

function cv_url(): string
{
    $cv = setting('cv_file');
    return $cv !== '' ? media_url($cv) : url('cv.php');
}
