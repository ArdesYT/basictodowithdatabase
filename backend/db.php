<?php
// Choose driver via env var: 'mysql' (default) or 'sqlite'
$DB_DRIVER = getenv('DB_DRIVER') ?: 'mysql';

if ($DB_DRIVER === 'sqlite') {
    // Use SQLite file in backend/db.sqlite
    $sqliteFile = __DIR__ . '/db.sqlite';
    // If DB file does not exist, you can run init_sqlite.php to create it.
    try {
        $pdo = new PDO('sqlite:' . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SQLite connection error: " . $e->getMessage());
        http_response_code(500);
        exit(json_encode(['error' => 'Database connection failed']));
    }

    // No mysqli connection in SQLite mode; keep $conn null for compatibility checks
    $conn = null;

} else {
    // Default: MySQL (via PDO) with optional mysqli fallback
    $DB_HOST = getenv('DB_HOST') ?: 'localhost';
    $DB_NAME = getenv('DB_NAME') ?: 'todos';
    $DB_USER = getenv('DB_USER') ?: 'root';
    $DB_PASS = getenv('DB_PASS') ?: '';
    $DB_CHAR = getenv('DB_CHAR') ?: 'utf8mb4';

    // Initialize mysqli for backward compatibility ($conn)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        $conn->set_charset($DB_CHAR);
    } catch (mysqli_sql_exception $e) {
        error_log("MySQLi connection error: " . $e->getMessage());
        $conn = null;
    }

    // Initialize PDO (preferred API)
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHAR}";
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log("PDO connection error: " . $e->getMessage());
        $pdo = null;
    }

    // Ensure at least one connection is available
    if ($pdo === null && $conn === null) {
        http_response_code(500);
        // Do not leak DB details to client; log and exit
        error_log("No database connections available (pdo and mysqli are null)");
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Helper to return PDO instance (if available)
function getDb(): ?PDO {
    global $pdo;
    return $pdo ?? null;
}
?>
