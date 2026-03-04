-- MySQL (use in phpMyAdmin / mysql CLI)
-- CREATE DATABASE IF NOT EXISTS todos CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE todos;
CREATE TABLE IF NOT EXISTS todo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  szoveg TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SQLite (use with sqlite3 or the provided init_sqlite.php)
-- SQLite variant (same table semantics)
-- CREATE TABLE IF NOT EXISTS todo (
--   id INTEGER PRIMARY KEY AUTOINCREMENT,
--   szoveg TEXT NOT NULL,
--   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
-- );
