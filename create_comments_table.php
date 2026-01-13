<?php
require_once 'database/config/database.php';

try {
    $pdo = getDB();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            status ENUM('approved', 'pending', 'spam') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");

    echo "✓ Comments table created successfully.";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
