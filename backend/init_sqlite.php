<?php
header('Content-Type: application/json; charset=utf-8');

// Only allow local invocation for safety (remove or adjust for trusted servers)
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $allowedHosts)) {
    // If run from CLI, REMOTE_ADDR may be missing — allow CLI
    if (php_sapi_name() !== 'cli') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
}

$dbFile = __DIR__ . '/db.sqlite';

try {
    // Ensure directory writable
    if (!is_dir(dirname($dbFile)) || !is_writable(dirname($dbFile))) {
        // attempt to create directory (should already exist)
        @mkdir(dirname($dbFile), 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if not exists (SQLite syntax)
    $sql = "CREATE TABLE IF NOT EXISTS todo (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        szoveg TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Make the sqlite file readable/writable by web server user if possible
    @chmod($dbFile, 0664);

    echo json_encode(['status' => 'success', 'db' => $dbFile]);
    exit;
} catch (Exception $e) {
    error_log("init_sqlite.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create SQLite DB']);
    exit;
}
?>
