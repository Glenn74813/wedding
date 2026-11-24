<?php
// Shared helpers. All data lives in a Postgres database (DATABASE_URL env var).
if (PHP_SAPI !== 'cli' && !defined('NO_SESSION')) session_start();

function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $u = parse_url(getenv('DATABASE_URL') ?: '');
    if (empty($u['host'])) die('DATABASE_URL is not set.');
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=require', $u['host'], $u['port'] ?? 5432, ltrim($u['path'], '/'));
    $pdo = new PDO($dsn, urldecode($u['user']), urldecode($u['pass'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rsvps(id TEXT PRIMARY KEY, name TEXT, attending TEXT, guests INT, note TEXT, created TIMESTAMP DEFAULT now())");
    $pdo->exec("CREATE TABLE IF NOT EXISTS photos(id SERIAL PRIMARY KEY, mime TEXT, data BYTEA, pos INT DEFAULT 0)");
    return $pdo;
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function save_settings($s) {
    $st = db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT (k) DO UPDATE SET v=EXCLUDED.v');
    foreach ($s as $k => $v) $st->execute([$k, $v]);
}
function settings() {
    $defaults = [
        'bride' => 'Isabella', 'groom' => 'Alexander', 'date' => '2027-06-12T16:00',
        'venue' => 'The Grand Ballroom', 'address' => '123 Garden Avenue, Quezon City',
        'message' => 'Together with their families, they joyfully invite you to celebrate their marriage.',
        'rsvp_deadline' => 'May 12, 2027',
    ];
    $rows = db()->query('SELECT k,v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!isset($rows['password_hash'])) {
        $defaults['password_hash'] = password_hash(getenv('ADMIN_PASSWORD') ?: 'admin123', PASSWORD_DEFAULT);
        save_settings($defaults);
        return $defaults;
    }
    return array_merge($defaults, $rows);
}
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok() {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}
