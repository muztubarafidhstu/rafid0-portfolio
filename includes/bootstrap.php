<?php
/**
 * Loaded first by every page. Sets up paths, sessions, the database and helpers.
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('UPLOAD_DIR', ROOT . '/uploads');
define('APP_VERSION', '1.0.0');

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', DATA_DIR . '/error.log');

// Work out the base URL so the site works in the domain root or in a sub-folder.
$__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$__dir = str_replace('\\', '/', dirname($__script));
if (basename($__dir) === 'admin') {
    $__dir = dirname($__dir);
}
define('BASE_URL', rtrim(str_replace('\\', '/', $__dir), '/'));

$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');
define('IS_HTTPS', $__https);

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_name('pf_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => (BASE_URL === '' ? '/' : BASE_URL . '/'),
        'secure' => IS_HTTPS,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/partials.php';
