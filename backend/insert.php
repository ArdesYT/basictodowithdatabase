<?php
// Allow CORS for local development (adjust origin in production)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db.php';

$raw = file_get_contents("php://input");
if (empty($raw)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty request body']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Accept either "todo" or "text" keys for compatibility
$todoText = trim((string)($data['todo'] ?? $data['text'] ?? ''));

if ($todoText === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Todo text is required']);
    exit;
}

// Try PDO first (preferred), fallback to mysqli if PDO is not available or fails
try {
    $pdo = getDb();
    if ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO todo (szoveg) VALUES (:text)");
        $stmt->execute([':text' => $todoText]);
        $id = $pdo->lastInsertId();
        http_response_code(201);
        echo json_encode(['status' => 'success', 'id' => $id, 'todo' => $todoText]);
        exit;
    } elseif (!empty($conn)) {
        // Fallback to mysqli
        $stmt = $conn->prepare("INSERT INTO todo (szoveg) VALUES (?)");
        if ($stmt === false) {
            throw new Exception("MySQLi prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $todoText);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            http_response_code(201);
            echo json_encode(['status' => 'success', 'id' => $id, 'todo' => $todoText]);
            $stmt->close();
            $conn->close();
            exit;
        } else {
            throw new Exception("MySQLi execute failed: " . $conn->error);
        }
    } else {
        throw new Exception("No DB connection available");
    }
} catch (PDOException $e) {
    // If table not found, log and return appropriate error
    error_log("insert.php PDO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
} catch (Exception $e) {
    error_log("insert.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to add todo']);
    exit;
}

?>