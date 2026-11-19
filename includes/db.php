<?php
/**
 * SQLite database connection and schema. The database file is created
 * automatically in /data on first visit — no setup needed.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $file = DATA_DIR . '/' . db_filename();
    $pdo = new PDO('sqlite:' . $file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version < 1) {
        db_install($pdo);
    }
    return $pdo;
}

/** The database gets a random file name so it can't be guessed and downloaded, even on servers that ignore .htaccess. */
function db_filename(): string
{
    $cfg = DATA_DIR . '/config.php';
    if (is_file($cfg)) {
        $name = include $cfg;
        if (is_string($name) && preg_match('/^[a-z0-9\-]+\.sqlite$/', $name)) {
            return $name;
        }
    }
    $name = 'site-' . bin2hex(random_bytes(12)) . '.sqlite';
    if (@file_put_contents($cfg, "<?php\n// Name of the database file in this folder. Do not share.\nreturn '" . $name . "';\n") === false) {
        throw new RuntimeException('The /data folder is not writable. Please set its permission to 755.');
    }
    return $name;
}

function db_install(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT
);
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    subtitle TEXT DEFAULT '',
    category TEXT DEFAULT '',
    tags TEXT DEFAULT '',
    summary TEXT DEFAULT '',
    overview TEXT DEFAULT '',
    features TEXT DEFAULT '',
    technologies TEXT DEFAULT '',
    specs TEXT DEFAULT '',
    results TEXT DEFAULT '',
    chart_data TEXT DEFAULT '',
    chart_label TEXT DEFAULT '',
    details TEXT DEFAULT '',
    cover_image TEXT DEFAULT '',
    diagram_image TEXT DEFAULT '',
    completed_date TEXT DEFAULT '',
    team_size TEXT DEFAULT '',
    duration TEXT DEFAULT '',
    status TEXT DEFAULT 'Completed',
    demo_url TEXT DEFAULT '',
    video_url TEXT DEFAULT '',
    featured INTEGER DEFAULT 0,
    published INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    views INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS project_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    type TEXT NOT NULL,              -- image | video | document
    file_path TEXT NOT NULL,
    title TEXT DEFAULT '',
    file_size INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS research (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    status TEXT DEFAULT 'Published',  -- Published | Patent Filed | In Progress | Under Review
    venue TEXT DEFAULT '',
    year TEXT DEFAULT '',
    authors TEXT DEFAULT '',
    summary TEXT DEFAULT '',
    body TEXT DEFAULT '',
    image TEXT DEFAULT '',
    link_url TEXT DEFAULT '',
    pdf_file TEXT DEFAULT '',
    doi TEXT DEFAULT '',
    featured INTEGER DEFAULT 0,
    published INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS gallery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT DEFAULT '',
    category TEXT DEFAULT '',
    file_path TEXT NOT NULL,
    type TEXT DEFAULT 'image',        -- image | video
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS downloads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT DEFAULT '',
    category TEXT DEFAULT '',
    file_path TEXT NOT NULL,
    file_size INTEGER DEFAULT 0,
    download_count INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT DEFAULT '',
    message TEXT NOT NULL,
    ip TEXT DEFAULT '',
    is_read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS login_attempts (
    ip TEXT PRIMARY KEY,
    attempts INTEGER DEFAULT 0,
    last_attempt INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_media_project ON project_media(project_id);
SQL);

    require_once __DIR__ . '/seed.php';
    seed_demo_content($pdo);
    $pdo->exec('PRAGMA user_version = 1');
}
