<?php
// Quick debug helper: visit index.php?debug=1 to see server/path info
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "PHP_SAPI: " . PHP_SAPI . PHP_EOL;
    echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . PHP_EOL;
    echo "Requested URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . PHP_EOL;
    echo "Script filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . PHP_EOL;
    echo "This file realpath: " . (realpath(__FILE__) ?: 'N/A') . PHP_EOL;
    echo "File exists: " . (file_exists(__FILE__) ? 'yes' : 'no') . PHP_EOL;
    echo "Permissions (octal): " . substr(sprintf('%o', @fileperms(__FILE__)), -4) . PHP_EOL;
    echo "Apache error log: c:\\xampp\\apache\\logs\\error.log (check for errors)" . PHP_EOL;
    exit;
}

require_once __DIR__ . '/backend/db.php';

$errors = [];
$success = null;

// Handle form POST to add a todo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$text = trim((string)($_POST['text'] ?? ''));
	if ($text === '') {
		$errors[] = 'Please enter a todo.';
	} else {
		try {
			$pdo = null;
			if (function_exists('getDb')) $pdo = getDb();
			if ($pdo) {
				$stmt = $pdo->prepare("INSERT INTO todo (szoveg) VALUES (:text)");
				$stmt->execute([':text' => $text]);
			} elseif (!empty($conn)) {
				// mysqli fallback
				$stmt = $conn->prepare("INSERT INTO todo (szoveg) VALUES (?)");
				if ($stmt === false) throw new Exception("MySQLi prepare failed: " . $conn->error);
				$stmt->bind_param("s", $text);
				$stmt->execute();
				$stmt->close();
			} else {
				throw new Exception("No database connection available");
			}
			// Redirect to avoid form resubmission
			header('Location: ' . $_SERVER['PHP_SELF']);
			exit;
		} catch (Exception $e) {
			error_log("index.php insert error: " . $e->getMessage());
			$errors[] = 'Failed to add todo (see server log).';
		}
	}
}

// Load todos for display
$todos = [];
try {
	$pdo = null;
	if (function_exists('getDb')) $pdo = getDb();
	if ($pdo) {
		$stmt = $pdo->query("SELECT id, szoveg FROM todo ORDER BY id DESC");
		$todos = $stmt->fetchAll();
	} elseif (!empty($conn)) {
		$res = $conn->query("SELECT id, szoveg FROM todo ORDER BY id DESC");
		while ($row = $res->fetch_assoc()) {
			$todos[] = $row;
		}
	}
} catch (Exception $e) {
	error_log("index.php select error: " . $e->getMessage());
	$errors[] = 'Failed to load todos (see server log).';
}

// Simple HTML UI
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Todo - Server UI</title>
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<style>
		body { font-family: Arial, sans-serif; max-width:800px; margin:40px auto; padding:0 16px; }
		form { display:flex; gap:8px; margin-bottom:16px; }
		input[type="text"] { flex:1; padding:8px; font-size:16px; }
		button { padding:8px 12px; font-size:16px; }
		.todo { padding:10px; border:1px solid #eee; margin-bottom:8px; border-radius:4px; background:#fff; }
		.error { color:#b00020; }
		.info { color:#007700; }
	</style>
</head>
<body>
	<h1>Todo List (server)</h1>

	<?php if ($errors): ?>
		<div class="error">
			<ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<input type="text" name="text" placeholder="Add a new task..." aria-label="New todo">
		<button type="submit">Add</button>
	</form>

	<?php if (empty($todos)): ?>
		<p>No tasks yet.</p>
	<?php else: ?>
		<?php foreach ($todos as $t): ?>
			<div class="todo">
				<strong>#<?= htmlspecialchars($t['id']) ?></strong>
				<span><?= htmlspecialchars($t['szoveg']) ?></span>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<p class="info">This page uses the same DB as the PHP API. If your React app is running, use it for the nicer UI.</p>
</body>
</html>
